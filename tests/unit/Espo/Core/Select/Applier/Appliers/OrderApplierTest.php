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

namespace tests\unit\Espo\Core\Select\Applier\Appliers;

use Espo\Core\AclManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Entities\User;
use Espo\ORM\Query\Part\OrderList;
use Espo\ORM\Query\Part\Order;
use Espo\Core\Select\Order\Applier as OrderApplier;
use Espo\Core\Select\Order\Item;
use Espo\Core\Select\Order\ItemConverter;
use Espo\Core\Select\Order\ItemConverterFactory;
use Espo\Core\Select\Order\MetadataProvider;
use Espo\Core\Select\Order\Orderer;
use Espo\Core\Select\Order\OrdererFactory;
use Espo\Core\Select\Order\Params as OrderParams;
use Espo\Core\Select\SearchParams;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use PHPUnit\Framework\TestCase;

class OrderApplierTest extends TestCase
{
    private ?OrdererFactory $ordererFactory = null;

    private $metadataProvider;
    private $itemConverterFactory;
    private $queryBuilder;
    private $params;
    private $entityType;
    private $applier;

    protected function setUp(): void
    {
        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->itemConverterFactory = $this->createMock(ItemConverterFactory::class);
        $this->ordererFactory = $this->createMock(OrdererFactory::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->params = $this->createMock(OrderParams::class);

        $aclManager = $this->createMock(AclManager::class);
        $user = $this->createMock(User::class);

        $this->entityType = 'Test';

        $this->applier = new OrderApplier(
            $this->entityType,
            $this->metadataProvider,
            $this->itemConverterFactory,
            $this->ordererFactory,
            $aclManager,
            $user,
        );
    }

    public function testApplyDefault()
    {
        $order = SearchParams::ORDER_DESC;

        $this->params
            ->expects($this->any())
            ->method('forceDefault')
            ->willReturn(true);

        $this->params
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getDefaultOrderBy')
            ->with($this->entityType)
            ->willReturn('testField');

        $this->initApplyOrderTest('testField', $order, 'varchar');

        $this->applier->apply($this->queryBuilder, $this->params);
    }

    public function testApply1()
    {
        $order = SearchParams::ORDER_DESC;
        $orderBy = 'testField';

        $this->params
            ->expects($this->any())
            ->method('forceDefault')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $this->params
            ->expects($this->any())
            ->method('getOrderBy')
            ->willReturn($orderBy);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getDefaultOrderBy')
            ->with($this->entityType)
            ->willReturn($orderBy);

        $this->initApplyOrderTest($orderBy, $order, 'varchar');

        $this->applier->apply($this->queryBuilder, $this->params);
    }

    public function testApplyWithConverter()
    {
        $order = SearchParams::ORDER_DESC;
        $orderBy = 'testField';

        $this->params
            ->expects($this->any())
            ->method('forceDefault')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $this->params
            ->expects($this->any())
            ->method('getOrderBy')
            ->willReturn($orderBy);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getDefaultOrderBy')
            ->with($this->entityType)
            ->willReturn($orderBy);

        $converterResult = OrderList::create([
            Order::fromString('hello')->withDesc(),
        ]);

        $this->initApplyOrderTest($orderBy, $order, 'varchar', $converterResult);

        $this->applier->apply($this->queryBuilder, $this->params);
    }

    public function testApplyNotExisting()
    {
        $order = SearchParams::ORDER_DESC;
        $orderBy = 'testField';

        $this->params
            ->expects($this->any())
            ->method('forceDefault')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $this->params
            ->expects($this->any())
            ->method('getOrderBy')
            ->willReturn($orderBy);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getDefaultOrderBy')
            ->with($this->entityType)
            ->willReturn($orderBy);

        $this->initApplyOrderTest($orderBy, $order, 'varchar', null, true);

        $this->applier->apply($this->queryBuilder, $this->params);
    }

    public function testApplyWithOrderer()
    {
        $order = SearchParams::ORDER_DESC;
        $orderBy = 'testField';

        $this->params
            ->expects($this->any())
            ->method('forceDefault')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $this->params
            ->expects($this->any())
            ->method('getOrderBy')
            ->willReturn($orderBy);

        $this->ordererFactory
            ->expects($this->once())
            ->method('has')
            ->with($this->entityType, $orderBy)
            ->willReturn(true);

        $orderer = $this->createMock(Orderer::class);

        $this->ordererFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $orderBy)
            ->willReturn($orderer);

        $orderer
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder, Item::create($orderBy, $order));

        $this->applier->apply($this->queryBuilder, $this->params);
    }

    protected function initApplyOrderTest(
        string $orderBy,
        string $order,
        string $fieldType,
        ?OrderList $converterResult = null,
        bool $notExisting = false
    ) {
        $this->metadataProvider
            ->expects($this->any())
            ->method('getFieldType')
            ->with($this->entityType, $orderBy)
            ->willReturn($fieldType);

        $this->itemConverterFactory
            ->expects($this->once())
            ->method('has')
            ->with($this->entityType, $orderBy)
            ->willReturn(
                (bool) $converterResult
            );

            $this->metadataProvider
                ->expects($this->any())
                ->method('hasAttribute')
                ->willReturnMap(
                        [
                            [$this->entityType, $orderBy, !$notExisting],
                            [$this->entityType, 'id', true],
                        ]
                );

        if ($converterResult) {
            $converter = $this->createMock(ItemConverter::class);

            $this->itemConverterFactory
                ->expects($this->once())
                ->method('create')
                ->with($this->entityType, $orderBy)
                ->willReturn($converter);

            $item = Item::create($orderBy, $order);

            $converter
                ->expects($this->once())
                ->method('convert')
                ->with($item)
                ->willReturn($converterResult);
        } else {
            if ($notExisting) {
                $this->expectException(BadRequest::class);

                return;
            }

            $this->metadataProvider
                ->expects($this->once())
                ->method('isAttributeParamUniqueTrue')
                ->with($this->entityType, $orderBy)
                ->willReturn(false);
        }

        $expectedOrderBy = ($converterResult ? $this->orderListToArray($converterResult): null)
            ?? [[$orderBy, $order]];

        $expectedOrderBy[] = ['id', $order];

        $this->queryBuilder
            ->expects($this->once())
            ->method('order')
            ->with($expectedOrderBy);
    }

    private function orderListToArray(OrderList $orderList): array
    {
        $list = [];

        foreach ($orderList as $order) {
            $list[] = [
                $order->getExpression()->getValue(),
                $order->getDirection(),
            ];
        }

        return $list;
    }
}
