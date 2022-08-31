<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\ORM\Query\Select as SelectQuery;
use Espo\ORM\QueryComposer\QueryComposer as QueryComposer;

use IteratorAggregate;
use Countable;
use Traversable;
use PDO;
use PDOStatement;
use RuntimeException;

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
    private EntityManager $entityManager;

    private string $entityType;

    private ?SelectQuery $query = null;

    private ?PDOStatement $sth = null;

    private ?string $sql = null;

    private function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    private function getQueryComposer(): QueryComposer
    {
        return $this->entityManager->getQueryComposer();
    }

    private function getEntityFactory(): EntityFactory
    {
        return $this->entityManager->getEntityFactory();
    }

    private function getSqlExecutor(): SqlExecutor
    {
        return $this->entityManager->getSqlExecutor();
    }

    private function executeQuery(): void
    {
        $sql = $this->getSql();

        $sth = $this->getSqlExecutor()->execute($sql);

        $this->sth = $sth;
    }

    private function getSql(): string
    {
        if (!$this->sql) {
            $this->sql = $this->getQueryComposer()->composeSelect($this->getQuery());
        }

        return $this->sql;
    }

    private function getQuery(): SelectQuery
    {
        /** @var SelectQuery */
        return $this->query;
    }

    public function getIterator(): Traversable
    {
        return (function () {
            if (isset($this->sth)) {
                $this->sth->execute();
            }

            while ($row = $this->fetchRow()) {
                $entity = $this->getEntityFactory()->create($this->entityType);

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
     * @return array<string,mixed>
     */
    private function fetchRow()
    {
        $this->executeQueryIfNotExecuted();

        assert($this->sth !== null);

        return $this->sth->fetch(PDO::FETCH_ASSOC);
    }

    public function count(): int
    {
        $this->executeQueryIfNotExecuted();

        assert($this->sth !== null);

        $rowCount = $this->sth->rowCount();

        // MySQL may not return a row count for select queries.
        if ($rowCount) {
            return $rowCount;
        }

        return count($this->getValueMapList());
    }

    protected function prepareEntity(Entity $entity): void
    {
    }

    /**
     * @deprecated
     * @return array<int,array<string,mixed>>|\stdClass[]
     */
    public function toArray(bool $itemsAsObjects = false): array
    {
        $arr = [];

        foreach ($this as $entity) {
            if ($itemsAsObjects) {
                $item = $entity->getValueMap();
            }
            else if (method_exists($entity, 'toArray')) {
                $item = $entity->toArray();
            }
            else {
                $item = get_object_vars($entity->getValueMap());
            }

            $arr[] = $item;
        }

        return $arr;
    }

    public function getValueMapList(): array
    {
        /** @var \stdClass[] */
        return $this->toArray(true);
    }


    /**
     * Whether Is fetched from DB. SthCollection is always fetched.
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
     * @return self<Entity>
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
     * @return self<Entity>
     */
    public static function fromSql(string $entityType, string $sql, EntityManager $entityManager): self
    {
        /** @var self<Entity> $obj */
        $obj = new self($entityManager);

        $obj->entityType = $entityType;
        $obj->sql = $sql;

        return $obj;
    }
}
