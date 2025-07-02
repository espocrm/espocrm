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

namespace Espo\Tools\Notification;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Note;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\Collection;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\Stream\NoteAccessControl;

class RecordService
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata,
        private NoteAccessControl $noteAccessControl,
        private SelectBuilderFactory $selectBuilderFactory,
    ) {}

    /**
     * Get notifications for a user.
     *
     * @return RecordCollection<Notification>
     * @throws Error
     * @throws BadRequest
     * @throws Forbidden
     */
    public function get(User $user, SearchParams $searchParams): RecordCollection
    {
        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(Notification::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->where([Notification::ATTR_USER_ID => $user->getId()])
            ->order(Notification::ATTR_NUMBER, SearchParams::ORDER_DESC);

        if ($this->isGroupingEnabled()) {
            $queryBuilder->where($this->getActionIdWhere($user->getId()));
        }

        /*$queryBuilder
            ->leftJoin(
                Join
                    ::createWithSubQuery(
                        SelectBuilder::create()
                            ->from(Notification::ENTITY_TYPE)
                            ->select('actionId')
                            ->select(
                                Selection::create(
                                    Expr::max(Expr::column('number')),
                                    'maxNumber'
                                )
                            )
                            ->where(
                                Expr::isNotNull(Expr::column('actionId'))
                            )
                            ->group('actionId')
                            ->build(),
                        'subLatest'
                    )
                    ->withConditions(
                        Cond::and(
                            Cond::equal(
                                Expr::column('notification.actionId'),
                                Expr::alias('subLatest.actionId')
                            ),
                            Cond::equal(
                                Expr::column('notification.number'),
                                Expr::alias('subLatest.maxNumber')
                            ),
                        )
                    )
            );*/

        $offset = $searchParams->getOffset();
        $limit = $searchParams->getMaxSize();

        if ($limit) {
            $queryBuilder->limit($offset, $limit + 1);
        }

        $ignoreScopeList = $this->getIgnoreScopeList();

        if ($ignoreScopeList !== []) {
            $queryBuilder->where([
                'OR' => [
                    'relatedParentType' => null,
                    'relatedParentType!=' => $ignoreScopeList,
                ],
            ]);
        }

        $query = $queryBuilder->build();

        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->clone($query)
            ->find();

        if (!$collection instanceof EntityCollection) {
            throw new Error("Collection is not instance of EntityCollection.");
        }

        $collection = $this->prepareCollection($collection, $user);

        $groupedCountMap = $this->getGroupedCountMap($collection, $user->getId());

        $ids = [];
        $actionIds = [];

        foreach ($collection as  $entity) {
            $ids[] = $entity->getId();

            $groupedCount = null;

            if ($entity->getActionId() && $this->isGroupingEnabled()) {
                $actionIds[] = $entity->getActionId();

                $groupedCount = $groupedCountMap[$entity->getActionId()] ?? 0;
            }

            $entity->set('groupedCount', $groupedCount);
        }

        $collection = new EntityCollection([...$collection], Notification::ENTITY_TYPE);

        $this->markAsRead($user, $ids, $actionIds);

        return RecordCollection::createNoCount($collection, $limit);
    }

    /**
     * @param Collection<Notification> $collection
     * @return EntityCollection<Notification>
     */
    public function prepareCollection(Collection $collection, User $user): EntityCollection
    {
        if (!$collection instanceof EntityCollection) {
            $collection = new EntityCollection([...$collection], Notification::ENTITY_TYPE);
        }

        $limit = count($collection);

        foreach ($collection as $i => $entity) {
            if ($i === $limit) {
                break;
            }

            $this->prepareListItem(
                entity: $entity,
                index: $i,
                collection: $collection,
                count: $limit,
                user: $user,
            );
        }

        /** @var EntityCollection<Notification> */
        return new EntityCollection([...$collection], Notification::ENTITY_TYPE);
    }

    /**
     * @param string[] $ids
     * @param string[] $actionIds
     */
    private function markAsRead(User $user, array $ids, array $actionIds): void
    {
        if ($ids === [] && $actionIds === []) {
            return;
        }

        $query = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Notification::ENTITY_TYPE)
            ->set([Notification::ATTR_READ => true])
            ->where([Notification::ATTR_USER_ID => $user->getId()])
            ->where(
                Cond::or(
                    Cond::in(Expr::column(Attribute::ID), $ids),
                    Cond::in(Expr::column(Notification::ATTR_ACTION_ID), $actionIds),
                )
            )
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    /**
     * @param EntityCollection<Notification> $collection
     */
    private function prepareListItem(
        Notification $entity,
        int $index,
        EntityCollection $collection,
        ?int &$count,
        User $user
    ): void {

        $noteId = $this->getNoteId($entity);

        if (!$noteId) {
            return;
        }

        if (
            !in_array($entity->getType(), [
                Notification::TYPE_NOTE,
                Notification::TYPE_MENTION_IN_POST,
                Notification::TYPE_USER_REACTION,
            ])
        ) {
            return;
        }

        $note = $this->entityManager->getRDBRepositoryByClass(Note::class)->getById($noteId);

        if (!$note) {
            unset($collection[$index]);

            if ($count !== null) {
                $count--;
            }

            $this->entityManager->removeEntity($entity);

            return;
        }

        $this->noteAccessControl->apply($note, $user);
        $this->loadNoteFields($note, $entity);

        $entity->set('noteData', $note->getValueMap());
    }

    public function getNotReadCount(string $userId): int
    {
        $whereClause = [
            Notification::ATTR_USER_ID => $userId,
            Notification::ATTR_READ => false,
        ];

        $ignoreScopeList = $this->getIgnoreScopeList();

        if (count($ignoreScopeList)) {
            $whereClause[] = [
                'OR' => [
                    'relatedParentType' => null,
                    'relatedParentType!=' => $ignoreScopeList,
                ]
            ];
        }

        $builder = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where($whereClause);

        if ($this->isGroupingEnabled()) {
            $builder->where($this->getActionIdWhere($userId));
        }

        return $builder->count();
    }

    public function markAllRead(string $userId): bool
    {
        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Notification::ENTITY_TYPE)
            ->set(['read' => true])
            ->where([
                'userId' => $userId,
                'read' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    /**
     * @return string[]
     */
    private function getIgnoreScopeList(): array
    {
        $ignoreScopeList = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (!$this->acl->checkScope($scope)) {
                $ignoreScopeList[] = $scope;
            }
        }

        return $ignoreScopeList;
    }

    private function getNoteId(Notification $entity): ?string
    {
        $noteId = null;

        $data = $entity->getData();

        if ($data) {
            $noteId = $data->noteId ?? null;
        }

        if ($entity->getRelated()?->getEntityType() === Note::ENTITY_TYPE) {
            $noteId = $entity->getRelated()->getId();
        }

        return $noteId;
    }

    private function loadNoteFields(Note $note, Notification $notification): void
    {
        $parentId = $note->getParentId();
        $parentType = $note->getParentType();

        if ($parentId && $parentType) {
            if ($notification->getType() !== Notification::TYPE_USER_REACTION) {
                $parent = $this->entityManager->getEntityById($parentType, $parentId);

                if ($parent) {
                    $note->set('parentName', $parent->get(Field::NAME));
                }
            }
        } else if (!$note->isGlobal()) {
            $targetType = $note->getTargetType();

            if (!$targetType || $targetType === Note::TARGET_USERS) {
                $note->loadLinkMultipleField('users');
            }

            if ($targetType !== Note::TARGET_USERS) {
                if (!$targetType || $targetType === Note::TARGET_TEAMS) {
                    $note->loadLinkMultipleField(Field::TEAMS);
                } else if ($targetType === Note::TARGET_PORTALS) {
                    $note->loadLinkMultipleField('portals');
                }
            }
        }

        $relatedId = $note->getRelatedId();
        $relatedType = $note->getRelatedType();

        if ($relatedId && $relatedType && $notification->getType() !== Notification::TYPE_USER_REACTION) {
            $related = $this->entityManager->getEntityById($relatedType, $relatedId);

            if ($related) {
                $note->set('relatedName', $related->get(Field::NAME));
            }
        }

        if ($notification->getType() !== Notification::TYPE_USER_REACTION) {
            $note->loadLinkMultipleField('attachments');
        }
    }

    private function getActionIdWhere(string $userId): WhereItem
    {
        return Cond::or(
            Expr::isNull(Expr::column('actionId')),
            Cond::and(
                Expr::isNotNull(Expr::column('actionId')),
                Cond::not(
                    Cond::exists(
                        SelectBuilder::create()
                            ->from(Notification::ENTITY_TYPE, 'sub')
                            ->select('id')
                            ->where(
                                Cond::equal(
                                    Expr::column('sub.actionId'),
                                    Expr::column('notification.actionId')
                                )
                            )
                            ->where(
                                Cond::less(
                                    Expr::column('sub.number'),
                                    Expr::column('notification.number')
                                )
                            )
                            ->where([Notification::ATTR_USER_ID => $userId])
                            ->build()
                    )
                )
            )
        );
    }

    /**
     * @param EntityCollection<Notification> $collection
     * @return array<string, int>
     */
    private function getGroupedCountMap(EntityCollection $collection, string $userId): array
    {
        if (!$this->isGroupingEnabled()) {
            return [];
        }

        $groupedCountMap = [];

        $actionIds = [];

        foreach ($collection as $note) {
            if ($note->getActionId()) {
                $actionIds[] = $note->getActionId();
            }
        }

        $countsQuery = SelectBuilder::create()
            ->from(Notification::ENTITY_TYPE)
            ->select(Expr::count(Expr::column(Attribute::ID)), 'count')
            ->select(Expr::column(Notification::ATTR_ACTION_ID))
            ->where([
                Notification::ATTR_ACTION_ID => $actionIds,
                Notification::ATTR_USER_ID => $userId,
            ])
            ->group(Expr::column(Notification::ATTR_ACTION_ID))
            ->build();

        $rows = $this->entityManager->getQueryExecutor()->execute($countsQuery)->fetchAll();

        foreach ($rows as $row) {
            $actionId = $row[Notification::ATTR_ACTION_ID] ?? null;

            if (!is_string($actionId)) {
                continue;
            }

            $groupedCountMap[$actionId] = $row['count'] ?? 0;
        }

        return $groupedCountMap;
    }

    private function isGroupingEnabled(): bool
    {
        return true;
    }
}
