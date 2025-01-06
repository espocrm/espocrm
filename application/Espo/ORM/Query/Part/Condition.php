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

use Espo\ORM\Query\Part\Where\AndGroup;
use Espo\ORM\Query\Part\Where\Comparison;
use Espo\ORM\Query\Part\Where\Exists;
use Espo\ORM\Query\Part\Where\Not;
use Espo\ORM\Query\Part\Where\OrGroup;

use Espo\ORM\Query\Select;

/**
 * A util-class for creating items that can be used as a where-clause.
 */
class Condition
{
    private function __construct()
    {}

    /**
     * Create 'AND' group.
     */
    public static function and(WhereItem ...$items): AndGroup
    {
        return AndGroup::create(...$items);
    }

    /**
     * Create 'OR' group.
     */
    public static function or(WhereItem ...$items): OrGroup
    {
        return OrGroup::create(...$items);
    }

    /**
     * Create 'NOT'.
     */
    public static function not(WhereItem $item): Not
    {
        return Not::create($item);
    }

    /**
     * Create `EXISTS`.
     */
    public static function exists(Select $subQuery): Exists
    {
        return Exists::create($subQuery);
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
     * @param Expression $argument1 An expression.
     * @param Expression|Select|string|int|float|bool|null $argument2 A scalar, expression or sub-query.
     */
    public static function equal(
        Expression $argument1,
        Expression|Select|string|int|float|bool|null $argument2
    ): Comparison {

        return Comparison::equal($argument1, $argument2);
    }

    /**
     * Create '!=' comparison.
     *
     * @param Expression $argument1 An expression.
     * @param Expression|Select|string|int|float|bool|null $argument2 A scalar, expression or sub-query.
     */
    public static function notEqual(
        Expression $argument1,
        Expression|Select|string|int|float|bool|null $argument2
    ): Comparison {

        return Comparison::notEqual($argument1, $argument2);
    }

    /**
     * Create 'LIKE' comparison.
     *
     * @param Expression $subject What to test.
     * @param Expression|string $pattern A pattern.
     */
    public static function like(Expression $subject, Expression|string $pattern): Comparison
    {
        return Comparison::like($subject, $pattern);
    }

    /**
     * Create 'NOT LIKE' comparison.
     *
     * @param Expression $subject What to test.
     * @param Expression|string $pattern A pattern.
     */
    public static function notLike(Expression $subject, Expression|string $pattern): Comparison
    {
        return Comparison::notLike($subject, $pattern);
    }

    /**
     * Create '>' comparison.
     *
     * @param Expression $argument1 An expression.
     * @param Expression|Select|string|int|float $argument2 A scalar, expression or sub-query.
     */
    public static function greater(
        Expression $argument1,
        Expression|Select|string|int|float $argument2
    ): Comparison {

        return Comparison::greater($argument1, $argument2);
    }

    /**
     * Create '>=' comparison.
     *
     * @param Expression $argument1 An expression.
     * @param Expression|Select|string|int|float $argument2 A scalar, expression or sub-query.
     */
    public static function greaterOrEqual(
        Expression $argument1,
        Expression|Select|string|int|float $argument2
    ): Comparison {

        return Comparison::greaterOrEqual($argument1, $argument2);
    }

    /**
     * Create '<' comparison.
     *
     * @param Expression $argument1 An expression.
     * @param Expression|Select|string|int|float $argument2 A scalar, expression or sub-query.
     */
    public static function less(
        Expression $argument1,
        Expression|Select|string|int|float $argument2
    ): Comparison {

        return Comparison::less($argument1, $argument2);
    }

    /**
     * Create '<=' comparison.
     *
     * @param Expression $argument1 An expression.
     * @param Expression|Select|string|int|float $argument2 A scalar, expression or sub-query.
     */
    public static function lessOrEqual(
        Expression $argument1,
        Expression|Select|string|int|float $argument2
    ): Comparison {

        return Comparison::lessOrEqual($argument1, $argument2);
    }

    /**
     * Create 'IN' comparison.
     *
     * @param Expression $subject What to test.
     * @param Select|scalar[] $set A set of values. A select query or array of scalars.
     */
    public static function in(Expression $subject, Select|array $set): Comparison
    {
        return Comparison::in($subject, $set);
    }

    /**
     * Create 'NOT IN' comparison.
     *
     * @param Expression $subject What to test.
     * @param Select|scalar[] $set A set of values. A select query or array of scalars.
     */
    public static function notIn(Expression $subject, Select|array $set): Comparison
    {
        return Comparison::notIn($subject, $set);
    }
}
