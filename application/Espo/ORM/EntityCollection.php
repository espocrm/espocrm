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

use Iterator;
use Countable;
use ArrayAccess;
use SeekableIterator;
use RuntimeException;
use OutOfBoundsException;
use InvalidArgumentException;

/**
 * A standard collection of entities. It allocates a memory for all entities.
 *
 * @template TEntity of Entity
 * @implements Iterator<TEntity>
 */
class EntityCollection implements Collection, Iterator, Countable, ArrayAccess, SeekableIterator
{
    private $entityFactory = null;

    private $entityType;

    private $position = 0;

    private $isFetched = false;

    protected $dataList = [];

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
     * @return mixed
     */
    public function current()
    {
        return $this->getEntityByOffset($this->position);
    }

    /**
     * @return mixed
     */
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
     * @return mixed
     */
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

    public function append(Entity $entity): void
    {
        $this->dataList[] = $entity;
    }

    private function getEntityByOffset($offset)
    {
        $value = $this->dataList[$offset];

        if ($value instanceof Entity) {
            return $value;
        }

        if (is_array($value)) {
            $this->dataList[$offset] = $this->buildEntityFromArray($value);

            return $this->dataList[$offset];
        }

        return null;
    }

    protected function buildEntityFromArray(array $dataArray): Entity
    {
        if (!$this->entityFactory) {
            throw new RuntimeException("Can't build from array. EntityFactory was not passed to the constructor.");
        }

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
     * @deprecated
     */
    public function getEntityName()
    {
        return $this->entityType;
    }

    public function getDataList()
    {
        return $this->dataList;
    }

    /**
     * Merge with another collection.
     */
    public function merge(EntityCollection $collection)
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
     */
    public function contains($value): bool
    {
        if ($this->indexOf($value) !== false) {
            return true;
        }

        return false;
    }

    public function indexOf($value)
    {
        $index = 0;

        if (is_array($value)) {
            foreach ($this->dataList as $v) {
                if (is_array($v)) {
                    if ($value['id'] == $v['id']) {
                        return $index;
                    }
                }
                else if ($v instanceof Entity) {
                    if ($value['id'] == $v->getId()) {
                        return $index;
                    }
                }

                $index ++;
            }
        }
        else if ($value instanceof Entity) {
            foreach ($this->dataList as $v) {
                if (is_array($v)) {
                    if ($value->getId() == $v['id']) {
                        return $index;
                    }
                }
                else if ($v instanceof Entity) {
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
     * @deprecated Use `getValueMapList`.
     */
    public function toArray(bool $itemsAsObjects = false): array
    {
        $arr = [];

        foreach ($this as $entity) {
            if ($itemsAsObjects) {
                $item = $entity->getValueMap();
            }
            else {
                $item = $entity->toArray();
            }

            $arr[] = $item;
        }

        return $arr;
    }

    public function getValueMapList(): array
    {
        return $this->toArray(true);
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

    public static function fromSthCollection(SthCollection $sthCollection): self
    {
        $entityList = [];

        foreach ($sthCollection as $entity) {
            $entityList[] = $entity;
        }

        $obj = new EntityCollection($entityList, $sthCollection->getEntityType());

        $obj->setAsFetched();

        return $obj;
    }
}
