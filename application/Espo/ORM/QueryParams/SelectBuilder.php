<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\ORM\QueryParams;

use InvalidArgumentException;

class SelectBuilder implements Builder
{
    use SelectingBuilderTrait;

    /**
     * Build a SELECT query.
     */
    public function build() : Select
    {
        return Select::fromRaw($this->params);
    }

    /**
     * Clone an existing query for a subsequent modifying and building.
     */
    public function clone(Select $query) : self
    {
        $this->cloneInternal($query);

        return $this;
    }

    /**
     * Set to return STH collection. Recommended for fetching large number of records.
     *
     * @todo Remove.
     * @deprecated
     */
    public function sth() : self
    {
        $this->params['returnSthCollection'] = true;

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null) : self
    {
        $this->params['offset'] = $offset;
        $this->params['limit'] = $limit;

        return $this;
    }

    /**
     * Specify SELECT. Columns and expressions to be selected. If not called, then all entity attributes will be selected.
     * Passing an array will reset previously set items.
     * Passing a string will append an item.
     *
     * Usage options:
     * * `select([$item1, $item2, ...])`
     * * `select(string $expression)`
     * * `select(string $expression, string $alias)`
     *
     * @param array|string $select
     */
    public function select($select, ?string $alias = null) : self
    {
        if (is_array($select)) {
            $this->params['select'] = $select;

            return $this;
        }

        if (is_string($select)) {
            $this->params['select'] = $this->params['select'] ?? [];

            if ($alias) {
                $this->params['select'][] = [$select, $alias];
            } else {
                $this->params['select'][] = $select;
            }

            return $this;
        }

        throw new InvalidArgumentException();
    }

    /**
     * Specify GROUP BY.
     */
    public function groupBy(array $groupBy) : self
    {
        $this->params['groupBy'] = $groupBy;

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * Two usage options:
     * * `having(array $havingClause)`
     * * `having(string $key, string $value)`
     *
     * @param array|string $keyOrClause
     * @param ?array|string $value
     */
    public function having($keyOrClause = [], $value = null) : self
    {
        $this->applyWhereClause('havingClause', $keyOrClause, $value);

        return $this;
    }

    /**
     * @todo Remove?
     */
    public function withDeleted() : self
    {
        $this->params['withDeleted'] = true;

        return $this;
    }
}
