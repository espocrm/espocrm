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

use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\Part\WhereItem;

use InvalidArgumentException;
use RuntimeException;

class SelectBuilder implements Builder
{
    use SelectingBuilderTrait;

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Build a SELECT query.
     */
    public function build(): Select
    {
        return Select::fromRaw($this->params);
    }

    /**
     * Clone an existing query for a subsequent modifying and building.
     */
    public function clone(Select $query): self
    {
        $this->cloneInternal($query);

        return $this;
    }

    /**
     * Set FROM. For what entity type to build a query.
     */
    public function from(string $entityType, ?string $alias = null): self
    {
        if (isset($this->params['from']) && $entityType !== $this->params['from']) {
            throw new RuntimeException("Method 'from' can be called only once.");
        }

        if (isset($this->params['fromQuery'])) {
            throw new RuntimeException("Method 'from' can't be if 'fromQuery' is set.");
        }

        $this->params['from'] = $entityType;

        if ($alias) {
            $this->params['fromAlias'] = $alias;
        }

        return $this;
    }

    /**
     * Set FROM sub-query.
     */
    public function fromQuery(SelectingQuery $query, string $alias): self
    {
        if (isset($this->params['from'])) {
            throw new RuntimeException("Method 'fromQuery' can be called only once.");
        }

        if (isset($this->params['fromQuery'])) {
            throw new RuntimeException("Method 'fromQuery' can't be if 'from' is set.");
        }

        if ($alias === '') {
            throw new RuntimeException("Alias can't be empty.");
        }

        $this->params['fromQuery'] = $query;
        $this->params['fromAlias'] = $alias;

        return $this;
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct(): self
    {
        $this->params['distinct'] = true;

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null): self
    {
        $this->params['offset'] = $offset;
        $this->params['limit'] = $limit;

        return $this;
    }

    /**
     * Specify SELECT. Columns and expressions to be selected. If not called, then
     * all entity attributes will be selected. Passing an array will reset
     * previously set items. Passing a SelectExpression|Expression|string will append the item.
     *
     * Usage options:
     * * `select(SelectExpression $expression)`
     * * `select([$expr1, $expr2, ...])`
     * * `select(string $expression, string $alias)`
     *
     * @param Selection|Selection[]|Expression|Expression[]|string[]|string|array<int, string[]|string> $select
     * An array of expressions or one expression.
     * @param string|null $alias An alias. Actual if the first parameter is not an array.
     */
    public function select($select, ?string $alias = null): self
    {
        /** @phpstan-var mixed $select */

        if (is_array($select)) {
            $this->params['select'] = $this->normalizeSelectExpressionArray($select);

            return $this;
        }

        if ($select instanceof Expression) {
            $select = $select->getValue();
        } else if ($select instanceof Selection) {
            $alias = $alias ?? $select->getAlias();
            $select = $select->getExpression()->getValue();
        }

        if (is_string($select)) {
            $this->params['select'] = $this->params['select'] ?? [];

            $this->params['select'][] = $alias ?
                [$select, $alias] :
                $select;

            return $this;
        }

        throw new InvalidArgumentException();
    }

    /**
     * Specify GROUP BY.
     * Passing an array will reset previously set items.
     * Passing a string|Expression will append an item.
     *
     * Usage options:
     * * `groupBy(Expression|string $expression)`
     * * `groupBy([$expr1, $expr2, ...])`
     *
     * @param Expression|Expression[]|string|string[] $groupBy
     */
    public function group($groupBy): self
    {
        /** @phpstan-var mixed $groupBy */

        if (is_array($groupBy)) {
            $this->params['groupBy'] = $this->normalizeExpressionItemArray($groupBy);

            return $this;
        }

        if ($groupBy instanceof Expression) {
            $groupBy = $groupBy->getValue();
        }

        if (is_string($groupBy)) {
            $this->params['groupBy'] = $this->params['groupBy'] ?? [];

            $this->params['groupBy'][] = $groupBy;

            return $this;
        }

        throw new InvalidArgumentException();
    }

    /**
     * @deprecated Use `group` method.
     * @param Expression|Expression[]|string|string[] $groupBy
     */
    public function groupBy($groupBy): self
    {
        return $this->group($groupBy);
    }

    /**
     * Use index.
     */
    public function useIndex(string $index): self
    {
        $this->params['useIndex'] = $this->params['useIndex'] ?? [];

        $this->params['useIndex'][] = $index;

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * Usage options:
     * * `having(WhereItem $clause)`
     * * `having(array $clause)`
     * * `having(string $key, string $value)`
     *
     * @param WhereItem|array<int|string, mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Omitted if the first argument is not string.
     */
    public function having($clause, $value = null): self
    {
        $this->applyWhereClause('havingClause', $clause, $value);

        return $this;
    }

    /**
     * Lock selected rows in shared mode. To be used within a transaction.
     */
    public function forShare(): self
    {
        if (isset($this->params['forUpdate'])) {
            throw new RuntimeException("Can't use two lock modes together.");
        }

        $this->params['forShare'] = true;

        return $this;
    }

    /**
     * Lock selected rows. To be used within a transaction.
     */
    public function forUpdate(): self
    {
        if (isset($this->params['forShare'])) {
            throw new RuntimeException("Can't use two lock modes together.");
        }

        $this->params['forUpdate'] = true;

        return $this;
    }

    /**
     * @todo Remove?
     */
    public function withDeleted(): self
    {
        $this->params['withDeleted'] = true;

        return $this;
    }

    /**
     * @param array<Expression|Selection|mixed[]> $itemList
     * @return array<array{0: string, 1?: string}|string>
     */
    private function normalizeSelectExpressionArray(array $itemList): array
    {
        $resultList = [];

        foreach ($itemList as $item) {
            if ($item instanceof Expression) {
                $resultList[] = $item->getValue();

                continue;
            }

            if ($item instanceof Selection) {
                $resultList[] = $item->getAlias() ?
                    [$item->getExpression()->getValue(), $item->getAlias()] :
                    [$item->getExpression()->getValue()];

                continue;
            }

            if (!is_array($item) || !count($item) || !$item[0] instanceof Expression) {
                /** @var array{0:string,1?:string} $item */
                $resultList[] = $item;

                continue;
            }

            $newItem = [$item[0]->getValue()];

            if (count($item) > 1) {
                $newItem[] = $item[1];
            }

            /** @var array{0: string, 1?: string} $newItem */

            $resultList[] = $newItem;
        }

        return $resultList;
    }
}
