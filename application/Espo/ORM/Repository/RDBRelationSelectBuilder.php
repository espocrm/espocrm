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

namespace Espo\ORM\Repository;

use Espo\ORM\{
    Collection,
    Entity,
    EntityManager,
    QueryParams\Select,
    QueryParams\SelectBuilder,
    Mapper\Mapper,
};

use RuntimeException;
use BadMethodCallException;

/**
 * Builds select parameters for related records for RDB repository.
 */
class RDBRelationSelectBuilder
{
    protected $entityManager;

    protected $entity;

    protected $entityType;

    protected $foreignEntityType;

    protected $relationName;

    protected $relationType = null;

    protected $builder = null;

    protected $additionalSelect = [];

    protected $selectIsAdded = false;

    public function __construct(EntityManager $entityManager, Entity $entity, string $relationName, ?Select $query = null)
    {
        $this->entityManager = $entityManager;

        $this->entity = $entity;

        $this->relationName = $relationName;

        $this->relationType = $entity->getRelationType($relationName);

        $this->entityType = $entity->getEntityType();

        $this->foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        if ($query) {
            $this->builder = $this->createSelectBuilder()->clone($query);
        } else {
            $this->builder = $this->createSelectBuilder()->from($this->foreignEntityType);
        }
    }

    protected function createSelectBuilder() : SelectBuilder
    {
        return new SelectBuilder($this->entityManager->getQueryComposer());
    }

    protected function getMapper() : Mapper
    {
        return $this->entityManager->getMapper();
    }

    /**
     * Additional middle table columns. Only for many-to-many relationships.
     *
     * Usage example:
     * `['columnName' => 'attributeName']`
     * Where `attributeName` is a non storable attribute that will be set with a column value.
     *
     * @todo Remove? Use attribute definitions to detect a proper select expression (in QueryComposer).
     * @deprecated Use `->select('middleTable', 'attributeName')` instead.
     */
    public function columns(array $columns) : self
    {
        if (!count($columns)) {
            return $this;
        }

        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't select relation columns for not many-to-many relationship.");
        }

        $middleName = lcfirst(
            $this->entity->getRelationParam($this->relationName, 'relationName')
        );

        foreach ($columns as $column => $alias) {
            $this->additionalSelect[] = [
                $middleName . '.' . $column,
                $alias,
            ];
        }

        return $this;
    }

    /**
     * Apply middle table conditions for a many-to-many relationship.
     *
     * Usage example:
     * `->columnsWhere(['column' => $value])`
     */
    public function columnsWhere(array $where) : self
    {
        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't add columns where for not many-to-many relationship.");
        }

        $transformedWhere = $this->applyMiddleAliasToWhere($where);

        $this->where($transformedWhere);

        return $this;
    }

    protected function applyMiddleAliasToWhere(array $where) : array
    {
        $transformedWhere = [];

        $middleName = lcfirst(
            $this->entity->getRelationParam($this->relationName, 'relationName')
        );

        foreach ($where as $key => $value) {
            $transformedKey = $key;
            $transformedValue = $value;

            if (is_int($key)) {
                $transformedKey = $key;
            }

            if (
                is_string($key) &&
                strlen($key) &&
                strpos($key, '.') === false &&
                $key[0] === strtolower($key[0])
            ) {
                $transformedKey = $middleName . '.' . $key;
            }

            if (is_array($value)) {
                $transformedValue = $this->applyMiddleAliasToWhere($value);
            }

            $transformedWhere[$transformedKey] = $transformedValue;
        }

        return $transformedWhere;
    }

    protected function addAdditionalSelect()
    {
        if (!count($this->additionalSelect)) {
            return;
        }

        $select = $this->builder->build()->getSelect();

        if (!count($select)) {
            $this->builder->select('*');
        }

        foreach ($this->additionalSelect as $item) {
            $this->builder->select($item[0], $item[1]);
        }
    }

    /**
     * Find related records by a criteria.
     */
    public function find() : Collection
    {
        $this->addAdditionalSelect();

        $query = $this->builder->build();

        $related = $this->getMapper()->selectRelated($this->entity, $this->relationName, $query);

        if ($related instanceof Collection) {
            return $related;
        }

        $collection = $this->entityManager->getCollectionFactory()->create($this->foreignEntityType);
        $collection->setAsFetched();

        if ($related instanceof Entity) {
            $collection[] = $related;
        }

        return $collection;
    }

    /**
     * Find a first related records by a criteria.
     */
    public function findOne() : ?Entity
    {
        $collection = $this->limit(0, 1)->find();

        if (!count($collection)) {
            return null;
        }

        foreach ($collection as $entity) {
            return $entity;
        }
    }

    /**
     * Get a number of related records that meet criteria.
     */
    public function count() : int
    {
        $query = $this->builder->build();

        return $this->getMapper()->countRelated($this->entity, $this->relationName, $query);
    }


    /**
     * Add JOIN.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::join()
     */
    public function join($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
        $this->builder->join($relationName, $alias, $conditions);

        return $this;
    }

    /**
     * Add LEFT JOIN.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::leftJoin()
     */
    public function leftJoin($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
        $this->builder->leftJoin($relationName, $alias, $conditions);

        return $this;
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct() : self
    {
        $this->builder->distinct();

        return $this;
    }

    /**
     * Set to return STH collection. Recommended for fetching large number of records.
     *
     * @todo Remove.
     */
    public function sth() : self
    {
        $this->builder->sth();

        return $this;
    }

    /**
     * Add a WHERE clause.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::where()
     *
     * @param array|string $keyOrClause
     * @param ?array|string $value
     */
    public function where($keyOrClause = [], $value = null) : self
    {
        $this->builder->where($keyOrClause, $value);

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::having()
     */
    public function having($keyOrClause = [], $value = null) : self
    {
        $this->builder->having($keyOrClause, $params2);

        return $this;
    }

    /**
     * Apply ORDER.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::order()
     *
     * @param string|array $orderBy
     * @param bool|string $direction
     */
    public function order($orderBy, $direction = 'ASC') : self
    {
        $this->builder->order($orderBy, $direction);

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null) : self
    {
        $this->builder->limit($offset, $limit);

        return $this;
    }

    /**
     * Specify SELECT. Which attributes to select. All attributes are selected by default.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::select()
     *
     * @param array|string $select
     */
    public function select($select, ?string $alias = null) : self
    {
        $this->builder->select($select, $alias);

        return $this;
    }

    /**
     * Specify GROUP BY.
     */
    public function groupBy(array $groupBy) : self
    {
        $this->builder->groupBy($groupBy);

        return $this;
    }
}
