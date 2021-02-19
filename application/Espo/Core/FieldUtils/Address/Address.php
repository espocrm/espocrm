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

namespace Espo\Core\FieldUtils\Address;

/**
 * An address value.
 */
class Address
{
    protected $street = null;

    protected $city = null;

    protected $country = null;

    protected $state = null;

    protected $postalCode = null;

    public function hasStreet() : bool
    {
        return $this->street !== null;
    }

    public function hasCity() : bool
    {
        return $this->city !== null;
    }

    public function hasCountry() : bool
    {
        return $this->country !== null;
    }

    public function hasState() : bool
    {
        return $this->state !== null;
    }

    public function hasPostalCode() : bool
    {
        return $this->postalCode !== null;
    }

    public function getStreet() : ?string
    {
        return $this->street;
    }

    public function getCity() : ?string
    {
        return $this->city;
    }

    public function getCountry() : ?string
    {
        return $this->country;
    }

    public function getState() : ?string
    {
        return $this->state;
    }

    public function getPostalCode() : ?string
    {
        return $this->postalCode;
    }

    public function withStreet(?string $street) : self
    {
        $newAddress = self::createBuilder()
            ->clone($this)
            ->setStreet($street)
            ->build();

        return $newAddress;
    }

    public function withCity(?string $city) : self
    {
        $newAddress = self::createBuilder()
            ->clone($this)
            ->setCity($city)
            ->build();

        return $newAddress;
    }

    public function withCountry(?string $country) : self
    {
        $newAddress = self::createBuilder()
            ->clone($this)
            ->setCountry($country)
            ->build();

        return $newAddress;
    }

    public function withState(?string $state) : self
    {
        $newAddress = self::createBuilder()
            ->clone($this)
            ->setState($state)
            ->build();

        return $newAddress;
    }

    public function withPostalCode(?string $postalCode) : self
    {
        $newAddress = self::createBuilder()
            ->clone($this)
            ->setPostalCode($postalCode)
            ->build();

        return $newAddress;
    }

    public static function fromRaw(array $raw) : self
    {
        $obj = new self();

        $obj->street = $raw['street'] ?? null;
        $obj->city = $raw['city'] ?? null;
        $obj->country = $raw['country'] ?? null;
        $obj->state = $raw['state'] ?? null;
        $obj->postalCode = $raw['postalCode'] ?? null;

        return $obj;
    }

    public static function createBuilder() : AddressBuilder
    {
        return new AddressBuilder();
    }
}
