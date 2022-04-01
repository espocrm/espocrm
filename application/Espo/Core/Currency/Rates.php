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

namespace Espo\Core\Currency;

use RuntimeException;

/**
 * Currency rates.
 */
class Rates
{
    private ?string $baseCode = null;

    /**
     * @var array<string,float>
     */
    private array $data = [];

    private function __construct(?string $baseCode = null)
    {
        $this->baseCode = $baseCode;
    }

    /**
     * Create an instance.
     *
     * @param ?string $baseCode A base-currency code.
     */
    public static function create(?string $baseCode = null): self
    {
        return new self($baseCode);
    }

    /**
     * Get a base-currency code.
     *
     * @throws RuntimeException If the base code is not set.
     */
    public function getBase(): string
    {
        if ($this->baseCode === null) {
            throw new RuntimeException("Base code is not set.");
        }

        return $this->baseCode;
    }

    /**
     * Clone with a rate value for a specific currency.
     */
    public function withRate(string $code, float $value): self
    {
        $obj = clone $this;
        $obj->data[$code] = $value;

        return $obj;
    }

    /**
     * Whether a rate is set for a specific currency.
     */
    public function hasRate(string $code): bool
    {
        return array_key_exists($code, $this->data);
    }

    /**
     * Get a rate value for a specific currency.
     */
    public function getRate(string $code): float
    {
        if (!$this->hasRate($code)) {
            throw new RuntimeException("No currency rate for '{$code}'.");
        }

        return $this->data[$code];
    }

    /**
     * @param array<string,float> $data
     */
    public static function fromArray(array $data, ?string $baseCode = null): self
    {
        $obj = new self($baseCode);
        $obj->data = $data;

        return $obj;
    }
}
