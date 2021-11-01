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

namespace Espo\ORM\Query\Part;

use Espo\ORM\Query\Part\{
    WhereItem,
    Where\AndGroup,
    Where\OrGroup,
    Where\Not,
    Where\Comparison,
};

use Espo\ORM\Query\Select;

/**
 * A util-class for creating items that can be used as a where-clause.
 */
class Condition
{
    private function __construct()
    {
    }

    /**
     * Create 'AND' group.
     */
    public static function and(WhereItem ...$itemList): AndGroup
    {
        return AndGroup::create(...$itemList);
    }

    /**
     * Create 'OR' group.
     */
    public static function or(WhereItem ...$itemList): OrGroup
    {
        return OrGroup::create(...$itemList);
    }

    /**
     * Create 'NOT'.
     */
    public static function not(WhereItem $item): Not
    {
        return Not::create($item);
    }

    /**
     * Create a column reference expression.
     *
     * @param string $expression Examples: `columnName`, `alias.columnName`.
     */
    public static function column(string $expression): Expression
    {
        return Expression::column($expression);
    }

    /**
     * Create '=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float|bool|null $arg2 A value (if scalar) or expression.
     */
    public static function equal(Expression $arg1, $arg2): Comparison
    {
        return Comparison::equal($arg1, $arg2);
    }

    /**
     * Create '!=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float|bool|null $arg2 A value (if scalar) or expression.
     */
    public static function notEqual(Expression $arg1, $arg2): Comparison
    {
        return Comparison::notEqual($arg1, $arg2);
    }

    /**
     * Create 'LIKE' comparison.
     *
     * @param Expression $subject What to test.
     * @param Expression|string $pattern A pattern.
     */
    public static function like(Expression $subject, $pattern): Comparison
    {
        return Comparison::like($subject, $pattern);
    }

    /**
     * Create 'NOT LIKE' comparison.
     *
     * @param Expression $subject What to test.
     * @param Expression|string $pattern A pattern.
     */
    public static function notLike(Expression $subject, $pattern): Comparison
    {
        return Comparison::notLike($subject, $pattern);
    }

    /**
     * Create '>' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     */
    public static function greater(Expression $arg1, $arg2): Comparison
    {
        return Comparison::greater($arg1, $arg2);
    }

    /**
     * Create '>=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     */
    public static function greaterOrEqual(Expression $arg1, $arg2): Comparison
    {
        return Comparison::greaterOrEqual($arg1, $arg2);
    }

    /**
     * Create '<' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     */
    public static function less(Expression $arg1, $arg2): Comparison
    {
        return Comparison::less($arg1, $arg2);
    }

    /**
     * Create '<=' comparison.
     *
     * @param Expression $arg1 An expression.
     * @param Expression|string|int|float $arg2 A value (if scalar) or expression.
     */
    public static function lessOrEqual(Expression $arg1, $arg2): Comparison
    {
        return Comparison::lessOrEqual($arg1, $arg2);
    }

    /**
     * Create 'IN' comparison.
     *
     * @param Expression $subject What to test.
     * @param Select|array $set A set of values. A select query or array of scalars.
     */
    public static function in(Expression $subject, $set): Comparison
    {
        return Comparison::in($subject, $set);
    }

    /**
     * Create 'NOT IN' comparison.
     *
     * @param Expression $subject What to test.
     * @param Select|array $set A set of values. A select query or array of scalars.
     */
    public static function notIn(Expression $subject, $set): Comparison
    {
        return Comparison::notIn($subject, $set);
    }
}
