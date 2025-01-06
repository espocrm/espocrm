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

namespace Espo\ORM\Repository;

use Espo\ORM\Collection;
use Espo\ORM\EntityCollection;
use Espo\ORM\Name\Attribute;
use Espo\ORM\SthCollection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\Part\Join;
use Espo\ORM\Mapper\Mapper;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Mapper\BaseMapper;

use LogicException;
use RuntimeException;

/**
 * Builds select parameters for an RDB repository. Contains 'find' methods.
 *
 * @template TEntity of Entity
 */
class RDBSelectBuilder
{
    private SelectBuilder $builder;
    /** @var RDBRepository<TEntity> */
    private RDBRepository $repository;

    private bool $returnSthCollection = false;

    public function __construct(
        private EntityManager $entityManager,
        string $entityType,
        ?Select $query = null
    ) {

        /** @var RDBRepository<TEntity> $repository */
        $repository = $this->entityManager->getRepository($entityType);

        $this->repository = $repository;

        if ($query && $query->getFrom() !== $entityType) {
            throw new RuntimeException("SelectBuilder: Passed query doesn't match the entity type.");
        }

        $this->builder = new SelectBuilder();

        if ($query) {
            $this->builder->clone($query);
        }

        if (!$query) {
            $this->builder->from($entityType);
        }
    }

    protected function getMapper(): Mapper
    {
        return $this->entityManager->getMapper();
    }

    /**
     * @return EntityCollection<TEntity>|SthCollection<TEntity>
     */
    public function find(): EntityCollection|SthCollection
    {
        $query = $this->builder->build();

        /** @var Collection<TEntity> $collection */
        $collection = $this->getMapper()->select($query);

        return $this->handleReturnCollection($collection);
    }

    /**
     * @return ?TEntity
     */
    public function findOne(): ?Entity
    {
        $query = $this->builder->build();

        $args = func_get_args();

        // For bc.
        // @todo Remove in v10.0.
        if (
            count($args) &&
            is_array($args[0]) &&
            !empty($args[0]['withDeleted'])
        ) {
            $query = SelectBuilder::create()
                ->clone($query)
                ->withDeleted()
                ->build();
        }

        $cloned = $this->repository->clone($query);

        $collection = $cloned
            ->sth()
            ->limit(0, 1)
            ->find();

        foreach ($collection as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Get a number of records.
     */
    public function count(): int
    {
        $query = $this->builder->build();

        return $this->getMapper()->count($query);
    }

    /**
     * Get a max value.
     *
     * @return int|float
     */
    public function max(string $attribute)
    {
        $query = $this->builder->build();

        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new RuntimeException("Not supported 'max'.");
        }

        return $mapper->max($query, $attribute);
    }

    /**
     * Get a min value.
     *
     * @return int|float
     */
    public function min(string $attribute)
    {
        $query = $this->builder->build();

        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new RuntimeException("Not supported 'min'.");
        }

        return $mapper->min($query, $attribute);
    }

    /**
     * Get a sum value.
     *
     * @return int|float
     */
    public function sum(string $attribute)
    {
        $query = $this->builder->build();

        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new RuntimeException("Not supported 'sum'.");
        }

        return $mapper->sum($query, $attribute);
    }

    /**
     * Add JOIN.
     *
     * @param Join|string $target
     *   A relation name or table. A relation name should be in camelCase, a table in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array<mixed, mixed>|null $conditions Join conditions.
     * @return RDBSelectBuilder<TEntity>
     */
    public function join($target, ?string $alias = null, $conditions = null): self
    {
        $this->builder->join($target, $alias, $conditions);

        return $this;
    }

    /**
     * Add LEFT JOIN.
     *
     * @param Join|string $target
     *   A relation name or table. A relation name should be in camelCase, a table in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array<string|int, mixed>|null $conditions Join conditions.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function leftJoin($target, ?string $alias = null, $conditions = null): self
    {
        $this->builder->leftJoin($target, $alias, $conditions);

        return $this;
    }

    /**
     * Set DISTINCT parameter.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function distinct(): self
    {
        $this->builder->distinct();

        return $this;
    }

    /**
     * Lock selected rows. To be used within a transaction.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function forUpdate(): self
    {
        $this->builder->forUpdate();
        $this->sth();

        return $this;
    }

    /**
     * Set to return STH collection. Recommended for fetching large number of records.
     *
     * @todo Remove.
     * @return RDBSelectBuilder<TEntity>
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
     * @param WhereItem|array<mixed, mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Should be omitted if the first argument is not string.
     * @return RDBSelectBuilder<TEntity>
     */
    public function where($clause = [], $value = null): self
    {
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
     * @param WhereItem|array<mixed, mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Should be omitted if the first argument is not string.
     * @return RDBSelectBuilder<TEntity>
     */
    public function having($clause = [], $value = null): self
    {
        $this->builder->having($clause, $value);

        return $this;
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
     * @param (Order::ASC|Order::DESC)|bool|null $direction A direction.
     * @return RDBSelectBuilder<TEntity>
     */
    public function order($orderBy = Attribute::ID, $direction = null): self
    {
        $this->builder->order($orderBy, $direction);

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function limit(?int $offset = null, ?int $limit = null): self
    {
        $this->builder->limit($offset, $limit);

        return $this;
    }

    /**
     * Specify SELECT. Columns and expressions to be selected. If not called, then
     * all entity attributes will be selected. Passing an array will reset
     * previously set items. Passing a string|Expression|SelectExpression will append the item.
     *
     * Usage options:
     * * `select([$expr1, $expr2, ...])`
     * * `select([[$expr1, $alias1], [$expr2, $alias2], ...])`
     * * `select([$selectItem1, $selectItem2, ...])`
     * * `select(string|Expression $expression)`
     * * `select(string|Expression $expression, string $alias)`
     * * `select(SelectExpression $selectItem)`
     *
     * @param Selection|Selection[]|Expression|Expression[]|string[]|string|array<int, string[]|string> $select
     * An array of expressions or one expression.
     * @param string|null $alias An alias. Actual if the first parameter is a string.
     * @return RDBSelectBuilder<TEntity>
     */
    public function select($select, ?string $alias = null): self
    {
        $this->builder->select($select, $alias);

        return $this;
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
     * @return RDBSelectBuilder<TEntity>
     */
    public function group($groupBy): self
    {
        $this->builder->group($groupBy);

        return $this;
    }

    /**
     * @deprecated Use `group` method.
     *
     * @return RDBSelectBuilder<TEntity>
     * @param Expression|Expression[]|string|string[] $groupBy
     */
    public function groupBy($groupBy): self
    {
        return $this->group($groupBy);
    }

    /**
     * @param Collection<TEntity> $collection
     * @return EntityCollection<TEntity>|SthCollection<TEntity>
     */
    protected function handleReturnCollection(Collection $collection): EntityCollection|SthCollection
    {
        if (!$collection instanceof SthCollection) {
            if (!$collection instanceof EntityCollection) {
                throw new LogicException();
            }

            return $collection;
        }

        if ($this->returnSthCollection) {
            return $collection;
        }

        /** @var EntityCollection<TEntity> */
        return $this->entityManager->getCollectionFactory()->createFromSthCollection($collection);
    }
}
