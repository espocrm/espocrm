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

use Espo\ORM\Name\Attribute;
use Iterator;
use Countable;
use ArrayAccess;
use SeekableIterator;
use RuntimeException;
use OutOfBoundsException;
use InvalidArgumentException;
use Closure;

/**
 * A standard collection of entities. It allocates a memory for all entities.
 *
 * @template TEntity of Entity
 * @implements Iterator<int, TEntity>
 * @implements Collection<TEntity>
 * @implements ArrayAccess<int, TEntity>
 * @implements SeekableIterator<int, TEntity>
 */
class EntityCollection implements Collection, Iterator, Countable, ArrayAccess, SeekableIterator
{
    private ?EntityFactory $entityFactory;
    private ?string $entityType;
    private int $position = 0;
    private bool $isFetched = false;
    /** @var array<TEntity|array<string, mixed>> */
    protected array $dataList = [];

    /**
     * @param array<TEntity|array<string, mixed>> $dataList
     */
    public function __construct(
        array $dataList = [],
        ?string $entityType = null,
        ?EntityFactory $entityFactory = null
    ) {
        $this->dataList = $dataList;
        $this->entityType = $entityType;
        $this->entityFactory = $entityFactory;
    }

    public function rewind(): void
    {
        $this->position = 0;

        while (!$this->valid() && $this->position <= $this->getLastValidKey()) {
            $this->position ++;
        }
    }

    /**
     * @return TEntity
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->getEntityByOffset($this->position);
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        do {
            $this->position ++;

            $next = false;

            if (!$this->valid() && $this->position <= $this->getLastValidKey()) {
                $next = true;
            }
        } while ($next);
    }

    /**
     * @return int
     */
    private function getLastValidKey()
    {
        $keys = array_keys($this->dataList);

        $i = end($keys);

        while ($i > 0) {
            if (isset($this->dataList[$i])) {
                break;
            }

            $i--;
        }

        return $i;
    }

    public function valid(): bool
    {
        return isset($this->dataList[$this->position]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->dataList[$offset]);
    }

    /**
     * @param mixed $offset
     * @return ?TEntity
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!isset($this->dataList[$offset])) {
            return null;
        }

        return $this->getEntityByOffset($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof Entity)) {
            throw new InvalidArgumentException('Only Entity is allowed to be added to EntityCollection.');
        }

        /** @var TEntity $value */

        if (is_null($offset)) {
            $this->dataList[] = $value;

            return;
        }

        $this->dataList[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->dataList[$offset]);
    }

    public function count(): int
    {
        return count($this->dataList);
    }

    /**
     * @param int $offset
     */
    public function seek($offset): void
    {
        $this->position = $offset;

        if (!$this->valid()) {
            throw new OutOfBoundsException("Invalid seek offset ($offset).");
        }
    }

    /**
     * @param TEntity $entity
     */
    public function append(Entity $entity): void
    {
        $this->dataList[] = $entity;
    }

    /**
     * @param int $offset
     * @return TEntity
     */
    private function getEntityByOffset($offset): Entity
    {
        if (!array_key_exists($offset, $this->dataList)) {
            throw new RuntimeException();
        }

        $value = $this->dataList[$offset];

        if ($value instanceof Entity) {
            /** @var TEntity */
            return $value;
        }

        if (is_array($value)) {
            $this->dataList[$offset] = $this->buildEntityFromArray($value);

            return $this->dataList[$offset];
        }

        throw new RuntimeException();
    }

    /**
     * @param array<string, mixed> $dataArray
     * @return TEntity
     */
    protected function buildEntityFromArray(array $dataArray): Entity
    {
        if (!$this->entityFactory) {
            throw new RuntimeException("Can't build from array. EntityFactory was not passed to the constructor.");
        }

        assert($this->entityType !== null);

        /** @var TEntity $entity */
        $entity = $this->entityFactory->create($this->entityType);

        $entity->set($dataArray);

        if ($this->isFetched) {
            $entity->setAsFetched();
        }

        return $entity;
    }

    /**
     * Get an entity type.
     */
    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    /**
     * @return array<TEntity|array<string, mixed>>
     */
    public function getDataList(): array
    {
        return $this->dataList;
    }

    /**
     * Merge with another collection.
     *
     * @param EntityCollection<TEntity> $collection
     */
    public function merge(EntityCollection $collection): void
    {
        $incomingDataList = $collection->getDataList();

        foreach ($incomingDataList as $v) {
            if (!$this->contains($v)) {
                $this->dataList[] = $v;
            }
        }
    }

    /**
     * Whether a collection contains a specific item.
     *
     * @param TEntity|array<string, mixed> $value
     */
    public function contains($value): bool
    {
        if ($this->indexOf($value) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param TEntity|array<string, mixed> $value
     * @return false|int
     */
    public function indexOf($value)
    {
        $index = 0;

        if (is_array($value)) {
            foreach ($this->dataList as $v) {
                if (is_array($v)) {
                    if ($value[Attribute::ID] == $v[Attribute::ID]) {
                        return $index;
                    }
                } else if ($v instanceof Entity) {
                    if ($value[Attribute::ID] == $v->getId()) {
                        return $index;
                    }
                }

                $index ++;
            }
        } else if ($value instanceof Entity) {
            foreach ($this->dataList as $v) {
                if (is_array($v)) {
                    if ($value->getId() == $v[Attribute::ID]) {
                        return $index;
                    }
                } else if ($v instanceof Entity) {
                    if ($value === $v) {
                        return $index;
                    }
                }

                $index ++;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueMapList(): array
    {
        $list = [];

        foreach ($this as $entity) {
            $item = $entity->getValueMap();

            $list[] = $item;
        }

        return $list;
    }

    /**
     * Mark as fetched from DB.
     */
    public function setAsFetched(): void
    {
        $this->isFetched = true;
    }

    /**
     * Is fetched from DB.
     */
    public function isFetched(): bool
    {
        return $this->isFetched;
    }

    /**
     * Create from SthCollection.
     *
     * @param SthCollection<TEntity> $sthCollection
     * @return self<TEntity>
     */
    public static function fromSthCollection(SthCollection $sthCollection): self
    {
        $entityList = [];

        foreach ($sthCollection as $entity) {
            $entityList[] = $entity;
        }

        /** @var self<TEntity> $obj */
        $obj = new EntityCollection($entityList, $sthCollection->getEntityType());
        $obj->setAsFetched();

        return $obj;
    }

    /**
     * Filter.
     *
     * @param Closure(TEntity): bool $callback A filter callback.
     * @return self<TEntity> A filtered collection. A new instance.
     * @since 9.1.0
     */
    public function filter(Closure $callback): self
    {
        $newList = [];

        foreach ($this as $entity) {
            if ($callback($entity)) {
                $newList[] = $entity;
            }
        }

        return new EntityCollection($newList, $this->entityType, $this->entityFactory);
    }

    /**
     * Sort.
     *
     * @param Closure(TEntity, TEntity): int $callback The comparison function.
     * @return self<TEntity> A sorted collection. A new instance.
     * @since 9.1.0
     */
    public function sort(Closure $callback): self
    {
        $newList = [...$this];

        usort($newList, $callback);

        return new EntityCollection($newList, $this->entityType, $this->entityFactory);
    }

    /**
     * Reverse.
     *
     * @return self<TEntity> A reversed collection.
     * @since 9.1.0
     */
    public function reverse(): self
    {
        $newList = array_reverse([...$this]);

        return new EntityCollection($newList, $this->entityType, $this->entityFactory);
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
        foreach ($this as $entity) {
            if ($callback($entity)) {
                return $entity;
            }
        }

        return null;
    }
}
