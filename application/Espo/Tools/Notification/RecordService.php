<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
use Espo\Core\Utils\Config;
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
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Union;
use Espo\ORM\Query\UnionBuilder;
use Espo\ORM\Repository\RDBSelectBuilder;
use Espo\Tools\Stream\NoteAccessControl;
use Espo\Tools\User\PreferencesProvider;
use PDO;
use UnexpectedValueException;

class RecordService
{
    private const string COLUMN_GROUP_TYPE = 'groupType';
    private const string COLUMN_GROUP_UNREAD_COUNT = 'groupedUnreadCount';

    /** @var string[] */
    private array $noGroupAttributes = [
        Attribute::DELETED,
        Field::CREATED_BY . 'Id',
        Notification::ATTR_ACTION_ID,
        Notification::FIELD_DATA,
        Notification::FIELD_MESSAGE,
        Notification::FIELD_TYPE,
        Notification::ATTR_RELATED_ID,
        Notification::ATTR_RELATED_TYPE,
    ];

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata,
        private NoteAccessControl $noteAccessControl,
        private SelectBuilderFactory $selectBuilderFactory,
        private Config $config,
        private PreferencesProvider $preferencesProvider,
    ) {}

    /**
     * Get notifications for a user.
     *
     * @return RecordCollection<Notification>
     * @throws Error
     * @throws BadRequest
     * @throws Forbidden
     *
     * @internal
     */
    public function get(User $user, SearchParams $searchParams, ?string $beforeNumber = null): RecordCollection
    {
        $queryBuilder = $this->isGroupingEnabled($user) ?
            $this->prepareGroupingQueryBuilder($user, $searchParams, beforeNumber: $beforeNumber) :
            $this->prepareQueryBuilder($user, $searchParams, beforeNumber: $beforeNumber);

        $offset = $searchParams->getOffset();
        $limit = $searchParams->getMaxSize();

        if ($limit) {
            $queryBuilder->limit($offset, $limit + 1);
        }

        $query = $queryBuilder->build();

        if ($query instanceof Union) {
            $collection = $this->fetchAndPrepareCollectionFromUnion($query);
        } else {
            $collection = $this->entityManager
                ->getRDBRepositoryByClass(Notification::class)
                ->clone($query)
                ->find();
        }

        if (!$collection instanceof EntityCollection) {
            throw new Error("Collection is not instance of EntityCollection.");
        }

        $collection = $this->prepareCollection($collection, $user);

        $groupedCountMap = $this->getActionGroupedCountMap($collection, $user->getId());

        $ids = [];
        $actionIds = [];

        foreach ($collection as $i => $entity) {
            if ($i === $limit) {
                break;
            }

            if (!$entity->getGroupType()) {
                $ids[] = $entity->getId();
            }

            $groupedCount = null;

            if ($this->isGroupingEnabled($user) && $entity->getGroupType()) {
                $groupedCount = -1;

                $entity->loadParentNameField(Notification::FIELD_RELATED_PARENT);
            }

            if ($entity->getActionId() && $this->isActionGroupingEnabled() && !$this->isGroupingEnabled($user)) {
                $actionIds[] = $entity->getActionId();

                $groupedCount = $groupedCountMap[$entity->getActionId()] ?? 0;
            }

            $entity->setGroupedCount($groupedCount);
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

        $this->prepareSetFields($entity);

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

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getNotReadCount(User $user): int
    {
        $searchParams = SearchParams::create();

        $queryBuilder = $this->isGroupingEnabled($user) ?
            $this->prepareGroupingQueryBuilder($user, $searchParams, true) :
            $this->prepareQueryBuilder($user, $searchParams, true);

        $countQuery = $this->entityManager->getQueryBuilder()
            ->select()
            ->fromQuery($queryBuilder->build(), 'q')
            ->select('COUNT:(q.id)', 'c')
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($countQuery);

        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $count = $row['c'] ?? null;

        if (!is_int($count)) {
            throw new UnexpectedValueException();
        }

        return $count;
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
    private function getActionGroupedCountMap(EntityCollection $collection, string $userId): array
    {
        if (!$this->isActionGroupingEnabled()) {
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

    private function isActionGroupingEnabled(): bool
    {
        // @todo Param in preferences?
        return (bool) ($this->config->get('notificationGrouping') ?? true);
    }

    private function prepareSetFields(Notification $entity): void
    {
        $relatedName = $entity->getData()->relatedName ?? null;
        $createdByName = $entity->getData()->createdByName ?? null;

        if ($entity->getRelated() && $relatedName !== null) {
            $entity->set('relatedName', $relatedName);
        }

        if ($entity->getCreatedBy() && $createdByName !== null) {
            $entity->set('createdByName', $createdByName);
        }
    }

    private function isGroupingEnabled(User $user): bool
    {
        return $this->preferencesProvider->tryGet($user->getId())?->get('notificationGrouping') ?? false;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareGroupingQueryBuilder(
        User $user,
        SearchParams $searchParams,
        bool $notRead = false,
        ?string $beforeNumber = null,
    ): UnionBuilder {

        $noteGroupBuilder = $this->prepareNoteGroupBuilder(
            searchParams: $searchParams,
            user: $user,
            notRead: $notRead,
            beforeNumber: $beforeNumber,
        );

        $emailGroupBuilder = $this->prepareEmailGroupBuilder(
            searchParams: $searchParams,
            user: $user,
            notRead: $notRead,
            beforeNumber: $beforeNumber,
        );

        $restBuilder = $this->prepareRestBuilder(
            searchParams: $searchParams,
            user: $user,
            notRead: $notRead,
            beforeNumber: $beforeNumber,
        );

        return UnionBuilder::create()
            ->query($noteGroupBuilder->build())
            ->query($emailGroupBuilder->build())
            ->query($restBuilder->build())
            ->order(Notification::ATTR_NUMBER, Order::DESC);
    }

    /**
     * @param RDBSelectBuilder<Notification>|SelectBuilder $builder
     */
    private function applyRelatedAccess(RDBSelectBuilder|SelectBuilder $builder): void
    {
        $ignoreScopeList = $this->getIgnoreScopeList();

        if ($ignoreScopeList === []) {
            return;
        }

        $builder->where([
            'OR' => [
                Notification::ATTR_RELATED_PARENT_TYPE => null,
                Notification::ATTR_RELATED_PARENT_TYPE . '!=' => $ignoreScopeList,
            ],
        ]);
    }

    /**
     * @return EntityCollection<Notification>
     */
    private function fetchAndPrepareCollectionFromUnion(Union $query): EntityCollection
    {
        $sth = $this->entityManager->getQueryExecutor()->execute($query);

        /** @var EntityCollection<Notification> $collection */
        $collection = $this->entityManager->getCollectionFactory()->create(Notification::ENTITY_TYPE);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $entity = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

            $entity->setMultiple($row);
            $entity->setAsFetched();

            $collection[] = $entity;
        }

        return $collection;
    }

    /**
     * @return Selection[]
     */
    private function getNullNoGroupSelections(): array
    {
        return array_map(function ($attribute) {
            return Selection::create(Expr::value(null), $attribute);
        }, $this->noGroupAttributes);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareQueryBuilder(
        User $user,
        SearchParams $searchParams,
        bool $notRead = false,
        ?string $beforeNumber = null,
    ): SelectBuilder {

        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Notification::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->where([Notification::ATTR_USER_ID => $user->getId()])
            ->order(Notification::ATTR_NUMBER, SearchParams::ORDER_DESC);

        if ($notRead) {
            $builder->where([Notification::ATTR_READ => false]);
        }

        if ($beforeNumber) {
            $builder->where([Notification::ATTR_NUMBER . '<' => $beforeNumber]);
        }

        $this->applyRelatedAccess($builder);

        if ($this->isActionGroupingEnabled()) {
            $builder->where($this->getActionIdWhere($user->getId()));
        }

        return $builder;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareNoteGroupBuilder(
        SearchParams $searchParams,
        User $user,
        bool $notRead,
        ?string $beforeNumber,
    ): SelectBuilder {

        if ($searchParams->getMaxSize() !== null) {
            $searchParams = $searchParams->withMaxSize($searchParams->getMaxSize() + 1);
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Notification::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->select([
                Selection::create(
                    Expr::value(Notification::GROUP_TYPE_RECORD),
                    self::COLUMN_GROUP_TYPE
                ),
                Selection::create(
                    Expr::concat(
                        Expr::value(Notification::GROUP_TYPE_RECORD),
                        Expr::value('_'),
                        Expr::column(Notification::ATTR_RELATED_PARENT_TYPE),
                        Expr::value('_'),
                        Expr::column(Notification::ATTR_RELATED_PARENT_ID),
                    ),
                    Attribute::ID
                ),
                Selection::create(
                    $this->getMaxNumberExpr(),
                    Notification::ATTR_NUMBER
                ),
                Notification::ATTR_RELATED_PARENT_ID,
                Notification::ATTR_RELATED_PARENT_TYPE,
                Selection::create(
                    Expr::switch(
                        Expr::greater(Expr::sum(Expr::not(Expr::column(Notification::ATTR_READ))), 0),
                        Expr::value(false),
                        Expr::value(true),
                    ),
                    Notification::ATTR_READ
                ),
                Selection::create(
                    Expr::sum(
                        Expr::switch(
                            Expr::column(Notification::ATTR_READ),
                            Expr::value(0),
                            Expr::value(1),
                        ),
                    ),
                    self::COLUMN_GROUP_UNREAD_COUNT
                ),
                Selection::create(
                    Expr::max(Expr::column(Field::CREATED_AT)),
                    Field::CREATED_AT,
                ),
                Selection::create(
                    Expr::switch(
                        Expr::greater(
                            Expr::sum(
                                Expr::and(
                                    Expr::not(Expr::column(Notification::ATTR_READ)),
                                    Expr::column(Notification::FIELD_IS_FEATURED),
                                )
                            ),
                            0
                        ),
                        true,
                        false,
                    ),
                    Notification::FIELD_IS_FEATURED
                ),
                ...$this->getNullNoGroupSelections(),
            ])
            ->where([
                Notification::ATTR_RELATED_PARENT_ID . '!=' => null,
                Notification::FIELD_TYPE => $this->getRecordGroupNoteTypes(),
            ])
            ->where([Notification::ATTR_USER_ID => $user->getId()])
            ->group(Notification::ATTR_RELATED_PARENT_ID)
            ->group(Notification::ATTR_RELATED_PARENT_TYPE)
            ->order([])
            ->order($this->getMaxNumberExpr(), Order::DESC);

        if ($beforeNumber) {
            $builder->having(
                Cond::less(
                    $this->getMaxNumberExpr(),
                    Expr::value($beforeNumber)
                )
            );
        }

        $this->applyRelatedAccess($builder);

        if ($notRead) {
            $builder->where([Notification::ATTR_READ => false]);
        }

        return $builder;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareEmailGroupBuilder(
        SearchParams $searchParams,
        User $user,
        bool $notRead,
        ?string $beforeNumber,
    ): SelectBuilder {

        if ($searchParams->getMaxSize() !== null) {
            $searchParams = $searchParams->withMaxSize($searchParams->getMaxSize() + 1);
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Notification::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->select([
                Selection::create(
                    Expr::value(Notification::GROUP_TYPE_EMAIL_RECEIVED),
                    self::COLUMN_GROUP_TYPE
                ),
                Selection::create(
                    Expr::concat(
                        Expr::value(Notification::GROUP_TYPE_EMAIL_RECEIVED),
                        Expr::value('_'),
                    ),
                    Attribute::ID
                ),
                Selection::create(
                    $this->getMaxNumberExpr(),
                    Notification::ATTR_NUMBER
                ),
                Selection::create(
                    Expr::value(null),
                    Notification::ATTR_RELATED_PARENT_ID,
                ),
                Selection::create(
                    Expr::value(null),
                    Notification::ATTR_RELATED_PARENT_TYPE,
                ),
                Selection::create(
                    Expr::switch(
                        Expr::greater(Expr::sum(Expr::not(Expr::column(Notification::ATTR_READ))), 0),
                        Expr::value(false),
                        Expr::value(true),
                    ),
                    Notification::ATTR_READ
                ),
                Selection::create(
                    Expr::sum(
                        Expr::switch(
                            Expr::column(Notification::ATTR_READ),
                            Expr::value(0),
                            Expr::value(1),
                        ),
                    ),
                    self::COLUMN_GROUP_UNREAD_COUNT
                ),
                Selection::create(
                    Expr::max(Expr::column(Field::CREATED_AT)),
                    Field::CREATED_AT,
                ),
                Selection::create(
                    Expr::value(null),
                    Notification::FIELD_IS_FEATURED,
                ),
                ...$this->getNullNoGroupSelections(),
            ])
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->where([Notification::ATTR_USER_ID => $user->getId()])
            ->group(Notification::FIELD_TYPE)
            ->order([])
            ->order($this->getMaxNumberExpr(), Order::DESC);

        if ($beforeNumber) {
            $builder->having(
                Cond::less(
                    $this->getMaxNumberExpr(),
                    Expr::value($beforeNumber)
                )
            );
        }

        if ($notRead) {
            $builder->where([Notification::ATTR_READ => false]);
        }

        return $builder;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareRestBuilder(
        SearchParams $searchParams,
        User $user,
        bool $notRead,
        ?string $beforeNumber,
    ): SelectBuilder {

        if ($searchParams->getMaxSize() !== null) {
            $searchParams = $searchParams->withMaxSize($searchParams->getMaxSize() + 1);
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Notification::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->select([
                Selection::create(
                    Expr::value(null),
                    self::COLUMN_GROUP_TYPE,
                ),
                Attribute::ID,
                Notification::ATTR_NUMBER,
                Notification::ATTR_RELATED_PARENT_ID,
                Notification::ATTR_RELATED_PARENT_TYPE,
                Notification::ATTR_READ,
                Selection::create(
                    Expr::switch(
                        Expr::column(Notification::ATTR_READ),
                        Expr::value(0),
                        Expr::value(1),
                    ),
                    self::COLUMN_GROUP_UNREAD_COUNT
                ),
                Field::CREATED_AT,
                Selection::create(
                    Expr::value(null),
                    Notification::FIELD_IS_FEATURED,
                ),
                ...$this->noGroupAttributes,
            ])
            ->where([
                [
                    'OR' => [
                        Notification::FIELD_TYPE . '!=' => $this->getRecordGroupNoteTypes(),
                        Notification::ATTR_RELATED_PARENT_ID => null,
                    ],
                ],
                Notification::FIELD_TYPE . '!=' => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->where([Notification::ATTR_USER_ID => $user->getId()])
            ->order([])
            ->order(Notification::ATTR_NUMBER, Order::DESC);

        if ($notRead) {
            $builder->where([Notification::ATTR_READ => false]);
        }

        if ($beforeNumber) {
            $builder->where([Notification::ATTR_NUMBER . '<' => $beforeNumber]);
        }

        return $builder;
    }

    private function getMaxNumberExpr(): Expr
    {
        return Expr::max(Expr::column(Notification::ATTR_NUMBER));
    }

    /**
     * @internal
     * @return string[]
     */
    public function getRecordGroupNoteTypes(): array
    {
        return [
            Notification::TYPE_NOTE,
            Notification::TYPE_USER_REACTION,
            Notification::TYPE_COLLABORATING,
            'EventAttendee',
        ];
    }
}
