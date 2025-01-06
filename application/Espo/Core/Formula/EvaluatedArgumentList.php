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

namespace Espo\Core\Formula;

use BadMethodCallException;
use OutOfBoundsException;
use Iterator;
use Countable;
use ArrayAccess;
use SeekableIterator;

/**
 * A list of evaluated function arguments.
 *
 * @implements ArrayAccess<int, mixed>
 * @implements Iterator<mixed>
 * @implements SeekableIterator<int, mixed>
 */
class EvaluatedArgumentList implements Iterator, Countable, ArrayAccess, SeekableIterator
{
    private int $position = 0;

    /**
     * @param array<int, mixed> $dataList
     */
    public function __construct(private array $dataList)
    {}

    private function getLastValidKey(): int
    {
        $keys = array_keys($this->dataList);

        $i = end($keys);

        if ($i === false) {
            return -1;
        }

        while ($i > 0) {
            if (array_key_exists($i, $this->dataList)) {
                break;
            }

            $i--;
        }

        return $i;
    }

    public function rewind(): void
    {
        $this->position = 0;

        while (!$this->valid() && $this->position <= $this->getLastValidKey()) {
            $this->position ++;
        }
    }

    private function getArgumentByIndex(int $index): mixed
    {
        return $this->dataList[$index];
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->getArgumentByIndex($this->position);
    }

    /**
     * @return mixed
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

            if (
                !$this->valid() &&
                $this->position <= $this->getLastValidKey()
            ) {
                $next = true;
            }
        } while ($next);
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->dataList);
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->dataList);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->getArgumentByIndex($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Setting is not allowed.');
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Unsetting is not allowed.');
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
}
