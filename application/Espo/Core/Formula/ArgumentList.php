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

namespace Espo\Core\Formula;

use BadMethodCallException;
use OutOfBoundsException;
use Iterator;
use Countable;
use ArrayAccess;
use SeekableIterator;

/**
 * A list of function arguments.
 */
class ArgumentList implements Evaluatable, Iterator, Countable, ArrayAccess, SeekableIterator
{
    protected $dataList;

    private $position = 0;

    public function __construct(array $dataList)
    {
        $this->dataList = $dataList;
    }

    private function getLastValidKey()
    {
        $keys = array_keys($this->dataList);

        $i = end($keys);

        while ($i > 0) {
            if (array_key_exists($i, $this->dataList)) {
                break;
            }

            $i--;
        }

        return $i;
    }

    public function rewind()
    {
        $this->position = 0;

        while (!$this->valid() && $this->position <= $this->getLastValidKey()) {
            $this->position ++;
        }
    }

    private function getArgumentByIndex(int $index): Argument
    {
        return new Argument($this->dataList[$index]);
    }

    public function current()
    {
        return $this->getArgumentByIndex($this->position);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        do {
            $this->position ++;
            $next = false;
            if (!$this->valid() && $this->position <= $this->getLastValidKey()) {
                $next = true;
            }
        } while ($next);
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->dataList);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->dataList);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }
        return $this->getArgumentByIndex($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Setting is not allowed.');
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Unsetting is not allowed.');
    }

    public function count(): int
    {
        return count($this->dataList);
    }

    public function seek($offset)
    {
        $this->position = $offset;

        if (!$this->valid()) {
            throw new OutOfBoundsException("Invalid seek offset ($offset).");
        }
    }
}
