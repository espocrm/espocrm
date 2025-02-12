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

namespace Espo\ORM;

use Espo\ORM\Query\Select as SelectQuery;
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

    private function __construct(private EntityManager $entityManager)
    {}

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

    public function getIterator(): Traversable
    {
        return (function () {
            if (isset($this->sth)) {
                $this->sth->execute();
            }

            while ($row = $this->fetchRow()) {
                $entity = $this->entityManager->getEntityFactory()->create($this->entityType);

                $entity->set($row);
                $entity->setAsFetched();

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
    public static function fromQuery(SelectQuery $query, EntityManager $entityManager): self
    {
        /** @var self<Entity> $obj */
        $obj = new self($entityManager);

        $entityType = $query->getFrom();

        if ($entityType === null) {
            throw new RuntimeException("Query w/o entity type.");
        }

        $obj->entityType = $entityType;
        $obj->query = $query;

        return $obj;
    }

    /**
     * Create from an SQL.
     *
     * @return self<Entity>
     * @internal
     */
    public static function fromSql(string $entityType, string $sql, EntityManager $entityManager): self
    {
        /** @var self<Entity> $obj */
        $obj = new self($entityManager);

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
     * @noinspection PhpDocSignatureInspection
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
}
