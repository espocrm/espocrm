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

namespace Espo\Core\Select\Order;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Query\Part\OrderList;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\Order\Item as OrderItem;
use Espo\Core\Select\Order\Params as OrderParams;
use Espo\Core\Select\SearchParams;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use RuntimeException;

class Applier
{
    public function __construct(
        private string $entityType,
        private MetadataProvider $metadataProvider,
        private ItemConverterFactory $itemConverterFactory,
        private OrdererFactory $ordererFactory
    ) {}

    /**
     * @throws Forbidden
     * @throws BadRequest
     */
    public function apply(QueryBuilder $queryBuilder, OrderParams $params): void
    {
        if ($params->forceDefault()) {
            $this->applyDefaultOrder($queryBuilder, $params->getOrder());

            return;
        }

        $orderBy = $params->getOrderBy();

        if ($orderBy) {
            if (
                !is_string($orderBy) ||
                str_contains($orderBy, '.') ||
                str_contains($orderBy, ':')
            ) {
                throw new Forbidden("Complex expressions are forbidden in 'orderBy'.");
            }

            if ($this->metadataProvider->isFieldOrderDisabled($this->entityType, $orderBy)) {
                throw new Forbidden("Order by the field '$orderBy' is disabled.");
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

    /**
     * @param SearchParams::ORDER_ASC|SearchParams::ORDER_DESC|null $order
     * @throws BadRequest
     */
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
                throw new RuntimeException("Bad default order.");
            }
        }

        /** @var SearchParams::ORDER_ASC|SearchParams::ORDER_DESC|null $order */

        $this->applyOrder($queryBuilder, $orderBy, $order);
    }

    /**
     * @param SearchParams::ORDER_ASC|SearchParams::ORDER_DESC|null $order
     * @throws BadRequest
     */
    private function applyOrder(QueryBuilder $queryBuilder, string $orderBy, ?string $order): void
    {
        if (!$orderBy) {
            throw new RuntimeException("Could not apply empty order.");
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
            /*!str_contains($orderBy, '.') &&
            !str_contains($orderBy, ':') &&*/
            !$this->metadataProvider->hasAttribute($this->entityType, $orderBy)
        ) {
            throw new BadRequest("Order by non-existing field '$orderBy'.");
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
     * @return array<array{string, string}>
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
