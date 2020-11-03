<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
    Exceptions\Error,
    Utils\Metadata,
    Select\SelectManagerFactory,
    ORM\EntityManager,
};

use Espo\{
    Services\Record as RecordService,
    ORM\QueryParams\Select as SelectQuery,
};

class Kanban
{
    protected $entityType;

    protected $countDisabled = false;

    protected $orderDisabled = false;

    protected $searchParams = [];

    protected $maxSelectTextAttributeLength = null;

    protected $userId = null;

    protected $maxOrderNumber = 50;

    const MAX_GROUP_LENGTH = 100;

    protected $metadata;
    protected $selectManagerFactory;
    protected $entityManager;

    public function __construct(
        Metadata $metadata,
        SelectManagerFactory $selectManagerFactory,
        EntityManager $entityManager
    ) {
        $this->metadata = $metadata;
        $this->selectManagerFactory = $selectManagerFactory;
        $this->entityManager = $entityManager;
    }

    public function setRecordService(RecordService $recordService) : self
    {
        $this->recordService = $recordService;

        return $this;
    }

    public function setEntityType(string $entityType) : self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function setSearchParams(array $searchParams) : self
    {
        $this->searchParams = $searchParams;

        return $this;
    }

    public function setCountDisabled(bool $countDisabled) : self
    {
        $this->countDisabled = $countDisabled;

        return $this;
    }

    public function setOrderDisabled(bool $orderDisabled) : self
    {
        $this->orderDisabled = $orderDisabled;

        return $this;
    }

    public function setUserId(string $userId) : self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setMaxOrderNumber(int $maxOrderNumber) : self
    {
        $this->maxOrderNumber = $maxOrderNumber;

        return $this;
    }

    public function setMaxSelectTextAttributeLength(?int $maxSelectTextAttributeLength) : self
    {
        $this->maxSelectTextAttributeLength = $maxSelectTextAttributeLength;

        return $this;
    }

    /**
     * Get kanban record data.
     */
    public function getResult() : Result
    {
        $params = $this->searchParams;

        if (!$this->entityType) {
            throw new Error("Entity type is not specified.");
        }

        if (!$this->recordService) {
            throw new Error("Record service is not set.");
        }

        $maxSize = 0;

        if ($this->countDisabled) {
           if (!empty($params['maxSize'])) {
               $maxSize = $params['maxSize'];

               $params['maxSize'] = $params['maxSize'] + 1;
           }
        }

        $selectManager = $this->selectManagerFactory->create($this->entityType);

        $selectParams = $selectManager->getSelectParams($params, true, true, true);

        if ($this->maxSelectTextAttributeLength) {
            $selectParams['maxTextColumnsLength'] = $this->maxSelectTextAttributeLength;
        }

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create($this->entityType);

        $statusField = $this->getStatusField();
        $statusList = $this->getStatusList();
        $statusIgnoreList = $this->getStatusIgnoreList();

        $additionalData = (object) [
            'groupList' => [],
        ];

        $repository = $this->entityManager->getRepository($this->entityType);

        foreach ($statusList as $status) {
            if (in_array($status, $statusIgnoreList)) {
                continue;
            }

            if (!$status) {
                continue;
            }

            $selectParamsSub = $selectParams;

            $selectParamsSub['whereClause'][] = [
                $statusField => $status,
            ];

            $o = (object) [
                'name' => $status,
            ];

            $query = SelectQuery::fromRaw($selectParamsSub);

            $newOrder = $selectParamsSub['orderBy'] ?? [];

            array_unshift($newOrder, [
                'COALESCE:(kanbanOrder.order, ' . strval($this->maxOrderNumber + 1) . ')',
                'ASC',
            ]);

            if ($this->userId && !$this->orderDisabled) {
                $group = mb_substr($status, 0, self::MAX_GROUP_LENGTH);

                $builder = $this->entityManager
                    ->getQueryBuilder()
                    ->select()
                    ->clone($query)
                    ->order($newOrder)
                    ->leftJoin(
                        'KanbanOrder',
                        'kanbanOrder',
                        [
                            'kanbanOrder.entityType' => $this->entityType,
                            'kanbanOrder.entityId:' => 'id',
                            'kanbanOrder.group' => $group,
                            'kanbanOrder.userId' => $this->userId,
                        ]
                    );

                $query = $builder->build();
            }

            $collectionSub = $repository
                ->clone($query)
                ->find();

            if (!$this->countDisabled) {
                $totalSub = $repository->count($selectParamsSub);
            } else {
                if ($maxSize && count($collectionSub) > $maxSize) {
                    $totalSub = -1;

                    unset($collectionSub[count($collectionSub) - 1]);
                } else {
                    $totalSub = -2;
                }
            }

            foreach ($collectionSub as $e) {
                $this->recordService->loadAdditionalFieldsForList($e);

                if (!empty($params['loadAdditionalFields'])) {
                    $this->recordService->loadAdditionalFields($e);
                }

                if (!empty($params['select'])) {
                    $this->recordService->loadLinkMultipleFieldsForList($e, $params['select']);
                }

                $this->recordService->prepareEntityForOutput($e);

                $collection[] = $e;
            }

            $o->total = $totalSub;

            $o->list = $collectionSub->getValueMapList();

            $additionalData->groupList[] = $o;
        }

        if (!$this->countDisabled) {
            $total = $repository->count($selectParams);
        } else {
            if ($maxSize && count($collection) > $maxSize) {
                $total = -1;

                unset($collection[count($collection) - 1]);
            } else {
                $total = -2;
            }
        }

        return new Result($collection, $total, $additionalData);
    }

    protected function getStatusField() : string
    {
        $statusField = $this->metadata->get(['scopes', $this->entityType, 'statusField']);

        if (!$statusField) {
            throw new Error("No status field for entity type '{$this->entityType}'.");
        }

        return $statusField;
    }

    protected function getStatusList() : array
    {
        $statusField = $this->getStatusField();

        $statusList = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $statusField, 'options']);

        if (empty($statusList)) {
            throw new Error("No options for status field for entity type '{$this->entityType}'.");
        }

        return $statusList;
    }

    protected function getStatusIgnoreList() : array
    {
        return $this->metadata->get(['scopes', $this->entityType, 'kanbanStatusIgnoreList'], []);
    }
}
