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

use FILTER_VALIDATE_EMAIL;

/**
 * An email address value. Immutable.
 */
class EmailAddress
{
    private string $address;
    private bool $isOptedOut = false;
    private bool $isInvalid = false;

    public function __construct(string $address)
    {
        if ($address === '') {
            throw new RuntimeException("Empty email address.");
        }

        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Not valid email address '{$address}'.");
        }

        $this->address = $address;
    }

    /**
     * Get an address.
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Whether opted-out.
     */
    public function isOptedOut(): bool
    {
        return $this->isOptedOut;
    }

    /**
     * Whether invalid.
     */
    public function isInvalid(): bool
    {
        return $this->isInvalid;
    }

    /**
     * Clone set invalid.
     */
    public function invalid(): self
    {
        $obj = $this->clone();

        $obj->isInvalid = true;

        return $obj;
    }

    /**
     * Clone set not invalid.
     */
    public function notInvalid(): self
    {
        $obj = $this->clone();

        $obj->isInvalid = false;

        return $obj;
    }

    /**
     * Clone set opted-out.
     */
    public function optedOut(): self
    {
        $obj = $this->clone();

        $obj->isOptedOut = true;

        return $obj;
    }

    /**
     * Clone set not opted-out.
     */
    public function notOptedOut(): self
    {
        $obj = $this->clone();

        $obj->isOptedOut = false;

        return $obj;
    }

    /**
     * Create from an address.
     */
    public static function create(string $address): self
    {
        return new self($address);
    }

    private function clone(): self
    {
        $obj = new self($this->address);

        $obj->isInvalid = $this->isInvalid;
        $obj->isOptedOut = $this->isOptedOut;

        return $obj;
    }
}
