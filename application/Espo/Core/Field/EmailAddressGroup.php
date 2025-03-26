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
 * An email address group. Contains a list of email addresses. One email address is set as primary.
 * If not empty, then there always should be a primary address. Immutable.
 */
class EmailAddressGroup
{
    /** @var EmailAddress[] */
    private array $list = [];
    private ?EmailAddress $primary = null;

    /**
     * @param EmailAddress[] $list
     * @throws RuntimeException
     */
    public function __construct(array $list = [])
    {
        foreach ($list as $item) {
            $this->list[] = clone $item;
        }

        $this->validateList();

        if (count($this->list) !== 0) {
            $this->primary = $this->list[0];
        }
    }

    public function __clone()
    {
        $newList = [];

        foreach ($this->list as $item) {
            $newList[] = clone $item;
        }

        $this->list = $newList;

        if ($this->primary) {
            $this->primary = clone $this->primary;
        }
    }

    /**
     * Get a primary address as a string. If no primary, then returns null,
     */
    public function getPrimaryAddress(): ?string
    {
        $primary = $this->getPrimary();

        if (!$primary) {
            return null;
        }

        return $primary->getAddress();
    }

    /**
     * Get a primary email address.
     */
    public function getPrimary(): ?EmailAddress
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->primary;
    }

    /**
     * Get a list of all email addresses.
     *
     * @return EmailAddress[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * Get a number of addresses.
     */
    public function getCount(): int
    {
        return count($this->list);
    }

    /**
     * Get a list of email addresses w/o a primary.
     *
     * @return EmailAddress[]
     */
    public function getSecondaryList(): array
    {
        $list = [];

        foreach ($this->list as $item) {
            if ($item === $this->primary) {
                continue;
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * Get a list of email addresses represented as strings.
     *
     * @return string[]
     */
    public function getAddressList(): array
    {
        $list = [];

        foreach ($this->list as $item) {
            $list[] = $item->getAddress();
        }

        return $list;
    }

    /**
     * Get an email address by address represented as a string.
     */
    public function getByAddress(string $address): ?EmailAddress
    {
        $index = $this->searchAddressInList($address);

        if ($index === null) {
            return null;
        }

        return $this->list[$index];
    }

    /**
     * Whether an address is in the list.
     */
    public function hasAddress(string $address): bool
    {
        return in_array($address, $this->getAddressList());
    }

    /**
     * Clone with another primary email address.
     */
    public function withPrimary(EmailAddress $emailAddress): self
    {
        $list = $this->list;

        $index = $this->searchAddressInList($emailAddress->getAddress());

        if ($index !== null) {
            unset($list[$index]);

            $list = array_values($list);
        }

        $newList = array_merge([$emailAddress], $list);

        return self::create($newList);
    }

    /**
     * Clone with an added email address list.
     *
     * @param EmailAddress[] $list
     */
    public function withAddedList(array $list): self
    {
        $newList = $this->list;

        foreach ($list as $item) {
            $index = $this->searchAddressInList($item->getAddress());

            if ($index !== null) {
                $newList[$index] = $item;

                continue;
            }

            $newList[] = $item;
        }

        return self::create($newList);
    }

    /**
     * Clone with an added email address.
     */
    public function withAdded(EmailAddress $emailAddress): self
    {
        return $this->withAddedList([$emailAddress]);
    }

    /**
     * Clone with removed email address.
     */
    public function withRemoved(EmailAddress $emailAddress): self
    {
        return $this->withRemovedByAddress($emailAddress->getAddress());
    }

    /**
     * Clone with removed email address passed by an address.
     */
    public function withRemovedByAddress(string $address): self
    {
        $newList = $this->list;

        $index = $this->searchAddressInList($address);

        if ($index !== null) {
            unset($newList[$index]);

            $newList = array_values($newList);
        }

        return self::create($newList);
    }

    /**
     * Create with an optional email address list. A first item will be set as primary.
     *
     * @param EmailAddress[] $list
     */
    public static function create(array $list = []): self
    {
        return new self($list);
    }

    private function searchAddressInList(string $address): ?int
    {
        foreach ($this->getAddressList() as $i => $item) {
            if ($item === $address) {
                return $i;
            }
        }

        return null;
    }

    private function validateList(): void
    {
        $addressList = [];

        foreach ($this->list as $item) {
            if (!$item instanceof EmailAddress) {
                throw new RuntimeException("Bad item.");
            }

            if (in_array(strtolower($item->getAddress()), $addressList)) {
                throw new RuntimeException("Address list contains a duplicate.");
            }

            $addressList[] = strtolower($item->getAddress());
        }
    }

    private function isEmpty(): bool
    {
        return count($this->list) === 0;
    }
}
