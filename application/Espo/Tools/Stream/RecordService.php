<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\Select\SearchParams;
use Espo\ORM\EntityManager;
use Espo\Entities\Subscription;
use Espo\Entities\User;
use Espo\Entities\Note;
use Espo\Entities\Email;
use Espo\Core\Utils\Metadata;
use Espo\Core\Acl;
use Espo\Core\AclManager;
use Espo\Core\Acl\Table;
use Espo\Core\Acl\Exceptions\NotImplemented as AclNotImplemented;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Acl\UserAclManagerProvider;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder as SelectQueryBuilder;

class RecordService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Metadata $metadata,
        private Acl $acl,
        private AclManager $aclManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private UserAclManagerProvider $userAclManagerProvider,
        private NoteAccessControl $noteAccessControl
    ) {}

    /**
     * Find user stream records.
     *
     * @return RecordCollection<Note>
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function findUser(?string $userId, SearchParams $searchParams): RecordCollection
    {
        $userId ??= $this->user->getId();

        $offset = $searchParams->getOffset() ?? 0;
        $maxSize = $searchParams->getMaxSize();

        $sqLimit = $offset + $maxSize + 1;

        $user = $userId === $this->user->getId() ?
            $this->user :
            $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            throw new NotFound("User not found.");
        }

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        if (!$this->acl->checkUserPermission($user, 'user')) {
            throw new Forbidden("No user permission access.");
        }

        $queryList = [];

        $baseBuilder = $this->buildBaseQueryBuilder($searchParams)
            ->select([
                'id',
                'number',
                'type',
                'post',
                'data',
                'parentType',
                'parentId',
                'relatedType',
                'relatedId',
                'targetType',
                'createdAt',
                'createdById',
                'createdByName',
                'isGlobal',
                'isInternal',
                'createdByGender',
            ])
            ->order('number', Order::DESC)
            ->limit(0, $sqLimit);

        $this->buildSubscriptionQueries($user, $baseBuilder, $queryList);
        $this->buildSubscriptionSuperQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToUserQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToPortalQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToTeamsQuery($user, $baseBuilder, $queryList);
        $this->buildPostedByUserQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToGlobalQuery($user, $baseBuilder, $queryList);

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union()
            ->all()
            ->order('number', 'DESC')
            ->limit($offset, $maxSize + 1);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sql = $this->entityManager
            ->getQueryComposer()
            ->compose($unionQuery);

        $sthCollection = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->findBySql($sql);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->createFromSthCollection($sthCollection);

        foreach ($collection as $e) {
            $this->loadNoteAdditionalFields($e);
            $this->applyAccessControlToNote($e, $user);
        }

        return RecordCollection::createNoCount($collection, $maxSize);
    }

    /**
     * @return array<string|int, mixed>
     */
    private function getSubscriptionIgnoreWhereClause(User $user): array
    {
        $ignoreScopeList = $this->getIgnoreScopeList($user, true);
        $ignoreRelatedScopeList = $this->getIgnoreScopeList($user);

        if (empty($ignoreScopeList)) {
            return [];
        }

        $whereClause = [];

        $whereClause[] = [
            'OR' => [
                'relatedType' => null,
                'relatedType!=' => $ignoreRelatedScopeList,
            ]
        ];

        $whereClause[] = [
            'OR' => [
                'parentType' => null,
                'parentType!=' => $ignoreScopeList,
            ]
        ];

        if (in_array(Email::ENTITY_TYPE, $ignoreRelatedScopeList)) {
            $whereClause[] = [
                'type!=' => [
                    Note::TYPE_EMAIL_RECEIVED,
                    Note::TYPE_EMAIL_SENT,
                ],
            ];
        }

        return $whereClause;
    }

    private function loadNoteAdditionalFields(Note $note): void
    {
        $note->loadAdditionalFields();
    }

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
        if ($scope === User::ENTITY_TYPE) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntityById($scope, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntity($entity, Table::ACTION_STREAM)) {
            throw new Forbidden();
        }

        $builder = $this->buildBaseQueryBuilder($searchParams);

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

        $builder->where($where);

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
            if (
                $e->getType() === Note::TYPE_POST ||
                $e->getType() === Note::TYPE_EMAIL_RECEIVED
            ) {
                $e->loadAttachments();
            }

            if (
                $e->getParentId() && $e->getParentType() &&
                ($e->getParentId() !== $id || $e->getParentType() !== $scope)
            ) {
                $e->loadParentNameField('parent');
            }

            if ($e->getRelatedId() && $e->getRelatedType()) {
                $e->loadParentNameField('related');
            }

            $this->applyAccessControlToNote($e);
        }

        $count = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->clone($countBuilder->build())
            ->count();

        return RecordCollection::create($collection, $count);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function buildBaseQueryBuilder(SearchParams $searchParams): SelectQueryBuilder
    {
        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Note::ENTITY_TYPE);

        if (
            $searchParams->getWhere() ||
            $searchParams->getTextFilter() ||
            $searchParams->getPrimaryFilter() ||
            $searchParams->getBoolFilterList() !== []
        ) {
            $builder = $this->selectBuilderFactory
                ->create()
                ->from(Note::ENTITY_TYPE)
                ->withComplexExpressionsForbidden()
                ->withWherePermissionCheck()
                ->withSearchParams(
                    $searchParams
                        ->withOffset(null)
                        ->withMaxSize(null)
                )
                ->buildQueryBuilder()
                ->order([]);
        }

        return $builder;
    }

    /**
     * @return string[]
     */
    private function getOnlyTeamEntityTypeList(User $user): array
    {
        if ($user->isPortal()) {
            return [];
        }

        $list = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if ($scope === User::ENTITY_TYPE) {
                continue;
            }

            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (
                $this->aclManager->checkReadOnlyTeam($user, $scope)
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getOnlyOwnEntityTypeList(User $user): array
    {
        if ($user->isPortal()) {
            return [];
        }

        $list = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if ($scope === User::ENTITY_TYPE) {
                continue;
            }

            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (
                $this->aclManager->checkReadOnlyOwn($user, $scope)
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    private function getUserAclManager(User $user): ?AclManager
    {
        try {
            return $this->userAclManagerProvider->get($user);
        }
        catch (Acl\Exceptions\NotAvailable) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    private function getNotAllEntityTypeList(User $user): array
    {
        if (!$user->isPortal()) {
            return [];
        }

        $aclManager = $this->getUserAclManager($user);

        $list = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if ($scope === User::ENTITY_TYPE) {
                continue;
            }

            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (
                !$aclManager ||
                $aclManager->getLevel($user, $scope, Table::ACTION_READ) !== Table::LEVEL_ALL
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getIgnoreScopeList(User $user, bool $forParent = false): array
    {
        $ignoreScopeList = [];

        $scopes = $this->metadata->get('scopes', []);

        $aclManager = $this->getUserAclManager($user);

        foreach ($scopes as $scope => $item) {
            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            try {
                $hasAccess =
                    $aclManager &&
                    $aclManager->checkScope($user, $scope, Table::ACTION_READ) &&
                    (!$forParent || $aclManager->checkScope($user, $scope, Table::ACTION_STREAM));
            }
            catch (AclNotImplemented) {
                $hasAccess = false;
            }

            if (!$hasAccess) {
                $ignoreScopeList[] = $scope;
            }
        }

        return $ignoreScopeList;
    }

    private function applyAccessControlToNote(Note $note, ?User $user = null): void
    {
        if (!$user) {
            $user = $this->user;
        }

        $this->noteAccessControl->apply($note, $user);
    }

    /**
     * @param array<string|int, mixed> $where
     */
    private function applyAccess(
        SelectQueryBuilder $builder,
        string $id,
        string $scope,
        array &$where
    ): void {

        if ($this->user->isPortal()) {
            return;
        }

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($this->user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($this->user);

        if (
            !count($onlyTeamEntityTypeList) &&
            !count($onlyOwnEntityTypeList)
        ) {
            return;
        }

        $builder
            ->distinct()
            ->leftJoin('teams')
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
        $ignoreScopeList = $this->getIgnoreScopeList($this->user, true);
        $ignoreRelatedScopeList = $this->getIgnoreScopeList($this->user);

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
    private function applyPortalAccess(SelectQueryBuilder $builder, array &$where): void
    {
        if (!$this->user->isPortal()) {
            return;
        }

        $notAllEntityTypeList = $this->getNotAllEntityTypeList($this->user);

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
     * @param string[] $onlyTeamEntityTypeList
     * @param string[] $onlyOwnEntityTypeList
     * @param Select[] $queryList
     */
    private function buildSubscriptionQueriesInternal(
        User $user,
        SelectQueryBuilder $builder,
        array &$queryList,
        array $onlyTeamEntityTypeList,
        array $onlyOwnEntityTypeList
    ): void {

        if ($user->isPortal()) {
            return;
        }

        $resetBuilder = clone $builder;

        $resetBuilder->where([
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
                ],
            ],
        ]);

        $queryList[] = $resetBuilder->build();

        if (count($onlyTeamEntityTypeList)) {
            $teamBuilder = clone $builder;

            $teamBuilder
                ->where([
                    'relatedId!=' => null,
                    'relatedType=' => $onlyTeamEntityTypeList,
                    'id=s' => SelectQueryBuilder::create()
                        ->select('id')
                        ->from(Note::ENTITY_TYPE)
                        ->leftJoin('NoteTeam', 'noteTeam', [
                            'noteTeam.noteId=:' => 'id',
                            'noteTeam.deleted' => false,
                        ])
                        ->leftJoin('NoteUser', 'noteUser', [
                            'noteUser.noteId=:' => 'id',
                            'noteUser.deleted' => false,
                        ])
                        ->where([
                            'OR' => [
                                'noteTeam.teamId' => $user->getTeamIdList(),
                                'noteUser.userId' => $user->getId(),
                            ]
                        ])
                        ->build()
                        ->getRaw(),
                ]);

            $queryList[] = $teamBuilder->build();
        }

        if (count($onlyOwnEntityTypeList)) {
            $ownBuilder = clone $builder;

            $ownBuilder
                ->where([
                    'relatedId!=' => null,
                    'relatedType=' => $onlyOwnEntityTypeList,
                    'id=s' => SelectQueryBuilder::create()
                        ->select('id')
                        ->from(Note::ENTITY_TYPE)
                        ->leftJoin('NoteUser', 'noteUser', [
                            'noteUser.noteId=:' => 'id',
                            'noteUser.deleted' => false,
                        ])
                        ->where(['noteUser.userId' => $user->getId()])
                        ->build()
                        ->getRaw(),
                ]);

            $queryList[] = $ownBuilder->build();
        }
    }

    /**
     * @param Select[] $queryList
     */
    private function buildSubscriptionQueriesPortal(
        User $user,
        SelectQueryBuilder $builder,
        array &$queryList
    ): void {

        if (!$user->isPortal()) {
            return;
        }

        $builder->where([
            'isInternal' => false,
        ]);

        $notAllEntityTypeList = $this->getNotAllEntityTypeList($user);

        $orGroup = [
            [
                'relatedId' => null,
            ],
            [
                'relatedId!=' => null,
                'relatedType!=' => $notAllEntityTypeList,
            ],
        ];

        $aclManager = $this->getUserAclManager($user);

        if ($aclManager && $aclManager->check($user, Email::ENTITY_TYPE, Table::ACTION_READ)) {
            $orGroup[] = [
                'relatedId!=' => null,
                'relatedType' => Email::ENTITY_TYPE,
                'noteUser.userId' => $user->getId(),
            ];

            $builder->leftJoin(
                'noteUser',
                'noteUser', [
                    'noteUser.noteId=:' => 'id',
                    'noteUser.deleted' => false,
                    'note.relatedType' => Email::ENTITY_TYPE,
                ]
            );
        }

        $builder->where([
            'OR' => $orGroup,
        ]);

        $queryList[] = $builder->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildSubscriptionQueries(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($user);
        $ignoreWhereClause = $this->getSubscriptionIgnoreWhereClause($user);

        $builder = clone $baseBuilder;

        $builder
            ->leftJoin('createdBy')
            ->join(
                Subscription::ENTITY_TYPE,
                'subscription',
                [
                    'entityType:' => 'parentType',
                    'entityId:' => 'parentId',
                    'subscription.userId' => $user->getId(),
                ]
            )
            ->where($ignoreWhereClause);

        if ($user->isPortal()) {
            $this->buildSubscriptionQueriesPortal($user, $builder, $queryList);

            return;
        }

        $this->buildSubscriptionQueriesInternal(
            $user,
            $builder,
            $queryList,
            $onlyTeamEntityTypeList,
            $onlyOwnEntityTypeList
        );
    }

    /**
     * @param Select[] $queryList
     */
    private function buildSubscriptionSuperQuery(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        if ($user->isPortal()) {
            return;
        }

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($user);
        $ignoreWhereClause = $this->getSubscriptionIgnoreWhereClause($user);

        $builder = clone $baseBuilder;

        $builder
            ->join(
                Subscription::ENTITY_TYPE,
                'subscription',
                [
                    'entityType:' => 'superParentType',
                    'entityId:' => 'superParentId',
                    'subscription.userId' => $user->getId(),
                ]
            )
            ->leftJoin(
                Subscription::ENTITY_TYPE,
                'subscriptionExclude',
                [
                    'entityType:' => 'parentType',
                    'entityId:' => 'parentId',
                    'subscription.userId' => $user->getId(),
                ]
            )
            ->where([
                'OR' => [
                    'parentId!=:' => 'superParentId',
                    'parentType!=:' => 'superParentType',
                ],
                'subscriptionExclude.id' => null,
            ])
            ->where($ignoreWhereClause);

        $resetBuilder = clone $builder;

        $resetBuilder->where([
            'OR' => [
                [
                    'relatedId!=' => null,
                    'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                ],
                [
                    'relatedId=' => null,
                    'parentType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                ],
            ],
        ]);

        $queryList[] = $resetBuilder->build();

        if (count($onlyTeamEntityTypeList)) {
            $teamBuilder = clone $builder;

            $teamBuilder
                ->distinct()
                ->leftJoin(
                    'noteTeam',
                    'noteTeam',
                    [
                        'noteTeam.noteId=:' => 'id',
                        'noteTeam.deleted' => false,
                    ]
                )
                ->leftJoin(
                    'noteUser',
                    'noteUser',
                    [
                        'noteUser.noteId=:' => 'id',
                        'noteUser.deleted' => false,
                    ]
                )
                ->where([
                    'OR' => [
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyTeamEntityTypeList,
                        ],
                        [
                            'relatedId=' => null,
                            'parentType=' => $onlyTeamEntityTypeList,
                        ],
                    ],
                    [
                        'OR' => [
                            'noteTeam.teamId' => $user->getTeamIdList(),
                            'noteUser.userId' => $user->getId(),
                        ],
                    ]
                ]);

            $queryList[] = $teamBuilder->build();
        }

        if (count($onlyOwnEntityTypeList)) {
            $ownBuilder = clone $builder;

            $ownBuilder
                ->distinct()
                ->leftJoin(
                    'noteUser',
                    'noteUser',
                    [
                        'noteUser.noteId=:' => 'id',
                        'noteUser.deleted' => false,
                    ]
                )
                ->where([
                    [
                        'relatedId!=' => null,
                        'relatedType=' => $onlyOwnEntityTypeList,
                    ],
                    'noteUser.userId' => $user->getId(),
                ]);

            $queryList[] = $ownBuilder->build();
        }
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToUserQuery(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('users')
            ->leftJoin('createdBy')
            ->where([
                'createdById!=' => $user->getId(),
                'usersMiddle.userId' => $user->getId(),
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToPortalQuery(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        if (!$user->isPortal()) {
            return;
        }

        $portalIdList = $user->getLinkMultipleIdList('portals');

        if ($portalIdList === []) {
            return;
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('portals')
            ->leftJoin('createdBy')
            ->where([
                'parentId' => null,
                'portalsMiddle.portalId' => $portalIdList,
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToTeamsQuery(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        if ($user->getTeamIdList() === []) {
            return;
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('teams')
            ->leftJoin('createdBy')
            ->where([
                'parentId' => null,
                'teamsMiddle.teamId' => $user->getTeamIdList(),
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedByUserQuery(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('createdBy')
            ->where([
                'createdById' => $user->getId(),
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToGlobalQuery(
        User $user,
        SelectQueryBuilder $baseBuilder,
        array &$queryList
    ): void {

        if (
            $user->isPortal() &&
            !$user->isAdmin() || $user->isApi()
        ) {
            return;
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('createdBy')
            ->where([
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'isGlobal' => true,
            ])
            ->build();
    }
}
