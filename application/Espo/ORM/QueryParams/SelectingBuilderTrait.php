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

namespace Espo\ORM\QueryParams;

trait SelectingBuilderTrait
{
    use BaseBuilderTrait;

    /**
     * Add a WHERE clause.
     *
     * Two usage options:
     * * `where(array $whereClause)`
     * * `where(string $key, string $value)`
     *
     * @param array|string $keyOrClause A key or where clause.
     * @param ?array|string $value A value. If the first argument is an array, then should be omited.
     */
    public function where($keyOrClause = [], $value = null) : self
    {
        $this->applyWhereClause('whereClause', $keyOrClause, $value);

        return $this;
    }

    protected function applyWhereClause(string $type, $keyOrClause, $value)
    {
        $this->params[$type] = $this->params[$type] ?? [];

        $original = $this->params[$type];

        if (!is_string($keyOrClause) && !is_array($keyOrClause)) {
            throw new InvalidArgumentException();
        }

        if (is_array($keyOrClause)) {
            $new = $keyOrClause;
        }

        if (is_string($keyOrClause)) {
            $new = [$keyOrClause => $value];
        }

        $containsSameKeys = (bool) count(
            array_intersect(
                array_keys($new),
                array_keys($original)
            )
        );

        if ($containsSameKeys) {
            $this->params[$type][] = $new;

            return $this;
        }

        $this->params[$type] = $new + $original;

        return $this;
    }

    /**
     * Apply ORDER.
     *
     * @param string|int|array $orderBy An attribute to order by or order definitions as an array.
     * @param bool|string $direction 'ASC' or 'DESC'. TRUE for DESC order.
     *                               If the first argument is an array then should be omitied.
     */
    public function order($orderBy, $direction = Select::ORDER_ASC) : self
    {
        if (is_array($orderBy)) {
            $this->params['orderBy'] = $orderBy;

            return $this;
        }

        if (!$orderBy) {
            throw InvalidArgumentException();
        }

        $this->params['orderBy'] = $this->params['orderBy'] ?? [];

        $this->params['orderBy'][] = [$orderBy, $direction];

        return $this;
    }

    /**
     * Add JOIN.
     *
     * @param string $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     */
    public function join($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
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
     * @param string $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     */
    public function leftJoin($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
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
    public function hasLeftJoinAlias(string $alias) : bool
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
    public function hasJoinAlias(string $alias) : bool
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
}
