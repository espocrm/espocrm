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

namespace Espo\ORM\Query\Part\Where;

use Espo\ORM\Query\{
    Part\WhereItem,
    Part\Expression,
    Select,
};

use RuntimeException;
use InvalidArgumentException;

/**
 * Compares an expression to a value or another expression. Immutable.
 */
class Comparison implements WhereItem
{
    private const OPERATOR_EQUAL = '=';

    private const OPERATOR_NOT_EQUAL = '!=';

    private const OPERATOR_GREATER = '>';

    private const OPERATOR_GREATER_OR_EQUAL = '>=';

    private const OPERATOR_LESS = '<';

    private const OPERATOR_LESS_OR_EQUAL = '<=';

    private const OPERATOR_LIKE = '*';

    private const OPERATOR_NOT_LIKE = '!*';

    private const OPERATOR_IN_SUB_QUERY = '=s';

    private const OPERATOR_NOT_IN_SUB_QUERY = '!=s';

    private $rawKey;

    private $rawValue;

    private function __construct(string $rawKey, $rawValue)
    {
        $this->rawKey = $rawKey;
        $this->rawValue = $rawValue;
    }

    public function getRaw(): array
    {
        return [$this->rawKey => $this->rawValue];
    }

    public function getRawKey(): string
    {
        return $this->rawKey;
    }

    /**
     * @return mixed
     */
    public function getRawValue()
    {
        return $this->rawValue;
    }

    /**
     * Create '=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float|bool|null $arg2 A value (if scalar) or expression.
     * @return self
     */
    public static function equal(Expression $arg1, $arg2): self
    {
        return self::createComparison(self::OPERATOR_EQUAL, $arg1, $arg2);
    }

    /**
     * Create '!=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float|bool|null $arg2 A value (if scalar) or expression.
     * @return self
     */
    public static function notEqual(Expression $arg1, $arg2): self
    {
        return self::createComparison(self::OPERATOR_NOT_EQUAL, $arg1, $arg2);
    }

    /**
     * Create 'LIKE' comparison.
     *
     * @param Expression $subject What to test.
     * @param Expression|string $pattern A pattern.
     * @return self
     */
    public static function like(Expression $subject, $pattern): self
    {
        return self::createComparison(self::OPERATOR_LIKE, $subject, $pattern);
    }

    /**
     * Create 'NOT LIKE' comparison.
     *
     * @param Expression $subject What to test.
     * @param Expression|string $pattern A pattern.
     * @return self
     */
    public static function notLike(Expression $subject, $pattern): self
    {
        return self::createComparison(self::OPERATOR_NOT_LIKE, $subject, $pattern);
    }

    /**
     * Create '>' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     * @return self
     */
    public static function greater(Expression $arg1, $arg2): self
    {
        return self::createComparison(self::OPERATOR_GREATER, $arg1, $arg2);
    }

    /**
     * Create '>=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     * @return self
     */
    public static function greaterOrEqual(Expression $arg1, $arg2): self
    {
        return self::createComparison(self::OPERATOR_GREATER_OR_EQUAL, $arg1, $arg2);
    }

    /**
     * Create '<' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     * @return self
     */
    public static function less(Expression $arg1, $arg2): self
    {
        return self::createComparison(self::OPERATOR_LESS, $arg1, $arg2);
    }

    /**
     * Create '<=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     * @return self
     */
    public static function lessOrEqual(Expression $arg1, $arg2): self
    {
        return self::createComparison(self::OPERATOR_LESS_OR_EQUAL, $arg1, $arg2);
    }

    /**
     * Create 'IN' comparison.
     *
     * @param Expression $subject What to test.
     * @param Select|array $set A set of values. A select query or array of scalars.
     * @return self
     */
    public static function in(Expression $subject, $set): self
    {
        if ($set instanceof Select) {
            return self::createInOrNotInSubQuery(self::OPERATOR_IN_SUB_QUERY, $subject, $set);
        }

        return self::createInOrNotInArray(self::OPERATOR_EQUAL, $subject, $set);
    }

    /**
     * Create 'NOT IN' comparison.
     *
     * @param Expression $subject What to test.
     * @param Select|array $set A set of values. A select query or array of scalars.
     * @return self
     */
    public static function notIn(Expression $subject, $set): self
    {
        if ($set instanceof Select) {
            return self::createInOrNotInSubQuery(self::OPERATOR_NOT_IN_SUB_QUERY, $subject, $set);
        }

        return self::createInOrNotInArray(self::OPERATOR_NOT_EQUAL, $subject, $set);
    }

    /**
     * @param Expression|string $arg1
     * @param Expression|string|int|float|bool|null $arg2
     */
    private static function createComparison(string $operator, $arg1, $arg2): self
    {
        /** @phpstan-var mixed $arg1 */
        /** @phpstan-var mixed $arg2 */

        if (is_string($arg1)) {
            $key = $arg1;

            if ($key === '') {
                throw new RuntimeException("Expression can't be empty.");
            }
        }
        else if ($arg1 instanceof Expression) {
            $key = $arg1->getValue();
        }
        else {
            throw new InvalidArgumentException("First argument must be Expression or string.");
        }

        if (substr($key, -1) === ':') {
            throw new RuntimeException("Expression should not end with `:`.");
        }

        $key .= $operator;

        if ($arg2 instanceof Expression) {
            $key .= ':';

            $value = $arg2->getValue();
        }
        else if (!is_scalar($arg2) && !is_null($arg2)) {
            throw new InvalidArgumentException("Second argument must be scalar or Expression.");
        }
        else {
            $value = $arg2;
        }

        return new self($key, $value);
    }

    /**
     * @param Expression|string $arg1
     */
    private static function createInOrNotInArray(string $operator, $arg1, array $valueList): self
    {
        foreach ($valueList as $item) {
            if (!is_scalar($item)) {
                throw new RuntimeException("Array items must be scalar.");
            }
        }

        /** @phpstan-var mixed $arg1 */

        if (is_string($arg1)) {
            $key = $arg1;

            if ($key === '') {
                throw new RuntimeException("Expression can't be empty.");
            }

            if (substr($key, -1) === ':') {
                throw new RuntimeException("Expression can't end with `:`.");
            }
        }
        else if ($arg1 instanceof Expression) {
            $key = $arg1->getValue();
        }
        else {
            throw new InvalidArgumentException("First argument must be Expression or string.");
        }

        $key .= $operator;

        return new self($key, $valueList);
    }

    /**
     * @param Expression|string $arg1
     */
    private static function createInOrNotInSubQuery(string $operator, $arg1, Select $query): self
    {
        /** @phpstan-var mixed $arg1 */

        if (is_string($arg1)) {
            $key = $arg1;

            if ($key === '') {
                throw new RuntimeException("Expression can't be empty.");
            }

            if (substr($key, -1) === ':') {
                throw new RuntimeException("Expression can't end with `:`.");
            }
        }
        else if ($arg1 instanceof Expression) {
            $key = $arg1->getValue();
        }
        else {
            throw new InvalidArgumentException("First argument must be Expression or string.");
        }

        $key .= $operator;

        return new self($key, $query->getRaw());
    }
}
