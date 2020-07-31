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

use Espo\Core\Exceptions\Error;

use Espo\ORM\{
    Repositories\Findable,
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

    protected $params = [];

    protected $entityManager;

    protected $repository;

    protected $entityType = null;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function from(string $entityType) : self
    {
        if ($this->repository) {
            throw new Error("SelectBuilder: Method 'from' can be called only once.");
        }

        $this->entityType = $entityType;
        $this->repository = $this->entityManager->getRepository($entityType);

        return $this;
    }

    protected function isExecutable() : bool
    {
        return (bool) $this->entityType;
    }

    protected function processExecutableCheck()
    {
        if (!$this->isExecutable()) {
            throw new Error("SelectBuilder: Method 'from' must be called.");
        }
    }

    public function find(array $params = []) : Collection
    {
        $this->processExecutableCheck();

        $params = $this->getMergedParams($params);
        return $this->repository->find($params);
    }

    public function findOne(array $params = []) : ?Entity
    {
        $this->processExecutableCheck();

        $params = $this->getMergedParams($params);
        return $this->repository->findOne($params);
    }

    public function count(array $params = []) : int
    {
        $this->processExecutableCheck();

        $params = $this->getMergedParams($params);
        return $this->repository->count($params);
    }

    public function max(string $attribute)
    {
        $this->processExecutableCheck();

        $params = $this->getMergedParams();
        return $this->repository->max($attribute, $params);
    }

    public function min(string $attribute)
    {
        $this->processExecutableCheck();

        $params = $this->getMergedParams();
        return $this->repository->min($attribute, $params);
    }

    public function sum(string $attribute)
    {
        $this->processExecutableCheck();

        $params = $this->getMergedParams();
        return $this->repository->sum($attribute, $params);
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
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * This method works the same way as `join` method.
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
     * Set DISTINCT parameter.
     */
    public function distinct() : self
    {
        $this->params['distinct'] = true;

        return $this;
    }

    /**
     * Set to return STH collection. Recommended fetching large number of records.
     */
    public function sth() : self
    {
        $this->params['returnSthCollection'] = true;

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
        $this->params['orderBy'] = $attribute;
        $this->params['order'] = $direction;

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
     * Specify SELECT. Which attributes to select. All attributes are selected by default.
     */
    public function select(array $select) : self
    {
        $this->params['select'] = $select;

        return $this;
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
     * Builds result select parameters.
     */
    public function build() : RDBSelectParams
    {
        $this->processExecutableCheck();

        return new RDBSelectParams($this->entityType, $this->getMergedParams());
    }

    protected function getMergedParams(array $params = []) : array
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

        if (!empty($params['leftJoins']) && !empty($this->params['leftJoins'])) {
            foreach ($this->params['leftJoins'] as $j) {
                $params['leftJoins'][] = $j;
            }
        }

        if (!empty($params['joins']) && !empty($this->params['joins'])) {
            foreach ($this->params['joins'] as $j) {
                $params['joins'][] = $j;
            }
        }

        $params = array_replace_recursive($this->params, $params);

        return $params;
    }
}
