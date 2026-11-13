<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\ORM;

use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Select as SelectQuery;
use Espo\ORM\Relation\RelationsMap;
use Espo\ORM\Repository\Util;
use IteratorAggregate;
use Countable;
use Traversable;
use PDO;
use PDOStatement;
use RuntimeException;
use LogicException;
use Closure;

/**
 * Reasonable to use when selecting a large number of records.
 * It doesn't allocate a memory for every entity.
 * Entities are fetched on each iteration while traversing a collection.
 *
 * STH stands for Statement Handle.
 *
 * @template TEntity of Entity
 * @implements IteratorAggregate<int,TEntity>
 * @implements Collection<TEntity>
 */
class SthCollection implements Collection, IteratorAggregate, Countable
{
    private string $entityType;
    private ?SelectQuery $query = null;
    private ?PDOStatement $sth = null;
    private ?string $sql = null;
    private bool $isPartiallyLoaded = false;

    /**
     * @var array{string, string[]}[] $eagerItems
     */
    private array $eagerItems = [];

    private function __construct(
        private EntityManager $entityManager,
        private RelationsMap $relationsMap,
    ) {}

    private function executeQuery(): void
    {
        if ($this->query) {
            $this->sth = $this->entityManager->getQueryExecutor()->execute($this->query);

            return;
        }

        if (!$this->sql) {
            throw new LogicException("No query & sql.");
        }

        $this->sth = $this->entityManager->getSqlExecutor()->execute($this->sql);
    }

    /**
     * @param array{string, string[]}[] $eagerItems
     * @return self<TEntity>
     * @internal
     */
    public function withEagerItems(array $eagerItems): self
    {
        $cloned = clone $this;

        $cloned->eagerItems = $eagerItems;

        return $cloned;
    }

    public function getIterator(): Traversable
    {
        return (function () {
            if (isset($this->sth)) {
                $this->sth->execute();
            }

            while ($row = $this->fetchRow()) {
                $entity = $this->entityManager->getEntityFactory()->create($this->entityType);

                $entity->setMultiple($this->stripEagerColumns($row));

                $this->applyEagerItemsToEntity($entity, $row);

                $entity->setAsFetched();

                if ($this->isPartiallyLoaded && $entity instanceof BaseEntity) {
                    $entity->setAsPartiallyLoaded();
                }

                $this->prepareEntity($entity);

                yield $entity;
            }
        })();
    }

