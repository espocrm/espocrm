<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\Order;

use Espo\ORM\Query\Part\OrderList;

use Espo\Core\{
    Exceptions\Error,
    Exceptions\Forbidden,
    Select\SearchParams,
    Select\Order\Params as OrderParams,
    Select\Order\Item as OrderItem,
    Select\Order\ItemConverterFactory,
    Select\Order\MetadataProvider,
    Select\Order\OrdererFactory,
};

use Espo\ORM\Query\SelectBuilder as QueryBuilder;

class Applier
{
    private string $entityType;

    private MetadataProvider $metadataProvider;

    private ItemConverterFactory $itemConverterFactory;

    private OrdererFactory $ordererFactory;

    public function __construct(
        string $entityType,
        MetadataProvider $metadataProvider,
        ItemConverterFactory $itemConverterFactory,
        OrdererFactory $ordererFactory
    ) {
        $this->entityType = $entityType;
        $this->metadataProvider = $metadataProvider;
        $this->itemConverterFactory = $itemConverterFactory;
        $this->ordererFactory = $ordererFactory;
    }

    public function apply(QueryBuilder $queryBuilder, OrderParams $params): void
    {
        if ($params->forceDefault()) {
            $this->applyDefaultOrder($queryBuilder, $params->getOrder());

            return;
        }

        $orderBy = $params->getOrderBy();

        if ($params->forbidComplexExpressions() && $orderBy) {
            if (
                !is_string($orderBy) ||
                strpos($orderBy, '.') !== false ||
                strpos($orderBy, ':') !== false
            ) {
                throw new Forbidden("Complex expressions are forbidden in 'orderBy'.");
            }
        }

        if ($orderBy === null) {
            $orderBy = $this->metadataProvider->getDefaultOrderBy($this->entityType);
        }

        if (!$orderBy) {
            return;
        }

        $this->applyOrder($queryBuilder, $orderBy, $params->getOrder());
    }

    private function applyDefaultOrder(QueryBuilder $queryBuilder, ?string $order): void
    {
        $orderBy = $this->metadataProvider->getDefaultOrderBy($this->entityType);

        if (!$orderBy) {
            $queryBuilder->order('id', $order);

            return;
        }

        if (!$order) {
            $order = $this->metadataProvider->getDefaultOrder($this->entityType);

            if ($order && strtolower($order) === 'desc') {
                $order = SearchParams::ORDER_DESC;
            }
            else if ($order && strtolower($order) === 'asc') {
                $order = SearchParams::ORDER_ASC;
            }
            else if ($order !== null) {
                throw new Error("Bad default order.");
            }
        }

        $this->applyOrder($queryBuilder, $orderBy, $order);
    }

    private function applyOrder(QueryBuilder $queryBuilder, string $orderBy, ?string $order): void
    {
        if (!$orderBy) {
            throw new Error("Could not apply empty order.");
        }

        if ($order === null) {
            $order = SearchParams::ORDER_ASC;
        }

        $hasOrderer = $this->ordererFactory->has($this->entityType, $orderBy);

        if ($hasOrderer) {
            $orderer = $this->ordererFactory->create($this->entityType, $orderBy);

            $orderer->apply(
                $queryBuilder,
                OrderItem::create($orderBy, $order)
            );

            if ($order !== 'id') {
                $queryBuilder->order('id', $order);
            }

            return;
        }

        $resultOrderBy = $orderBy;

        $type = $this->metadataProvider->getFieldType($this->entityType, $orderBy);

        $hasItemConverter = $this->itemConverterFactory->has($this->entityType, $orderBy);

        if ($hasItemConverter) {
            $converter = $this->itemConverterFactory->create($this->entityType, $orderBy);

            $resultOrderBy = $this->orderListToArray(
                $converter->convert(
                    OrderItem::create($orderBy, $order)
                )
            );
        }
        else if (in_array($type, ['link', 'file', 'image', 'linkOne'])) {
            $resultOrderBy .= 'Name';
        }
        else if ($type === 'linkParent') {
            $resultOrderBy .= 'Type';
        }
        else if (
            strpos($orderBy, '.') === false &&
            strpos($orderBy, ':') === false &&
            !$this->metadataProvider->hasAttribute($this->entityType, $orderBy)
        ) {
            throw new Error("Order by non-existing field '{$orderBy}'.");
        }

        $orderByAttribute = null;

        if (!is_array($resultOrderBy)) {
            $orderByAttribute = $resultOrderBy;

            $resultOrderBy = [
                [$resultOrderBy, $order]
            ];
        }

        if (
            $orderBy !== 'id' &&
            (
                !$orderByAttribute ||
                !$this->metadataProvider->isAttributeParamUniqueTrue($this->entityType, $orderByAttribute)
            ) &&
            $this->metadataProvider->hasAttribute($this->entityType, 'id')
        ) {
            $resultOrderBy[] = ['id', $order];
        }

        $queryBuilder->order($resultOrderBy);
    }

    /**
     * @return array<array{string,string}>
     */
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
