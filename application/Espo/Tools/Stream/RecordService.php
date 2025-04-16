<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Stream;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\Note;
use Espo\Entities\Email;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\Stream\RecordService\Helper;
use Espo\Tools\Stream\RecordService\NoteHelper;
use Espo\Tools\Stream\RecordService\QueryHelper;

class RecordService
{
    private const PINNED_MAX_SIZE = 100;

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Acl $acl,
        private NoteAccessControl $noteAccessControl,
        private Helper $helper,
        private QueryHelper $queryHelper,
        private Metadata $metadata,
        private NoteHelper $noteHelper,
        private MassNotePreparator $massNotePreparator,
        private SelectBuilderFactory $selectBuilderFactory,
    ) {}

    /**
     * Find a record stream records.
     *
     * @return RecordCollection<Note>
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function find(string $scope, string $id, SearchParams $searchParams): RecordCollection
    {
        $this->checkAccess($scope, $id);

        return $this->findInternal($scope, $id, $searchParams);
    }

    /**
     * Find a record stream records.
     *
     * @return RecordCollection<Note>
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function findUpdates(string $scope, string $id, SearchParams $searchParams): RecordCollection
    {
        if ($this->user->isPortal()) {
            throw new Forbidden();
        }

        if ($this->acl->getPermissionLevel(Acl\Permission::AUDIT) !== Table::LEVEL_YES) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntityById($scope, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityRead($entity)) {
            throw new Forbidden();
        }

        if ($entity instanceof User && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $searchParams = $searchParams->withPrimaryFilter('updates');

        return $this->findInternal($scope, $id, $searchParams);
    }

    /**
     * Get pinned notes.
     *
     * @return Collection<Note>
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function getPinned(string $scope, string $id): Collection
    {
        $this->checkAccess($scope, $id);

        $builder = $this->queryHelper->buildBaseQueryBuilder(SearchParams::create());

        $where = [
            'parentType' => $scope,
            'parentId' => $id,
            'isPinned' => true,
        ];

        if ($this->user->isPortal()) {
            $where[] = ['isInternal' => true];
        }

        $this->applyPortalAccess($builder, $where);
        $this->applyAccess($builder, $id, $scope, $where);
        $this->applyIgnore($where);
        $this->applyStatusIgnore($scope, $where);

        $builder->where($where);

        $builder
            ->limit(0, self::PINNED_MAX_SIZE)
            ->order('number', 'DESC');

        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->clone($builder->build())
            ->find();

        foreach ($collection as $item) {
            $this->prepareNote($item, $scope, $id);
        }

        $this->massNotePreparator->prepare($collection);

        return $collection;
    }

    /**
     * Find a record stream records.
     *
     * @return RecordCollection<Note>
     * @throws Forbidden
     * @throws BadRequest
     */
    private function findInternal(string $scope, string $id, SearchParams $searchParams): RecordCollection
    {
        $builder = $this->prepareSelectBuilder($scope, $id, $searchParams);

        $offset = $searchParams->getOffset();
        $maxSize = $searchParams->getMaxSize();

        $countBuilder = clone $builder;

        $builder
            ->limit($offset ?? 0, $maxSize)
            ->order('number', 'DESC');

        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->clone($builder->build())
            ->find();

        foreach ($collection as $e) {
            $this->prepareNote($e, $scope, $id);
        }

        $this->massNotePreparator->prepare($collection);

        $count = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->clone($countBuilder->build())
            ->count();

        return RecordCollection::create($collection, $count);
    }

    /**
     * @param array<string|int, mixed> $where
     */
    private function applyAccess(
        SelectBuilder $builder,
        string $id,
        string $scope,
        array &$where
    ): void {

        if ($this->user->isPortal()) {
            return;
        }

        $onlyTeamEntityTypeList = $this->helper->getOnlyTeamEntityTypeList($this->user);
        $onlyOwnEntityTypeList = $this->helper->getOnlyOwnEntityTypeList($this->user);

        if (
            !count($onlyTeamEntityTypeList) &&
            !count($onlyOwnEntityTypeList)
        ) {
            return;
        }

        $builder
            ->distinct()
            ->leftJoin(Field::TEAMS)
            ->leftJoin('users');

        $where[] = [
            'OR' => [
                'OR' => [
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => array_merge(
                            $onlyTeamEntityTypeList,
                            $onlyOwnEntityTypeList,
                        ),
                    ],
                    [
                        'relatedId=' => null,
                        'superParentId' => $id,
                        'superParentType' => $scope,
                        'parentId!=' => null,
                        'parentType!=' => array_merge(
                            $onlyTeamEntityTypeList,
                            $onlyOwnEntityTypeList,
                        ),
                    ],
                    [
                        'relatedId=' => null,
                        'parentType=' => $scope,
                        'parentId=' => $id,
                    ]
                ],
                [
                    'OR' => [
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyTeamEntityTypeList,
                        ],
                        [
                            'relatedId=' => null,
                            'parentType=' => $onlyTeamEntityTypeList,
                        ]
                    ],
                    [
                        'OR' => [
                            'teamsMiddle.teamId' => $this->user->getTeamIdList(),
                            'usersMiddle.userId' => $this->user->getId(),
                        ]
                    ]
                ],
                [
                    'OR' => [
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyOwnEntityTypeList,
                        ],
                        [
                            'relatedId=' => null,
                            'parentType=' => $onlyOwnEntityTypeList,
                        ]
                    ],
                    'usersMiddle.userId' => $this->user->getId(),
                ]
            ]
        ];
    }

    /**
     * @param array<string|int, mixed> $where
     */
    private function applyIgnore(array &$where): void
    {
        $ignoreScopeList = $this->helper->getIgnoreScopeList($this->user, true);
        $ignoreRelatedScopeList = $this->helper->getIgnoreScopeList($this->user);

        if ($ignoreRelatedScopeList === []) {
            return;
        }

        $where[] = [
            'OR' => [
                'relatedType' => null,
                'relatedType!=' => $ignoreRelatedScopeList,
            ]
        ];

        $where[] = [
            'OR' => [
                'parentType' => null,
                'parentType!=' => $ignoreScopeList,
            ]
        ];

        if (!in_array(Email::ENTITY_TYPE, $ignoreRelatedScopeList)) {
            return;
        }

        $where[] = [
            'type!=' => [
                Note::TYPE_EMAIL_RECEIVED,
                Note::TYPE_EMAIL_SENT,
            ]
        ];
    }

    /**
     * @param array<string|int, mixed> $where
     */
    private function applyPortalAccess(SelectBuilder $builder, array &$where): void
    {
        if (!$this->user->isPortal()) {
            return;
        }

        $notAllEntityTypeList = $this->helper->getNotAllEntityTypeList($this->user);

        $orGroup = [
            [
                'relatedId' => null,
            ],
            [
                'relatedId!=' => null,
                'relatedType!=' => $notAllEntityTypeList,
            ],
        ];

        if ($this->acl->check(Email::ENTITY_TYPE, Table::ACTION_READ)) {
            $builder->leftJoin(
                'noteUser',
                'noteUser',
                [
                    'noteUser.noteId=:' => 'id',
                    'noteUser.deleted' => false,
                    'note.relatedType' => Email::ENTITY_TYPE,
                ]
            );

            $orGroup[] = [
                'relatedId!=' => null,
                'relatedType' => Email::ENTITY_TYPE,
                'noteUser.userId' => $this->user->getId(),
            ];
        }

        $where[] = [
            'OR' => $orGroup,
        ];
    }

    /**
     * @param array<string|int, mixed> $where
     */
    private function applyStatusIgnore(string $scope, array &$where): void
    {
        $field = $this->metadata->get("scopes.$scope.statusField");

        if (!$field) {
            return;
        }

        if ($this->acl->checkField($scope, $field)) {
            return;
        }

        $where[] = ['type!=' => Note::TYPE_STATUS];
    }

    private function prepareNote(Note $note, string $scope, string $id): void
    {
        if (
            $note->getType() === Note::TYPE_POST ||
            $note->getType() === Note::TYPE_EMAIL_RECEIVED ||
            $note->getType() === Note::TYPE_EMAIL_SENT
        ) {
            $note->loadAttachments();
        }

        if (
            $note->getParentId() && $note->getParentType() &&
            ($note->getParentId() !== $id || $note->getParentType() !== $scope)
        ) {
            $note->loadParentNameField(Field::PARENT);
        }

        if ($note->getRelatedId() && $note->getRelatedType()) {
            $note->loadParentNameField('related');
        }

        $this->noteAccessControl->apply($note, $this->user);

        if ($note->getType() === Note::TYPE_UPDATE) {
            $this->noteHelper->prepare($note);
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function checkAccess(string $scope, string $id): void
    {
        if ($scope === User::ENTITY_TYPE) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntityById($scope, $id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        if (!$this->acl->checkEntity($entity, Table::ACTION_STREAM)) {
            throw new Forbidden("No stream access.");
        }
    }

    /**
     * @return RecordCollection<Attachment>
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     * @since 9.1.0
     * @internal
     */
    public function findAttachments(Entity $entity, SearchParams $searchParams): RecordCollection
    {
        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $this->checkAccess($entityType, $id);

        $noteBuilder = $this->prepareSelectBuilder($entityType, $id, SearchParams::create());

        $noteBuilder->select(['id']);

        $searchParams = $searchParams->withSelect([
            'id',
            'name',
            'type',
            'size',
            'parentType',
            'parentId',
            'createdAt',
            'createdById',
            'createdByName',
        ]);

        $query = $this->selectBuilderFactory
            ->create()
            ->from(Attachment::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->where(
                Condition::in(
                    Expression::column('parentId'),
                    $noteBuilder->build()
                )
            )
            ->where(['parentType' => Note::ENTITY_TYPE])
            ->build();

        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Attachment::class)
            ->clone($query)
            ->find();

        $total = $this->entityManager
            ->getRDBRepositoryByClass(Attachment::class)
            ->clone($query)
            ->count();

        return RecordCollection::create($collection, $total);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareSelectBuilder(string $scope, string $id, SearchParams $searchParams): SelectBuilder
    {
        $builder = $this->queryHelper->buildBaseQueryBuilder($searchParams);

        $where = $this->user->isPortal() ?
            [
                'parentType' => $scope,
                'parentId' => $id,
                'isInternal' => false,
            ] :
            [
                'OR' => [
                    [
                        'parentType' => $scope,
                        'parentId' => $id,
                    ],
                    [
                        'superParentType' => $scope,
                        'superParentId' => $id,
                    ],
                ]
            ];

        $this->applyPortalAccess($builder, $where);
        $this->applyAccess($builder, $id, $scope, $where);
        $this->applyIgnore($where);
        $this->applyStatusIgnore($scope, $where);

        $builder->where($where);

        return $builder;
    }
}