    private function executeQueryIfNotExecuted(): void
    {
        if (!$this->sth) {
            $this->executeQuery();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRow()
    {
        $this->executeQueryIfNotExecuted();

        assert($this->sth !== null);

        return $this->sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get count. Can be slow. Use EntityCollection if you need count.
     */
    public function count(): int
    {
        $this->executeQueryIfNotExecuted();

        assert($this->sth !== null);

        $rowCount = $this->sth->rowCount();

        // MySQL may not return a row count for select queries.
        if ($rowCount) {
            return $rowCount;
        }

        return iterator_count($this);
    }

    protected function prepareEntity(Entity $entity): void
    {}

    /**
     * {@inheritDoc}
     */
    public function getValueMapList(): array
    {
        $list = [];

        foreach ($this as $entity) {
            $list[] = $entity->getValueMap();
        }

        return $list;
    }

    /**
     * Whether is fetched from DB. SthCollection is always fetched.
     */
    public function isFetched(): bool
    {
        return true;
    }

    /**
     * Get an entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Create from a query.
     *
     * @return self<Entity>
     * @internal
     */
    public static function fromQuery(SelectQuery $query, EntityManager $entityManager, RelationsMap $relationsMap): self
    {
        /** @var self<Entity> $obj */
        $obj = new self($entityManager, $relationsMap);

        $entityType = $query->getFrom();

        if ($entityType === null) {
            throw new RuntimeException("Query w/o entity type.");
        }

        $obj->entityType = $entityType;
        $obj->query = $query;

        $select = $query->getSelect();

        if ($select !== [] && !array_find($select, fn ($it) => $it->getExpression()->getValue() === '*')) {
            $obj->isPartiallyLoaded = true;
        }

        return $obj;
    }

    /**
     * Create from an SQL.
     *
     * @return self<Entity>
     * @internal
     */
    public static function fromSql(
        string $entityType,
        string $sql,
        EntityManager $entityManager,
        RelationsMap $relationsMap,
    ): self {

        /** @var self<Entity> $obj */
        $obj = new self($entityManager, $relationsMap);

        $obj->entityType = $entityType;
        $obj->sql = $sql;

        return $obj;
    }

    /**
     * Filter.
     *
     * @param Closure(TEntity): bool $callback A filter callback.
     * @return EntityCollection<TEntity> A filtered collection.
     * @since 9.1.0
     */
    public function filter(Closure $callback): EntityCollection
    {
        return $this->toEntityCollection()->filter($callback);
    }

    /**
     * Sort.
     *
     * @param Closure(TEntity, TEntity): int $callback The comparison function.
     * @return EntityCollection<TEntity> A sorted collection. A new instance.
     * @since 9.1.0
     */
    public function sort(Closure $callback): EntityCollection
    {
        return $this->toEntityCollection()->sort($callback);
    }

    /**
     * Reverse.
     *
     * @return EntityCollection<TEntity> A reversed collection.
     * @since 9.1.0
     */
    public function reverse(): EntityCollection
    {
        return $this->toEntityCollection()->reverse();
    }

    /**
     * Find.
     *
     * @param Closure(TEntity): bool $callback A filter callback.
     * @return ?TEntity
     * @since 9.1.0
     */
    public function find(Closure $callback): ?Entity
    {
        return $this->toEntityCollection()->find($callback);
    }

    /**
     * @return EntityCollection<TEntity>
     */
    private function toEntityCollection(): EntityCollection
    {
        /** @var EntityCollection<TEntity> */
        return (new EntityCollection([...$this], $this->entityType));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function applyEagerItemsToEntity(Entity $entity, array $row): void
    {
        if ($this->eagerItems === []) {
            return;
        }

        foreach ($this->eagerItems as $item) {
            $this->applyEagerItemToEntity($entity, $row, $item[0], $item[1]);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param string[] $select
     */
    private function applyEagerItemToEntity(
        Entity $entity,
        array $row,
        string $relation,
        array $select,
    ): void {

        $idAlias = Util::composeEagerSelectAlias($relation, Attribute::ID);

        if (!array_key_exists($idAlias, $row)) {
            return;
        }

        $id = $row[$idAlias];

        if (!is_string($id) && $id !== null) {
            throw new RuntimeException("Bad ID value.");
        }

        $relations = $this->relationsMap->get($entity);

        if (!$relations) {
            throw new RuntimeException("Could not get relations for entity.");
        }

        if ($id === null) {
            $relations->setLoaded($relation, null);

            return;
        }

        $values = [Attribute::ID => $id];

        foreach ($select as $attribute) {
            $alias = Util::composeEagerSelectAlias($relation, $attribute);

            if (!array_key_exists($alias, $row)) {
                continue;
            }

            $values[$attribute] = $row[$alias];
        }

        $foreignEntityType = $this->entityManager
            ->getDefs()
            ->getEntity($this->entityType)
            ->getRelation($relation)
            ->getForeignEntityType();

        $foreignEntity = $this->entityManager->getEntityFactory()->create($foreignEntityType);

        if (!$foreignEntity instanceof BaseEntity) {
            throw new RuntimeException("Not BaseEntity.");
        }

        $foreignEntity->setMultiple($values);
        $foreignEntity->setAsPartiallyLoaded();
        $foreignEntity->setAsFetched();

        $relations->setLoaded($relation, $foreignEntity);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function stripEagerColumns(array $row): array
    {
        if ($this->eagerItems === []) {
            return $row;
        }

        foreach ($this->eagerItems as [$relation, $select]) {
            unset($row[Util::composeEagerSelectAlias($relation, Attribute::ID)]);

            foreach ($select as $attribute) {
                $alias = Util::composeEagerSelectAlias($relation, $attribute);

                unset($row[$alias]);
            }
        }

        return $row;
    }
}
