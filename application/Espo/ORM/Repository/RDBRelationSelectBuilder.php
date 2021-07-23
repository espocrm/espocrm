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

namespace Espo\ORM\Repository;

use Espo\ORM\{
    Collection,
    SthCollection,
    Entity,
    EntityManager,
    Query\Select,
    Query\SelectBuilder,
    Query\Part\WhereItem,
    Query\Part\SelectItem,
    Mapper\Mapper,
};

use RuntimeException;
use InvalidArgumentException;

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

    private $middleTableAlias = null;

    protected $returnSthCollection = false;

    public function __construct(
        EntityManager $entityManager,
        Entity $entity,
        string $relationName,
        ?Select $query = null
    ) {
        $this->entityManager = $entityManager;

        $this->entity = $entity;

        $this->relationName = $relationName;

        $this->relationType = $entity->getRelationType($relationName);

        $this->entityType = $entity->getEntityType();

        $this->foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        $this->builder = $query ?
            $this->cloneQueryToBuilder($query) :
            $this->createSelectBuilder()->from($this->foreignEntityType);
    }

    private function cloneQueryToBuilder(Select $query): SelectBuilder
    {
        $where = $query->getWhere();

        if ($where === null) {
            return $this->createSelectBuilder()->clone($query);
        }

        $rawQuery = $query->getRaw();

        $rawQuery['whereClause'] = $this->applyRelationAliasToWhereClause($where->getRaw());

        $newQuery = Select::fromRaw($rawQuery);

        return $this->createSelectBuilder()->clone($newQuery);
    }

    protected function createSelectBuilder(): SelectBuilder
    {
        return new SelectBuilder($this->entityManager->getQueryComposer());
    }

    protected function getMapper(): Mapper
    {
        return $this->entityManager->getMapper();
    }

    /**
     * Apply middle table conditions for a many-to-many relationship.
     *
     * Usage example:
     * `->columnsWhere(['column' => $value])`
     *
     * @param WhereItem|array $clause Where clause.
     */
    public function columnsWhere($clause): self
    {
        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't add columns where for not many-to-many relationship.");
        }

        if ($clause instanceof WhereItem) {
            $clause = $clause->getRaw();
        }

        if (!is_array($clause)) {
            throw new InvalidArgumentException();
        }

        $transformedWhere = $this->applyMiddleAliasToWhere($clause);

        $this->where($transformedWhere);

        return $this;
    }

    protected function applyMiddleAliasToWhere(array $where): array
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

    /**
     * Find related records by a criteria.
     */
    public function find(): Collection
    {
        $query = $this->builder->build();

        $related = $this->getMapper()->selectRelated($this->entity, $this->relationName, $query);

        if ($related instanceof Collection) {
            return $this->handleReturnCollection($related);
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
    public function findOne(): ?Entity
    {
        $collection = $this->sth()->limit(0, 1)->find();

        foreach ($collection as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Get a number of related records that meet criteria.
     */
    public function count(): int
    {
        $query = $this->builder->build();

        return $this->getMapper()->countRelated($this->entity, $this->relationName, $query);
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
        $this->builder->join($relationName, $alias, $conditions);

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
        $this->builder->leftJoin($relationName, $alias, $conditions);

        return $this;
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct(): self
    {
        $this->builder->distinct();

        return $this;
    }

    /**
     * Return STH collection. Recommended for fetching large number of records.
     */
    public function sth(): self
    {
        $this->returnSthCollection = true;

        return $this;
    }

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
    public function where($clause = [], $value = null): self
    {
        if ($this->isManyMany()) {
            if ($clause instanceof WhereItem) {
                $clause = $this->applyRelationAliasToWhereClause($clause->getRaw());
            }
            else if (is_string($clause)) {
                $clause = $this->applyRelationAliasToWhereClauseKey($clause);
            }
            else if (is_array($clause)) {
                $clause = $this->applyRelationAliasToWhereClause($clause);
            }
        }

        $this->builder->where($clause, $value);

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
     * @param WhereItem|array|string $clause A key or where clause.
     * @param array|string|null $value A value. Omitted if the first argument is not string.
     */
    public function having($clause = [], $value = null): self
    {
        $this->builder->having($clause, $value);

        return $this;
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
    public function order($orderBy = 'id', $direction = Select::ORDER_ASC): self
    {
        $this->builder->order($orderBy, $direction);

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null): self
    {
        $this->builder->limit($offset, $limit);

        return $this;
    }

    /**
     * Specify SELECT. Columns and expressions to be selected. If not called, then
     * all entity attributes will be selected. Passing an array will reset
     * previously set items. Passing a string|Expression|SelectItem will append the item.
     *
     * Usage options:
     * * `select([$expr1, $expr2, ...])`
     * * `select([[$expr1, $alias1], [$expr2, $alias2], ...])`
     * * `select([$selectItem1, $selectItem2, ...])`
     * * `select(string|Expression $expression)`
     * * `select(string|Expression $expression, string $alias)`
     * * `select(SelectItem $selectItem)`
     *
     * @param array|string|Expression|SelectItem $select An array of expressions or one expression.
     * @param string|null $alias An alias. Actual if the first parameter is a string.
     */
    public function select($select, ?string $alias = null): self
    {
        $this->builder->select($select, $alias);

        return $this;
    }

    /**
     * Specify GROUP BY.
     * Passing an array will reset previously set items.
     * Passing a string will append an item.
     *
     * Usage options:
     * * `groupBy([$expr1, $expr2, ...])`
     * * `groupBy(string|Expression $expression)`
     *
     * @param string|Expression|array $groupBy
     */
    public function groupBy($groupBy): self
    {
        $this->builder->groupBy($groupBy);

        return $this;
    }

    protected function getMiddleTableAlias(): ?string
    {
        if (!$this->isManyMany()) {
            return null;
        }

        if (!$this->middleTableAlias) {
            $middleName = $this->entity->getRelationParam($this->relationName, 'relationName');

            if (!$middleName) {
                throw new RuntimeException("No relation name.");
            }

            $this->middleTableAlias = lcfirst($middleName);
        }

        return $this->middleTableAlias;
    }

    protected function applyRelationAliasToWhereClauseKey(string $item): string
    {
        if (!$this->isManyMany()) {
            return $item;
        }

        $alias = $this->getMiddleTableAlias();

        return str_replace('@relation.', $alias . '.', $item);
    }

    protected function applyRelationAliasToWhereClause(array $where): array
    {
        if (!$this->isManyMany()) {
            return $where;
        }

        $transformedWhere = [];

        foreach ($where as $key => $value) {
            $transformedKey = $key;
            $transformedValue = $value;

            if (is_int($key)) {
                $transformedKey = $key;
            }

            if (is_string($key)) {
                $transformedKey = $this->applyRelationAliasToWhereClauseKey($key);
            }

            if (is_array($value)) {
                $transformedValue = $this->applyRelationAliasToWhereClause($value);
            }

            $transformedWhere[$transformedKey] = $transformedValue;
        }

        return $transformedWhere;
    }

    protected function isManyMany(): bool
    {
        return $this->relationType === Entity::MANY_MANY;
    }

    protected function handleReturnCollection(SthCollection $collection): Collection
    {
        if ($this->returnSthCollection) {
            return $collection;
        }

        return $this->entityManager->getCollectionFactory()->createFromSthCollection($collection);
    }
}
