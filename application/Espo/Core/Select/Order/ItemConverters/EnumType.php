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

namespace Espo\Core\Select\Order\ItemConverters;

use Espo\ORM\Query\Part\OrderList;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\Expression;

use Espo\Core\{
    Select\Order\ItemConverter,
    Select\Order\Item,
    Select\SearchParams,
    Utils\Metadata,
};

class EnumType implements ItemConverter
{
    private $entityType;

    private $metadata;

    public function __construct(
        string $entityType,
        Metadata $metadata
    ) {
        $this->entityType = $entityType;
        $this->metadata = $metadata;
    }

    public function convert(Item $item): OrderList
    {
        $orderBy = $item->getOrderBy();
        $order = $item->getOrder() ?? SearchParams::ORDER_ASC;

        $list = $this->metadata->get([
            'entityDefs', $this->entityType, 'fields', $orderBy, 'options'
        ]);

        if (!is_array($list) || !count($list)) {
            return OrderList::create([
                Order::fromString($orderBy)->withDirection($order)
            ]);
        }

        $isSorted = $this->metadata->get([
            'entityDefs', $this->entityType, 'fields', $orderBy, 'isSorted'
        ]);

        if ($isSorted) {
            asort($list);
        }

        if ($order === SearchParams::ORDER_DESC) {
            $list = array_reverse($list);
        }

        return OrderList::create([
            Order::createByPositionInList(Expression::column($orderBy), $list),
        ]);
    }
}
