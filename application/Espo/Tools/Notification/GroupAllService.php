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
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\UpdateBuilder;

class GroupAllService
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private User $user,
        private RecordService $recordService,
        private SelectBuilderFactory $selectBuilderFactory,
    ) {}

    /**
     * @return RecordCollection<Notification>
     * @throws BadRequest
     * @throws Forbidden
     */
    public function get(string $type, string $groupId, SearchParams $searchParams): RecordCollection
    {
        $searchParams = $this->amendMaxSize($type, $groupId, $searchParams);

        $recordCollection = $this->getInternal($type, $groupId, $searchParams);

        $recordCollection = $this->prepareRecordCollection($recordCollection);

        $this->markAsRead($recordCollection);

        return $recordCollection;
    }

    /**
     * @return RecordCollection<Notification>
     * @throws BadRequest
     * @throws Forbidden
     */
    private function getInternal(string $type, string $groupId, SearchParams $searchParams): RecordCollection
    {
        $collection = null;

        if ($type == Notification::GROUP_TYPE_NOTE) {
            $collection = $this->getNote($groupId, $searchParams);
        } else if ($type == Notification::GROUP_TYPE_EMAIL_RECEIVED) {
            $collection = $this->getEmailReceived($searchParams);
        }

        if (!$collection) {
            throw new BadRequest("Bad group.");
        }

        return $collection;
    }

    /**
     * @return RecordCollection<Notification>
     * @throws BadRequest
     * @throws Forbidden
     */
    private function getNote(string $groupId, SearchParams $searchParams): RecordCollection
    {
        if (substr_count($groupId, '_') < 2) {
            throw new BadRequest("Bad ID.");
        }

        [, $entityType, $id] = explode('_', $groupId, 3);

        if (!$this->acl->checkScope($entityType)) {
            /** @var Collection<Notification> $collection */
            $collection = $this->entityManager->getCollectionFactory()->create(Notification::ENTITY_TYPE);

            return RecordCollection::create($collection, 0);
        }

        $builder = $this->prepareBuilder($searchParams);

        $query = $builder
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_NOTE,
                Notification::ATTR_RELATED_PARENT_TYPE => $entityType,
                Notification::ATTR_RELATED_PARENT_ID => $id,
            ])
            ->build();

        [$collection, $total] = $this->runQuery($query);

        return RecordCollection::create($collection, $total);
    }

    /**
     * @return RecordCollection<Notification>
     * @throws BadRequest
     * @throws Forbidden
     */
    private function getEmailReceived(SearchParams $searchParams): RecordCollection
    {
        $builder = $this->prepareBuilder($searchParams);

        $query = $builder
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->build();

        [$collection, $total] = $this->runQuery($query);

        return RecordCollection::create($collection, $total);
    }

    /**
     * @return array{0: Collection<Notification>, 1: int}
     */
    private function runQuery(Select $query): array
    {
        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->clone($query)
            ->find();

        $total = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->clone($query)
            ->count();

        return [$collection, $total];
    }

    /**
     * @throws BadRequest
     */
    public function markRead(string $groupId): void
    {
        if (substr_count($groupId, '_') < 1) {
            throw new BadRequest("Bad ID.");
        }

        [$type,] = explode('_', $groupId, 2);

        if ($type == Notification::GROUP_TYPE_NOTE) {
            $this->markReadNote($groupId);

            return;
        }

        if ($type == Notification::GROUP_TYPE_EMAIL_RECEIVED) {
            $this->markReadEmailReceived();

            return;
        }

        throw new BadRequest("Bad group type.");
    }

    /**
     * @param string[] $ids
     */
    private function markAsReadByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $query = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Notification::ENTITY_TYPE)
            ->set([Notification::ATTR_READ => true])
            ->where([Notification::ATTR_USER_ID => $this->user->getId()])
            ->where(
                Cond::in(Expr::column(Attribute::ID), $ids)
            )
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    /**
     * @param RecordCollection<Notification> $collection
     */
    private function markAsRead(RecordCollection $collection): void
    {
        $ids = [];

        foreach ($collection->getCollection() as $entity) {
            $ids[] = $entity->getId();
        }

        $this->markAsReadByIds($ids);
    }

    /**
     * @return SelectBuilder
     * @throws BadRequest
     * @throws Forbidden
     */
    private function prepareBuilder(SearchParams $searchParams): SelectBuilder
    {
        return $this->selectBuilderFactory
            ->create()
            ->from(Notification::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->withComplexExpressionsForbidden()
            ->buildQueryBuilder()
            ->where([
                Notification::ATTR_USER_ID => $this->user->getId(),
            ]);
    }

    /**
     * @throws BadRequest
     */
    public function remove(string $groupId): void
    {
        if (substr_count($groupId, '_') < 1) {
            throw new BadRequest("Bad ID.");
        }

        [$type,] = explode('_', $groupId, 2);

        if ($type == Notification::GROUP_TYPE_NOTE) {
            $this->removeNote($groupId);

            return;
        }

        if ($type == Notification::GROUP_TYPE_EMAIL_RECEIVED) {
            $this->removeEmailReceived();

            return;
        }

        throw new BadRequest("Bad group type.");
    }

    /**
     * @throws BadRequest
     */
    private function removeNote(string $groupId): void
    {
        if (substr_count($groupId, '_') < 2) {
            throw new BadRequest("Bad ID.");
        }

        [, $entityType, $id] = explode('_', $groupId, 3);

        $query = DeleteBuilder::create()
            ->from(Notification::ENTITY_TYPE)
            ->where([
                Notification::ATTR_USER_ID => $this->user->getId(),
            ])
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_NOTE,
                Notification::ATTR_RELATED_PARENT_TYPE => $entityType,
                Notification::ATTR_RELATED_PARENT_ID => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function removeEmailReceived(): void
    {
        $query = DeleteBuilder::create()
            ->from(Notification::ENTITY_TYPE)
            ->where([
                Notification::ATTR_USER_ID => $this->user->getId(),
            ])
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    /**
     * @throws BadRequest
     */
    private function markReadNote(string $groupId): void
    {
        if (substr_count($groupId, '_') < 2) {
            throw new BadRequest("Bad ID.");
        }

        [, $entityType, $id] = explode('_', $groupId, 3);

        $query = UpdateBuilder::create()
            ->in(Notification::ENTITY_TYPE)
            ->where([
                Notification::ATTR_USER_ID => $this->user->getId(),
            ])
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_NOTE,
                Notification::ATTR_RELATED_PARENT_TYPE => $entityType,
                Notification::ATTR_RELATED_PARENT_ID => $id,
                Notification::ATTR_READ => false,
            ])
            ->set([
                Notification::ATTR_READ => true,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function markReadEmailReceived(): void
    {
        $query = UpdateBuilder::create()
            ->in(Notification::ENTITY_TYPE)
            ->where([
                Notification::ATTR_USER_ID => $this->user->getId(),
            ])
            ->where([
                Notification::FIELD_TYPE => Notification::TYPE_EMAIL_RECEIVED,
                Notification::ATTR_READ => false,
            ])
            ->set([
                Notification::ATTR_READ => true,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function hasBeforeNumber(SearchParams $searchParams): bool
    {
        foreach ($searchParams->getWhere()?->getItemList() ?? [] as $item) {
            if ($item->getAttribute() === Notification::ATTR_NUMBER) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function amendMaxSize(string $type, string $groupId, SearchParams $searchParams): SearchParams
    {
        if ($this->hasBeforeNumber($searchParams)) {
            return $searchParams;
        }

        $collection = $this->getInternal($type, $groupId, $searchParams);

        $firstReadIndex = -1;
        $hasNotRead = false;
        $hasNotReadAfterRead = false;

        foreach ($collection->getCollection() as $i => $entity) {
            if ($entity->isRead() && $firstReadIndex === -1) {
                $firstReadIndex = $i;
            }

            if (!$entity->isRead()) {
                $hasNotRead = true;
            }

            if ($firstReadIndex !== -1 && !$entity->isRead()) {
                $hasNotReadAfterRead = true;
            }
        }

        if ($firstReadIndex !== -1 && $hasNotRead && !$hasNotReadAfterRead) {
            $searchParams = $searchParams->withMaxSize($firstReadIndex);
        }

        return $searchParams;
    }

    /**
     * @param RecordCollection<Notification> $recordCollection
     * @return RecordCollection<Notification>
     */
    private function prepareRecordCollection(RecordCollection $recordCollection): RecordCollection
    {
        $collection = $recordCollection->getCollection();
        $collection = $this->recordService->prepareCollection($collection, $this->user);

        return new RecordCollection($collection, $recordCollection->getTotal());
    }
}
