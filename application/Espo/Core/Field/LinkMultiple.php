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

namespace Espo\Core\Field;

use RuntimeException;

/**
 * A link-multiple value object. Immutable.
 */
class LinkMultiple
{
    /**
     * @param LinkMultipleItem[] $list
     * @throws RuntimeException
     */
    public function __construct(private array $list = [])
    {
        $this->validateList();
    }

    public function __clone()
    {
        $newList = [];

        foreach ($this->list as $item) {
            $newList[] = clone $item;
        }

        $this->list = $newList;
    }

    /**
     * Whether contains a specific ID.
     */
    public function hasId(string $id): bool
    {
        return $this->searchIdInList($id) !== null;
    }

    /**
     * Get a list of IDs.
     *
     * @return string[]
     */
    public function getIdList(): array
    {
        $idList = [];

        foreach ($this->list as $item) {
            $idList[] = $item->getId();
        }

        return $idList;
    }

    /**
     * Get a list of items.
     *
     * @return LinkMultipleItem[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * Get a number of items.
     */
    public function getCount(): int
    {
        return count($this->list);
    }

    /**
     * Get item by ID.
     */
    public function getById(string $id): ?LinkMultipleItem
    {
        foreach ($this->list as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Clone with an added ID.
     */
    public function withAddedId(string $id): self
    {
        return $this->withAdded(LinkMultipleItem::create($id));
    }

    /**
     * Clone with an added IDs.
     *
     * @param string[] $idList IDs.
     */
    public function withAddedIdList(array $idList): self
    {
        $obj = $this;

        foreach ($idList as $id) {
            $obj = $obj->withAddedId($id);
        }

        return $obj;
    }

    /**
     * Clone with an added item.
     */
    public function withAdded(LinkMultipleItem $item): self
    {
        return $this->withAddedList([$item]);
    }

    /**
     * Clone with an added item list.
     * .
     * @param LinkMultipleItem[] $list
     *
     * @throws RuntimeException
     */
    public function withAddedList(array $list): self
    {
        $newList = $this->list;

        foreach ($list as $item) {
            $index = $this->searchIdInList($item->getId());

            if ($index !== null) {
                $newList[$index] = $item;

                continue;
            }

            $newList[] = $item;
        }

        return self::create($newList);
    }

    /**
     * Clone with removed item.
     */
    public function withRemoved(LinkMultipleItem $item): self
    {
        return $this->withRemovedById($item->getId());
    }

    /**
     * Clone with removed item by ID.
     */
    public function withRemovedById(string $id): self
    {
        $newList = $this->list;

        $index = $this->searchIdInList($id);

        if ($index !== null) {
            unset($newList[$index]);

            $newList = array_values($newList);
        }

        return self::create($newList);
    }

    /**
     * Create with an optional item list.
     *
     * @param LinkMultipleItem[] $list
     *
     * @throws RuntimeException
     */
    public static function create(array $list = []): self
    {
        return new self($list);
    }

    private function validateList(): void
    {
        $idList = [];

        foreach ($this->list as $item) {
            if (!$item instanceof LinkMultipleItem) {
                throw new RuntimeException("Bad item.");
            }

            if (in_array($item->getId(), $idList)) {
                throw new RuntimeException("List contains duplicates.");
            }

            $idList[] = strtolower($item->getId());
        }
    }

    private function searchIdInList(string $id): ?int
    {
        foreach ($this->getIdList() as $i => $item) {
            if ($item === $id) {
                return $i;
            }
        }

        return null;
    }
}
