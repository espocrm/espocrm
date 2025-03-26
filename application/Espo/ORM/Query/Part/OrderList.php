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

namespace Espo\ORM\Query\Part;

use InvalidArgumentException;
use Iterator;

/**
 * A list of order items.
 *
 * Immutable.
 *
 * @implements Iterator<Order>
 */
class OrderList implements Iterator
{
    private int $position = 0;
    /** @var Order[] */
    private array $list;

    /**
     * @param Order[] $list
     */
    private function __construct(array $list)
    {
        foreach ($list as $item) {
            if (!$item instanceof Order) {
                throw new InvalidArgumentException();
            }
        }

        $this->list = $list;
    }

    /**
     * Create an instance.
     *
     * @param Order[] $list
     */
    public static function create(array $list): self
    {
        return new self($list);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): Order
    {
        return $this->list[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->list[$this->position]);
    }
}
