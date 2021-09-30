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

namespace tests\unit\Espo\Core\Select\Applier\Appliers;

use Espo\ORM\Query\Part\OrderList;
use Espo\ORM\Query\Part\Order;

use Espo\Core\{
    Exceptions\Error,
    Select\Applier\Appliers\Order as OrderApplier,
    Select\SearchParams,
    Select\Order\Params as OrderParams,
    Select\Order\Item,
    Select\Order\ItemConverterFactory,
    Select\Order\ItemConverter,
    Select\Order\MetadataProvider,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Entities\User,
};

class OrderApplierTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->itemConverterFactory = $this->createMock(ItemConverterFactory::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->params = $this->createMock(OrderParams::class);

        $this->entityType = 'Test';

        $this->applier = new OrderApplier(
            $this->entityType,
            $this->user,
            $this->metadataProvider,
            $this->itemConverterFactory
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

    protected function initApplyOrderTest(
        string $orderBy, string $order, string $fieldType, ?OrderList $converterResult = null, bool $notExisting = false
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
                ->will(
                    $this->returnValueMap(
                        [
                            [$this->entityType, $orderBy, !$notExisting],
                            [$this->entityType, 'id', true],
                        ]
                    )
                );

        if ($converterResult) {
            $converter = $this->createMock(ItemConverter::class);

            $this->itemConverterFactory
                ->expects($this->once())
                ->method('create')
                ->with($this->entityType, $orderBy)
                ->willReturn($converter);

            $item = Item::fromArray([
                'orderBy' => $orderBy,
                'order' => $order,
            ]);

            $converter
                ->expects($this->once())
                ->method('convert')
                ->with($item)
                ->willReturn($converterResult);
        } else {

            if ($notExisting) {
                $this->expectException(Error::class);

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
