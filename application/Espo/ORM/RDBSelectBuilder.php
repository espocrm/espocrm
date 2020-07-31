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

namespace Espo\ORM;

use Espo\ORM\{
    Repositories\Findable,
    Repositories\RDB as Repository,
    Collection,
    Entity,
};

/**
 * Builds select parameters for an RDB repository and invokes findable methods.
 */
class RDBSelectBuilder implements Findable
{
    protected $whereClause = [];

    protected $havingClause = [];

    protected $listParams = [];

    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function find(array $params = []) : Collection
    {
        $params = $this->getSelectParams($params);
        return $this->repository->find($params);
    }

    public function findOne(array $params = []) : ?Entity
    {
        $params = $this->getSelectParams($params);
        return $this->repository->findOne($params);
    }

    public function count(array $params = []) : int
    {
        $params = $this->getSelectParams($params);
        return $this->repository->count($params);
    }

    public function max(string $attribute)
    {
        $params = $this->getSelectParams();
        return $this->repository->max($attribute);
    }

    public function min(string $attribute)
    {
        $params = $this->getSelectParams();
        return $this->repository->min($attribute);
    }

    public function sum(string $attribute)
    {
        $params = $this->getSelectParams();
        return $this->repository->sum($attribute);
    }

    /**
     * Add JOIN.
     *
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * Usage options:
     * * `join(string $relationName)`
     * * `join(array $joinDefinitionList)`
     *
     * Usage examples:
     * ```
     * ->join($relationName)
     * ->join($relationName, $alias, $conditions)
     * ->join([$relationName1, $relationName2, ...])
     * ->join([[$relationName, $alias], ...])
     * ->join([[$relationName, $alias, $conditions], ...])
     * ```
     */
    public function join($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
        if (empty($this->listParams['joins'])) {
            $this->listParams['joins'] = [];
        }

        if (is_array($relationName)) {
            $joinList = $relationName;
            foreach ($joinList as $item) {
                $this->listParams['joins'][] = $item;
            }
            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->listParams['joins'][] = $relationName;
            return $this;
        }

        if (is_null($conditions)) {
            $this->listParams['joins'][] = [$relationName, $alias];
            return $this;
        }

        $this->listParams['joins'][] = [$relationName, $alias, $conditions];
        return $this;
    }

    /**
     * Add LEFT JOIN.
     *
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * This method works the same way as `join` method.
     */
    public function leftJoin($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
        if (empty($this->listParams['leftJoins'])) {
            $this->listParams['leftJoins'] = [];
        }

        if (is_array($relationName)) {
            $joinList = $relationName;
            foreach ($joinList as $item) {
                $this->listParams['leftJoins'][] = $item;
            }
            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->listParams['leftJoins'][] = $relationName;
            return $this;
        }

        if (is_null($conditions)) {
            $this->listParams['leftJoins'][] = [$relationName, $alias];
            return $this;
        }

        $this->listParams['leftJoins'][] = [$relationName, $alias, $conditions];
        return $this;
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct() : self
    {
        $this->listParams['distinct'] = true;

        return $this;
    }

    /**
     * Set to return STH collection. Recommended fetching large number of records.
     */
    public function sth() : self
    {
        $this->listParams['returnSthCollection'] = true;

        return $this;
    }

    /**
     * Add a WHERE clause.
     *
     * Two usage options:
     * * `where(array $whereClause)`
     * * `where(string $key, string $value)`
     */
    public function where($param1 = [], $param2 = null) : self
    {
        if (is_array($param1)) {
            $this->whereClause = $param1 + $this->whereClause;

        } else {
            if (!is_null($param2)) {
                $this->whereClause[] = [$param1 => $param2];
            }
        }

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * Two usage options:
     * * `having(array $havingClause)`
     * * `having(string $key, string $value)`
     */
    public function having($param1 = [], $param2 = null) : self
    {
        if (is_array($param1)) {
            $this->havingClause = $param1 + $this->havingClause;
        } else {
            if (!is_null($param2)) {
                $this->havingClause[] = [$param1 => $param2];
            }
        }

        return $this;
    }

    /**
     * Apply ORDER.
     *
     * @param string|array $attribute An attribute to order by or order definitions as an array.
     * @param bool|string $direction TRUE for DESC order.
     */
    public function order($attribute = 'id', $direction = 'ASC') : self
    {
        $this->listParams['orderBy'] = $attribute;
        $this->listParams['order'] = $direction;

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null) : self
    {
        $this->listParams['offset'] = $offset;
        $this->listParams['limit'] = $limit;

        return $this;
    }

    /**
     * Specify SELECT. Which attributes to select. All attributes are selected by default.
     */
    public function select(array $select) : self
    {
        $this->listParams['select'] = $select;

        return $this;
    }

    /**
     * Specify GROUP BY.
     */
    public function groupBy(array $groupBy) : self
    {
        $this->listParams['groupBy'] = $groupBy;

        return $this;
    }


    protected function getSelectParams(array $params = [])
    {
        if (isset($params['whereClause'])) {
            $params['whereClause'] = $params['whereClause'];
            if (!empty($this->whereClause)) {
                $params['whereClause'][] = $this->whereClause;
            }
        } else {
            $params['whereClause'] = $this->whereClause;
        }
        if (!empty($params['havingClause'])) {
            $params['havingClause'] = $params['havingClause'];
            if (!empty($this->havingClause)) {
                $params['havingClause'][] = $this->havingClause;
            }
        } else {
            $params['havingClause'] = $this->havingClause;
        }

        if (empty($params['whereClause'])) {
            unset($params['whereClause']);
        }

        if (empty($params['havingClause'])) {
            unset($params['havingClause']);
        }

        if (!empty($params['leftJoins']) && !empty($this->listParams['leftJoins'])) {
            foreach ($this->listParams['leftJoins'] as $j) {
                $params['leftJoins'][] = $j;
            }
        }

        if (!empty($params['joins']) && !empty($this->listParams['joins'])) {
            foreach ($this->listParams['joins'] as $j) {
                $params['joins'][] = $j;
            }
        }

        $params = array_replace_recursive($this->listParams, $params);

        return $params;
    }
}
