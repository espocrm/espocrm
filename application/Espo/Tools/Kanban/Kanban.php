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
    Exceptions\Error,
    Utils\Metadata,
    Select\SelectBuilderFactory,
    Select\SearchParams,
    FieldProcessing\ListLoadProcessor,
    FieldProcessing\Loader\Params as FieldLoaderParams,
    Record\ServiceContainer as RecordServiceContainer,
};

use Espo\{
    ORM\EntityManager,
};

class Kanban
{
    private const DEFAULT_MAX_ORDER_NUMBER = 50;

    private const MAX_GROUP_LENGTH = 100;

    protected $entityType;

    protected $countDisabled = false;

    protected $orderDisabled = false;

    /**
     * @var ?SearchParams
     */
    protected $searchParams = null;

    protected $userId = null;

    protected $maxOrderNumber = self::DEFAULT_MAX_ORDER_NUMBER;

    private $metadata;

    private $selectBuilderFactory;

    private $entityManager;

    private $listLoadProcessor;

    private $recordServiceContainer;

    public function __construct(
        Metadata $metadata,
        SelectBuilderFactory $selectBuilderFactory,
        EntityManager $entityManager,
        ListLoadProcessor $listLoadProcessor,
        RecordServiceContainer $recordServiceContainer
    ) {
        $this->metadata = $metadata;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->entityManager = $entityManager;
        $this->listLoadProcessor = $listLoadProcessor;
        $this->recordServiceContainer = $recordServiceContainer;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function setSearchParams(SearchParams $searchParams): self
    {
        $this->searchParams = $searchParams;

        return $this;
    }

    public function setCountDisabled(bool $countDisabled): self
    {
        $this->countDisabled = $countDisabled;

        return $this;
    }

    public function setOrderDisabled(bool $orderDisabled): self
    {
        $this->orderDisabled = $orderDisabled;

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setMaxOrderNumber(?int $maxOrderNumber): self
    {
        if ($maxOrderNumber === null) {
            $this->maxOrderNumber = self::DEFAULT_MAX_ORDER_NUMBER;

            return $this;
        }

        $this->maxOrderNumber = $maxOrderNumber;

        return $this;
    }

    /**
     * Get kanban record data.
     */
    public function getResult(): Result
    {
        if (!$this->entityType) {
            throw new Error("Entity type is not specified.");
        }

        if (!$this->searchParams) {
            throw new Error("No search params.");
        }

        $searchParams = $this->searchParams;

        $recordService = $this->recordServiceContainer->get($this->entityType);

        $maxSize = $searchParams->getMaxSize();

        if ($this->countDisabled && $maxSize) {
            $searchParams = $searchParams->withMaxSize($maxSize + 1);
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($this->entityType)
            ->withStrictAccessControl()
            ->withSearchParams($searchParams)
            ->build();

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create($this->entityType);

        $statusField = $this->getStatusField();
        $statusList = $this->getStatusList();
        $statusIgnoreList = $this->getStatusIgnoreList();

        $additionalData = (object) [
            'groupList' => [],
        ];

        $repository = $this->entityManager->getRDBRepository($this->entityType);

        foreach ($statusList as $status) {
            if (in_array($status, $statusIgnoreList)) {
                continue;
            }

            if (!$status) {
                continue;
            }

            $itemSelectBuilder = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->clone($query);

            $itemSelectBuilder->where([
                $statusField => $status,
            ]);

            $groupData = (object) [
                'name' => $status,
            ];

            $itemQuery = $itemSelectBuilder->build();

            $newOrder = $itemQuery->getOrder();

            array_unshift($newOrder, [
                'COALESCE:(kanbanOrder.order, ' . strval($this->maxOrderNumber + 1) . ')',
                'ASC',
            ]);

            if ($this->userId && !$this->orderDisabled) {
                $group = mb_substr($status, 0, self::MAX_GROUP_LENGTH);

                $itemQuery = $this->entityManager
                    ->getQueryBuilder()
                    ->select()
                    ->clone($itemQuery)
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
                    )
                    ->build();
            }

            $collectionSub = $repository
                ->clone($itemQuery)
                ->find();

            if (!$this->countDisabled) {
                $totalSub = $repository->clone($itemQuery)->count();
            }
            else {
                if ($maxSize && count($collectionSub) > $maxSize) {
                    $totalSub = -1;

                    unset($collectionSub[count($collectionSub) - 1]);
                }
                else {
                    $totalSub = -2;
                }
            }

            $loadProcessorParams = FieldLoaderParams
                ::create()
                ->withSelect($searchParams->getSelect());

            foreach ($collectionSub as $e) {
                $this->listLoadProcessor->process($e, $loadProcessorParams);

                $recordService->prepareEntityForOutput($e);

                $collection[] = $e;
            }

            $groupData->total = $totalSub;

            $groupData->list = $collectionSub->getValueMapList();

            $additionalData->groupList[] = $groupData;
        }

        if (!$this->countDisabled) {
            $total = $repository->clone($query)->count();
        }
        else {
            if ($maxSize && count($collection) > $maxSize) {
                $total = -1;

                unset($collection[count($collection) - 1]);
            }
            else {
                $total = -2;
            }
        }

        return new Result($collection, $total, $additionalData);
    }

    protected function getStatusField(): string
    {
        $statusField = $this->metadata->get(['scopes', $this->entityType, 'statusField']);

        if (!$statusField) {
            throw new Error("No status field for entity type '{$this->entityType}'.");
        }

        return $statusField;
    }

    protected function getStatusList(): array
    {
        $statusField = $this->getStatusField();

        $statusList = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $statusField, 'options']);

        if (empty($statusList)) {
            throw new Error("No options for status field for entity type '{$this->entityType}'.");
        }

        return $statusList;
    }

    protected function getStatusIgnoreList(): array
    {
        return $this->metadata->get(['scopes', $this->entityType, 'kanbanStatusIgnoreList'], []);
    }
}
