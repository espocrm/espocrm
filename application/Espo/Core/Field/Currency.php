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

use Espo\Core\Currency\CalculatorUtil;

use RuntimeException;
use InvalidArgumentException;

/**
 * A currency value object. Immutable.
 */
class Currency
{
    /** @var numeric-string */
    private string $amount;
    private string $code;

    /**
     * @param numeric-string|float $amount An amount.
     * @param string $code A currency code.
     * @throws RuntimeException
     */
    public function __construct($amount, string $code)
    {
        if (!is_string($amount) && !is_float($amount)) {
            throw new InvalidArgumentException();
        }

        if (strlen($code) !== 3) {
            throw new RuntimeException("Bad currency code.");
        }

        if (is_float($amount)) {
            $amount = (string) $amount;
        }

        $this->amount = $amount;
        $this->code = $code;
    }

    /**
     * Get an amount as string.
     *
     * @return numeric-string
     */
    public function getAmountAsString(): string
    {
        return $this->amount;
    }

    /**
     * Get an amount.
     */
    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    /**
     * Get a currency code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Add a currency value.
     *
     * @throws RuntimeException If currency codes are different.
     */
    public function add(self $value): self
    {
        if ($this->getCode() !== $value->getCode()) {
            throw new RuntimeException("Can't add a currency value with a different code.");
        }

        $amount = CalculatorUtil::add(
            $this->getAmountAsString(),
            $value->getAmountAsString()
        );

        return new self($amount, $this->getCode());
    }

    /**
     * Subtract a currency value.
     *
     * @throws RuntimeException If currency codes are different.
     */
    public function subtract(self $value): self
    {
        if ($this->getCode() !== $value->getCode()) {
            throw new RuntimeException("Can't subtract a currency value with a different code.");
        }

        $amount = CalculatorUtil::subtract(
            $this->getAmountAsString(),
            $value->getAmountAsString()
        );

        return new self($amount, $this->getCode());
    }

    /**
     * Multiply by a multiplier.
     */
    public function multiply(float|int $multiplier): self
    {
        $amount = CalculatorUtil::multiply(
            $this->getAmountAsString(),
            (string) $multiplier
        );

        return new self($amount, $this->getCode());
    }

    /**
     * Divide by a divider.
     */
    public function divide(float|int $divider): self
    {
        $amount = CalculatorUtil::divide(
            $this->getAmountAsString(),
            (string) $divider
        );

        return new self($amount, $this->getCode());
    }

    /**
     * Round with a precision.
     */
    public function round(int $precision = 0): self
    {
        $amount = CalculatorUtil::round($this->getAmountAsString(), $precision);

        return new self($amount, $this->getCode());
    }

    /**
     * Compare with another currency value. Returns:
     * - `1` if greater than the value;
     * - `0` if equal to the value;
     * - `-1` if less than the value.
     *
     * @throws RuntimeException If currency codes are different.
     */
    public function compare(self $value): int
    {
        if ($this->getCode() !== $value->getCode()) {
            throw new RuntimeException("Can't compare currencies with different codes.");
        }

        return CalculatorUtil::compare(
            $this->getAmountAsString(),
            $value->getAmountAsString()
        );
    }

    /**
     * Check whether the value is negative.
     */
    public function isNegative(): bool
    {
        return $this->compare(self::create(0.0, $this->code)) === -1;
    }

    /**
     * Create from an amount and code.
     *
     * @param numeric-string|float $amount An amount.
     * @param string $code A currency code.
     * @throws RuntimeException
     */
    public static function create($amount, string $code): self
    {
        return new self($amount, $code);
    }
}
