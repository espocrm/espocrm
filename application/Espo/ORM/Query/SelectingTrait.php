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

namespace Espo\ORM\Query;

use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\Join;

use RuntimeException;

trait SelectingTrait
{
    /**
     * Get ORDER items.
     *
     * @return Order[]
     */
    public function getOrder(): array
    {
        return array_map(
            function ($item) {
                if (is_array($item) && count($item)) {
                    $itemValue = is_int($item[0]) ? (string) $item[0] : $item[0];

                    return Order::fromString($itemValue)
                        ->withDirection($item[1] ?? Order::ASC);
                }

                if (is_string($item)) {
                    return Order::fromString($item);
                }

                throw new RuntimeException("Bad order item.");
            },
            $this->params['orderBy'] ?? []
        );
    }

    /**
     * Get WHERE clause.
     */
    public function getWhere(): ?WhereClause
    {
        $whereClause = $this->params['whereClause'] ?? null;

        if ($whereClause === null || $whereClause === []) {
            return null;
        }

        /** @phpstan-ignore-next-line */
        return WhereClause::fromRaw($whereClause);
    }

    /**
     * Get JOIN items.
     *
     * @return Join[]
     */
    public function getJoins(): array
    {
        return array_map(
            function ($item) {
                $conditions = isset($item[2]) ?
                    WhereClause::fromRaw($item[2]) :
                    null;

                return Join::create($item[0])
                    ->withAlias($item[1] ?? null)
                    ->withConditions($conditions);
            },
            $this->params['joins'] ?? []
        );
    }

    /**
     * Get LEFT JOIN items.
     *
     * @return Join[]
     */
    public function getLeftJoins(): array
    {
        return array_map(
            function ($item) {
                $conditions = isset($item[2]) ?
                    WhereClause::fromRaw($item[2]) :
                    null;

                return Join::create($item[0])
                    ->withAlias($item[1] ?? null)
                    ->withConditions($conditions);
            },
            $this->params['leftJoins'] ?? []
        );
    }

    private static function validateRawParamsSelecting(array $params): void
    {
    }
}
