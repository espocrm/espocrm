<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Kanban;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Metadata,
    Utils\Util,
};

use LogicException;

class OrdererProcessor
{
    private const MAX_GROUP_LENGTH = 100;

    private const DEFAULT_MAX_NUMBER = 50;

    private $entityType;

    private $group;

    private $userId = null;

    private $maxNumber = self::DEFAULT_MAX_NUMBER;

    private $entityManager;

    private $metadata;

    public function __construct(EntityManager $entityManager, Metadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function setGroup(string $group): self
    {
        $this->group = mb_substr($group, 0, self::MAX_GROUP_LENGTH);

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setMaxNumber(?int $maxNumber): self
    {
        if ($maxNumber === null) {
            $this->maxNumber = self::DEFAULT_MAX_NUMBER;

            return $this;
        }

        $this->maxNumber = $maxNumber;

        return $this;
    }

    public function order(array $ids): void
    {
        $this->validate();

        $count = count($ids);

        if (!$count) {
            return;
        }

        $this->entityManager
            ->getTransactionManager()
            ->start();

        $deleteQuery1 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('KanbanOrder')
            ->where([
                'entityType' => $this->entityType,
                'userId' => $this->userId,
                'entityId' => $ids,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($deleteQuery1);

        $minOrder = null;

        $first = $this->entityManager
            ->getRDBRepository('KanbanOrder')
            ->select(['id', 'order'])
            ->where([
                'entityType' => $this->entityType,
                'userId' => $this->userId,
                'group' => $this->group,
            ])
            ->order('order')
            ->findOne();

        if ($first) {
            $minOrder = $first->get('order');
        }

        if ($minOrder !== null) {
            $offset = $count - $minOrder;

            $updateQuery = $this->entityManager
                ->getQueryBuilder()
                ->update()
                ->in('KanbanOrder')
                ->where([
                    'entityType' => $this->entityType,
                    'group' => $this->group,
                    'userId' => $this->userId,
                ])
                ->set([
                    'order:' => 'ADD:(order, ' . strval($offset) . ')'
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($updateQuery);
        }

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create('KanbanOrder');

        foreach ($ids as $i => $id) {
            $item = $this->entityManager->getEntity('KanbanOrder');

            $item->set([
                'id' => Util::generateId(),
                'entityId' => $id,
                'entityType' => $this->entityType,
                'group' => $this->group,
                'userId' => $this->userId,
                'order' => $i,
            ]);

            $collection[] = $item;
        }

        $this->entityManager->getMapper()->massInsert($collection);

        $deleteQuery2 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('KanbanOrder')
            ->where([
                'entityType' => $this->entityType,
                'group' => $this->group,
                'userId' => $this->userId,
                'order>' => $this->maxNumber,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($deleteQuery2);

        $this->entityManager
            ->getTransactionManager()
            ->commit();
    }

    private function validate(): void
    {
        if (! $this->entityType) {
            throw new LogicException("No entity type.");
        }

        if (! $this->group) {
            throw new LogicException("No group.");
        }

        if (! $this->userId) {
            throw new LogicException("No user ID.");
        }

        if (! $this->metadata->get(['scopes', $this->entityType, 'object'])) {
            throw new LogicException("Not allowed entity type.");
        }

        $orderDisabled = $this->metadata->get(['scopes', $this->entityType, 'kanbanOrderDisabled']);

        if ($orderDisabled) {
            throw new LogicException("Order is disabled.");
        }

        $statusField = $this->metadata->get(['scopes', $this->entityType, 'statusField']);

        if (! $statusField) {
            throw new LogicException("Not status field.");
        }

        $statusList = $this->metadata
            ->get(['entityDefs', $this->entityType, 'fields', $statusField, 'options']) ?? [];

        if (!in_array($this->group, $statusList)) {
            throw new LogicException("Group is not available in status list.");
        }
    }
}
