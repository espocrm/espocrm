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

namespace Espo\ORM\Query;

use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\Join;

use InvalidArgumentException;
use LogicException;
use RuntimeException;

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
     * @param WhereItem|array<string|int, mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Omitted if the first argument is not string.
     */
    public function where($clause, $value = null): self
    {
        $this->applyWhereClause('whereClause', $clause, $value);

        return $this;
    }

    /**
     * @param WhereItem|array<string|int, mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Omitted if the first argument is not string.
     */
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
    }

    /**
     * Apply ORDER. Passing an array will override previously set items.
     * Passing non-array will append an item,
     *
     * Usage options:
     * * `order(OrderExpression $expression)
     * * `order([$expr1, $expr2, ...])
     * * `order(string $expression, string $direction)
     *
     * @param Order|Order[]|Expression|string|array<int, string[]>|string[] $orderBy
     * An attribute to order by or an array or order items.
     * Passing an array will reset a previously set order.
     * @param (Order::ASC|Order::DESC)|bool|null $direction A direction. True for DESC.
     */
    public function order($orderBy, $direction = null): self
    {
        if (is_bool($direction)) {
            $direction = $direction ? Order::DESC : Order::ASC;
        }

        if (is_array($orderBy)) {
            $this->params['orderBy'] = $this->normalizeOrderExpressionItemArray(
                $orderBy,
                $direction ?? Order::ASC
            );

            return $this;
        }

        if (!$orderBy) {
            throw new InvalidArgumentException();
        }

        $this->params['orderBy'] = $this->params['orderBy'] ?? [];

        if ($orderBy instanceof Expression) {
            $orderBy = $orderBy->getValue();
            $direction = $direction ?? Order::ASC;
        } else if ($orderBy instanceof Order) {
            $direction = $direction ?? $orderBy->getDirection();
            $orderBy = $orderBy->getExpression()->getValue();
        } else {
            $direction = $direction ?? Order::ASC;
        }

        $this->params['orderBy'][] = [$orderBy, $direction];

        return $this;
    }

    /**
     * Add JOIN.
     *
     * @param Join|string|Select $target A relation name, table or sub-query. A relation name should be in camelCase,
     *     a table in CamelCase.
     * @param ?string $alias An alias.
     * @param WhereItem|array<string|int, mixed>|null $conditions Join conditions.
     */
    public function join(
        $target,
        ?string $alias = null,
        WhereItem|array|null $conditions = null
    ): self {

        return $this->joinInternal('joins', $target, $alias, $conditions);
    }

    /**
     * Add LEFT JOIN.
     *
     * @param Join|string|Select $target A relation name, table or sub-query. A relation name should be in camelCase,
     *     a table in CamelCase.
     * @param ?string $alias An alias.
     * @param WhereItem|array<string|int, mixed>|null $conditions Join conditions.
     */
    public function leftJoin(
        $target,
        ?string $alias = null,
        WhereItem|array|null $conditions = null
    ): self {

        return $this->joinInternal('leftJoins', $target, $alias, $conditions);
    }

    /**
     * @param 'leftJoins'|'joins' $type
     * @todo Support USE INDEX in Join.
     * $target can be an array for backward compatibility.
     * @param Join|string|Select $target $target
     * @param WhereItem|array<string|int, mixed>|null $conditions
     */
    private function joinInternal(
        string $type,
        $target,
        ?string $alias = null,
        WhereItem|array|null $conditions = null
    ): self {

        $onlyMiddle = false;
        $isLateral = false;

        /** @var string|Join|array<int, mixed> $target */

        if ($target instanceof Join) {
            $alias = $alias ?? $target->getAlias();
            $conditions = $conditions ?? $target->getConditions();
            $onlyMiddle = $target->isOnlyMiddle();
            $isLateral = $target->isLateral();

            $target = $target->getTarget();
        }

        if ($target instanceof Select && !$alias) {
            throw new LogicException("Sub-query join can't be used w/o alias.");
        }

        $noLeftAlias = false;

        if ($conditions instanceof WhereItem) {
            $conditions = $conditions->getRaw();

            $noLeftAlias = true;
        }

        if (empty($this->params[$type])) {
            $this->params[$type] = [];
        }

        if (is_array($target)) {
            $joinList = $target;

            foreach ($joinList as $item) {
                $this->params[$type][] = $item;
            }

            return $this;
        }

        if (
            is_null($alias) &&
            is_null($conditions) &&
            is_string($target) &&
            $this->hasJoinAliasInternal($type, $target)
        ) {
            return $this;
        }

        $params = [];

        if ($noLeftAlias) {
            $params['noLeftAlias'] = true;
        }

        if ($onlyMiddle) {
            $params['onlyMiddle'] = true;
        }

        if ($isLateral) {
            $params['isLateral'] = true;
        }

        if ($params !== []) {
            $this->params[$type][] = [$target, $alias, $conditions, $params];

            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->params[$type][] = $target;

            return $this;
        }

        if (is_null($conditions)) {
            $this->params[$type][] = [$target, $alias];

            return $this;
        }

        $this->params[$type][] = [$target, $alias, $conditions];

        return $this;
    }

    private function hasJoinAliasInternal(string $type, string $alias): bool
    {
        $joins = $this->params[$type] ?? [];

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

    /**
     * Whether an alias is in left joins.
     */
    public function hasLeftJoinAlias(string $alias): bool
    {
        return $this->hasJoinAliasInternal('leftJoins', $alias);
    }

    /**
     * Whether an alias is in joins.
     */
    public function hasJoinAlias(string $alias): bool
    {
        return $this->hasJoinAliasInternal('joins', $alias);
    }

    /**
     * @param array<Expression|mixed[]> $itemList
     * @return array<array{0: string, 1?: string}|string>
     */
    private function normalizeExpressionItemArray(array $itemList): array
    {
        $resultList = [];

        foreach ($itemList as $item) {
            if ($item instanceof Expression) {
                $resultList[] = $item->getValue();

                continue;
            }

            if (!is_array($item) || !count($item) || !$item[0] instanceof Expression) {
                /** @var array{0:string, 1?:string} $item */
                $resultList[] = $item;

                continue;
            }

            $newItem = [$item[0]->getValue()];

            if (count($item) > 1) {
                $newItem[] = $item[1];
            }

            /** @var array{0:string,1?:string} $newItem */

            $resultList[] = $newItem;
        }

        return $resultList;
    }

    /**
     * @param array<Order|mixed[]|string> $itemList
     * @param string|bool|null $direction
     * @return array<array{string, string|bool}>
     */
    private function normalizeOrderExpressionItemArray(array $itemList, $direction): array
    {
        $resultList = [];

        foreach ($itemList as $item) {
            if (is_string($item)) {
                $resultList[] = [$item, $direction];

                continue;
            }

            if (is_int($item)) {
                $resultList[] = [(string) $item, $direction];

                continue;
            }

            if ($item instanceof Order) {
                $resultList[] = [
                    $item->getExpression()->getValue(),
                    $item->getDirection()
                ];

                continue;
            }

            if ($item instanceof Expression) {
                $resultList[] = [
                    $item->getValue(),
                    $direction
                ];

                continue;
            }

            if (!is_array($item) || !count($item)) {
                throw new RuntimeException("Bad order item.");
            }

            $itemValue = $item[0] instanceof Expression ?
                $item[0]->getValue() :
                $item[0];

            if (!is_string($itemValue) && !is_int($itemValue)) {
                throw new RuntimeException("Bad order item.");
            }

            $itemDirection = count($item) > 1 ? $item[1] : $direction;

            if (is_bool($itemDirection)) {
                $itemDirection = $itemDirection ?
                    Order::DESC :
                    Order::ASC;
            }

            $resultList[] = [$itemValue, $itemDirection];
        }

        return $resultList;
    }
}
