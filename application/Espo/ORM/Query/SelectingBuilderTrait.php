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

use Espo\ORM\Query\{
    Part\WhereItem,
    Part\Expression,
};

use InvalidArgumentException;

trait SelectingBuilderTrait
{
    use BaseBuilderTrait;

    /**
     * Add a WHERE clause.
     *
     * Usage options:
     * * `where(WhereItem $clause)`
     * * `where(array $clause)`
     * * `where(string $key, string $value)`
     *
     * @param WhereItem|array|string $clause A key or where clause.
     * @param array|string|null $value A value. Omitted if the first argument is not string.
     */
    public function where($clause, $value = null): self
    {
        $this->applyWhereClause('whereClause', $clause, $value);

        return $this;
    }

    private function applyWhereClause(string $type, $clause, $value): void
    {
        if ($clause instanceof WhereItem) {
            $clause = $clause->getRaw();
        }

        $this->params[$type] = $this->params[$type] ?? [];

        $original = $this->params[$type];

        if (!is_string($clause) && !is_array($clause)) {
            throw new InvalidArgumentException("Bad where clause.");
        }

        if (is_array($clause)) {
            $new = $clause;
        }

        if (is_string($clause)) {
            $new = [$clause => $value];
        }

        $containsSameKeys = (bool) count(
            array_intersect(
                array_keys($new),
                array_keys($original)
            )
        );

        if ($containsSameKeys) {
            $this->params[$type][] = $new;

            return;
        }

        $this->params[$type] = $new + $original;

        return;
    }

    /**
     * Apply ORDER.
     *
     * Usage options:
     * * `order(Expression|string $orderBy, string|bool $direction)
     * * `order(int $positionInSelect, string|bool $direction)
     * * `order([[$expr1, $direction1], [$expr2, $direction2], ...])
     * * `order([$expr1, $expr2, ...], string|bool $direction)
     *
     * @param string|Expression|int|array $orderBy
     *     An attribute to order by or an array or order items.
     *     Passing an array will reset a previously set order.
     * @param string|bool $direction 'ASC' or 'DESC'. TRUE for DESC order.
     */
    public function order($orderBy, $direction = Select::ORDER_ASC): self
    {
        if (is_array($orderBy)) {
            $this->params['orderBy'] = $this->normilizeOrderExpressionItemArray($orderBy, $direction);

            return $this;
        }

        if (!$orderBy) {
            throw InvalidArgumentException();
        }

        $this->params['orderBy'] = $this->params['orderBy'] ?? [];

        if ($orderBy instanceof Expression) {
            $orderBy = $orderBy->getValue();
        }

        $this->params['orderBy'][] = [$orderBy, $direction];

        return $this;
    }

    /**
     * Add JOIN.
     *
     * @param string $relationName
     *     A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array|null $conditions Join conditions.
     */
    public function join($relationName, ?string $alias = null, $conditions = null): self
    {
        if ($conditions !== null && !is_array($conditions) && !$conditions instanceof WhereItem) {
            throw new InvalidArgumentException("Conditions must be WhereItem or array.");
        }

        if ($conditions instanceof WhereItem) {
            $conditions = $conditions->getRaw();
        }

        if (empty($this->params['joins'])) {
            $this->params['joins'] = [];
        }

        if (is_array($relationName)) {
            $joinList = $relationName;

            foreach ($joinList as $item) {
                $this->params['joins'][] = $item;
            }

            return $this;
        }

        if (is_null($alias) && is_null($conditions) && $this->hasJoinAlias($relationName)) {
            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->params['joins'][] = $relationName;

            return $this;
        }

        if (is_null($conditions)) {
            $this->params['joins'][] = [$relationName, $alias];

            return $this;
        }

        $this->params['joins'][] = [$relationName, $alias, $conditions];

        return $this;
    }

    /**
     * Add LEFT JOIN.
     *
     * @param string $relationName
     *     A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array|null $conditions Join conditions.
     */
    public function leftJoin($relationName, ?string $alias = null, $conditions = null): self
    {
        if ($conditions !== null && !is_array($conditions) && !$conditions instanceof WhereItem) {
            throw new InvalidArgumentException("Conditions must be WhereItem or array.");
        }

        if ($conditions instanceof WhereItem) {
            $conditions = $conditions->getRaw();
        }

        if (empty($this->params['leftJoins'])) {
            $this->params['leftJoins'] = [];
        }

        if (is_array($relationName)) {
            $joinList = $relationName;

            foreach ($joinList as $item) {
                $this->params['leftJoins'][] = $item;
            }

            return $this;
        }

        if (is_null($alias) && is_null($conditions) && $this->hasLeftJoinAlias($relationName)) {
            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->params['leftJoins'][] = $relationName;

            return $this;
        }

        if (is_null($conditions)) {
            $this->params['leftJoins'][] = [$relationName, $alias];

            return $this;
        }

        $this->params['leftJoins'][] = [$relationName, $alias, $conditions];

        return $this;
    }

    /**
     * Whether an alias is in left joins.
     */
    public function hasLeftJoinAlias(string $alias): bool
    {
        $leftJoins = $this->params['leftJoins'] ?? [];

        if (in_array($alias, $leftJoins)) {
            return true;
        }

        foreach ($leftJoins as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[1] === $alias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whether an alias is in joins.
     */
    public function hasJoinAlias(string $alias): bool
    {
        $joins = $this->params['joins'] ?? [];

        if (in_array($alias, $joins)) {
            return true;
        }

        foreach ($joins as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[1] === $alias) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normilizeExpressionItemArray(array $itemList): array
    {
        $resultList = [];

        foreach ($itemList as $item) {
            if ($item instanceof Expression) {
                $resultList[] = $item->getValue();

                continue;
            }

            if (!is_array($item) || !count($item) || !$item[0] instanceof Expression) {
                $resultList[] = $item;

                continue;
            }

            $newItem = [$item[0]->getValue()];

            if (count($item) > 1) {
                $newItem[] = $item[1];
            }

            $resultList[] = $newItem;

            continue;
        }

        return $resultList;
    }

    private function normilizeOrderExpressionItemArray(array $itemList, $direction): array
    {
        $resultList = [];

        foreach ($itemList as $item) {
            if (is_string($item)) {
                $resultList[] = [$item, $direction];

                continue;
            }

            if ($item instanceof Expression) {
                $resultList[] = [$item->getValue(), $direction];

                continue;
            }

            if (!is_array($item) || !count($item) || !$item[0] instanceof Expression) {
                $resultList[] = $item;

                continue;
            }

            $newItem = [$item[0]->getValue()];

            if (count($item) > 1) {
                $newItem[] = $item[1];
            }
            else {
                $newItem[] = $direction;
            }

            $resultList[] = $newItem;

            continue;
        }

        return $resultList;
    }
}
