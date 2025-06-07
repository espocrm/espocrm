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

use Espo\ORM\Query\Part\Expression\Util;

use RuntimeException;

/**
 * A complex expression. Can be a function or a simple column reference. Immutable.
 */
class Expression implements WhereItem
{
    private string $expression;

    public function __construct(string $expression)
    {
        if ($expression === '') {
            throw new RuntimeException("Expression can't be empty.");
        }

        if (str_ends_with($expression, ':')) {
            throw new RuntimeException("Expression should not end with `:`.");
        }

        $this->expression = $expression;
    }

    public function getRaw(): array
    {
        return [$this->getRawKey() => null];
    }

    public function getRawKey(): string
    {
        return $this->expression . ':';
    }

    public function getRawValue(): mixed
    {
        return null;
    }

    /**
     * Get a string expression.
     */
    public function getValue(): string
    {
        return $this->expression;
    }

    /**
     * Create an expression from a string.
     */
    public static function create(string $expression): self
    {
        return new self($expression);
    }

    /**
     * Create an expression from a scalar value or NULL.
     *
     * @param string|float|int|bool|null $value A scalar or NULL.
     */
    public static function value(string|float|int|bool|null $value): self
    {
        return self::create(self::stringifyArgument($value));
    }

    /**
     * Create a column reference expression.
     *
     * @param string $expression Examples: `columnName`, `alias.columnName`.
     */
    public static function column(string $expression): self
    {
        $string = $expression;

        if (strlen($string) && $string[0] === '@') {
            $string = substr($string, 1);
        }

        if ($string === '') {
            throw new RuntimeException("Empty column.");
        }

        if (!preg_match('/^[a-zA-Z\d.]+$/', $string)) {
            throw new RuntimeException("Bad column. Must be of letters, digits. Can have a dot.");
        }

        return self::create($expression);
    }

    /**
     * Create an alias reference expression.
     *
     * @param string $expression Examples: `someAlias`, `subQueryAlias.someAlias`.
     * @since 8.1.0
     */
    public static function alias(string $expression): self
    {
        if ($expression === '') {
            throw new RuntimeException("Empty alias.");
        }

        if (!preg_match('/^[a-zA-Z\d.]+$/', $expression)) {
            throw new RuntimeException("Bad alias expression. Must be of letters, digits. Can have a dot.");
        }

        if (str_contains($expression, '.')) {
            [$left, $right] = explode('.', $expression, 2);

            return self::create($left . '.#' . $right);
        }

        return self::create('#' . $expression);
    }

    /**
     * 'COUNT' function.
     *
     * @param Expression $expression
     */
    public static function count(Expression $expression): self
    {
        return self::composeFunction('COUNT', $expression);
    }

    /**
     * 'MIN' function.
     *
     * @param Expression $expression
     */
    public static function min(Expression $expression): self
    {
        return self::composeFunction('MIN', $expression);
    }

    /**
     * 'MAX' function.
     *
     * @param Expression $expression
     */
    public static function max(Expression $expression): self
    {
        return self::composeFunction('MAX', $expression);
    }

    /**
     * 'SUM' function.
     *
     * @param Expression $expression
     */
    public static function sum(Expression $expression): self
    {
        return self::composeFunction('SUM', $expression);
    }

    /**
     * 'AVG' function.
     *
     * @param Expression $expression
     */
    public static function average(Expression $expression): self
    {
        return self::composeFunction('AVG', $expression);
    }

    /**
     * 'IF' function. Return $then if a condition is true, $else otherwise.
     *
     * @param Expression $condition A condition.
     * @param Expression|string|int|float|bool|null $then Then.
     * @param Expression|string|int|float|bool|null $else Else.
     */
    public static function if(
        Expression $condition,
        Expression|string|int|float|bool|null $then,
        Expression|string|int|float|bool|null $else
    ): self {

        return self::composeFunction('IF', $condition, $then, $else);
    }

    /**
     * 'CASE' expression. Even arguments define 'WHEN' conditions, following odd arguments
     * define 'THEN' values. The last unmatched argument defines the 'ELSE' value.
     *
     * @param Expression|scalar|null ...$arguments Arguments.
     */
    public static function switch(Expression|string|int|float|bool|null ...$arguments): self
    {
        if (count($arguments) < 2) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('SWITCH', ...$arguments);
    }

    /**
     * 'CASE' expression that maps keys to values. The first argument is the value to map.
     * Odd arguments define keys, the following even arguments define mapped values.
     * The last unmatched argument defines the 'ELSE' value.
     *
     * @param Expression|scalar|null ...$arguments Arguments.
     */
    public static function map(Expression|string|int|float|bool|null ...$arguments): self
    {
        if (count($arguments) < 3) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('MAP', ...$arguments);
    }

    /**
     * 'IFNULL' function. If the first argument is not NULL, returns it,
     * otherwise returns the second argument.
     *
     * @param Expression $value A value.
     * @param Expression|string|int|float|bool $fallbackValue A fallback value.
     */
    public static function ifNull(Expression $value, Expression|string|int|float|bool $fallbackValue): self
    {
        return self::composeFunction('IFNULL', $value, $fallbackValue);
    }

    /**
     * 'NULLIF' function. If $arg1 = $arg2, returns NULL,
     * otherwise returns the first argument.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function nullIf(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('NULLIF', $argument1, $argument2);
    }

    /**
     * 'LIKE' operator.
     *
     * Example: `like(Expression:column('test'), 'test%'`.
     *
     * @param Expression $subject A subject.
     * @param Expression|string $pattern A pattern.
     */
    public static function like(Expression $subject, Expression|string $pattern): self
    {
        return self::composeFunction('LIKE', $subject, $pattern);
    }

    /**
     * '=' operator.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function equal(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('EQUAL', $argument1, $argument2);
    }

    /**
     * '<>' operator.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function notEqual(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('NOT_EQUAL', $argument1, $argument2);
    }

    /**
     * '>' operator.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function greater(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('GREATER_THAN', $argument1, $argument2);
    }

    /**
     * '<' operator.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function less(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('LESS_THAN', $argument1, $argument2);
    }

    /**
     * '>=' operator.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function greaterOrEqual(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('GREATER_THAN_OR_EQUAL', $argument1, $argument2);
    }

    /**
     * '<=' operator.
     *
     * @param Expression|string|int|float|bool $argument1
     * @param Expression|string|int|float|bool $argument2
     */
    public static function lessOrEqual(
        Expression|string|int|float|bool $argument1,
        Expression|string|int|float|bool $argument2
    ): self {

        return self::composeFunction('LESS_THAN_OR_EQUAL', $argument1, $argument2);
    }

    /**
     * 'IS NULL' operator.
     *
     * @param Expression $expression
     */
    public static function isNull(Expression $expression): self
    {
        return self::composeFunction('IS_NULL', $expression);
    }

    /**
     * 'IS NOT NULL' operator.
     *
     * @param Expression $expression
     */
    public static function isNotNull(Expression $expression): self
    {
        return self::composeFunction('IS_NOT_NULL', $expression);
    }

    /**
     * 'IN' operator. Check whether a value is within a set of values.
     *
     * @param Expression $expression
     * @param Expression[]|string[]|int[]|float[]|bool[] $values
     */
    public static function in(Expression $expression, array $values): self
    {
        return self::composeFunction('IN', $expression, ...$values);
    }

    /**
     * 'NOT IN' operator. Check whether a value is not within a set of values.
     *
     * @param Expression $expression
     * @param Expression[]|string[]|int[]|float[]|bool[] $values
     */
    public static function notIn(Expression $expression, array $values): self
    {
        return self::composeFunction('NOT_IN', $expression, ...$values);
    }

    /**
     * 'COALESCE' function. Returns the first non-NULL value in the list.
     */
    public static function coalesce(Expression ...$expressions): self
    {
        return self::composeFunction('COALESCE', ...$expressions);
    }

    /**
     * 'MONTH' function. Returns a month number of a passed date or date-time.
     *
     * @param Expression $date
     */
    public static function month(Expression $date): self
    {
        return self::composeFunction('MONTH_NUMBER', $date);
    }

    /**
     * 'WEEK' function. Returns a week number of a passed date or date-time.
     *
     * @param Expression $date
     * @param int $weekStart A week start. `0` for Sunday, `1` for Monday.
     */
    public static function week(Expression $date, int $weekStart = 0): self
    {
        if ($weekStart !== 0 && $weekStart !== 1) {
            throw new RuntimeException("Week start can be only 0 or 1.");
        }

        if ($weekStart === 1) {
            return self::composeFunction('WEEK_NUMBER_1', $date);
        }

        return self::composeFunction('WEEK_NUMBER', $date);
    }

    /**
     * 'DAYOFWEEK' function. A day of week of a passed date or date-time. 1..7.
     *
     * @param Expression $date
     */
    public static function dayOfWeek(Expression $date): self
    {
        return self::composeFunction('DAYOFWEEK', $date);
    }

    /**
     * 'DAYOFMONTH' function. A day of month of a passed date or date-time. 1..31.
     *
     * @param Expression $date
     */
    public static function dayOfMonth(Expression $date): self
    {
        return self::composeFunction('DAYOFMONTH', $date);
    }

    /**
     * 'YEAR' function. A year number of a passed date or date-time.
     *
     * @param Expression $date
     */
    public static function year(Expression $date): self
    {
        return self::composeFunction('YEAR', $date);
    }

    /**
     * 'YEAR' function taking into account a fiscal year start.
     *
     * @param Expression $date
     * @param int $fiscalYearStart A month number of a fiscal year start. 1..12.
     */
    public static function yearFiscal(Expression $date, int $fiscalYearStart = 1): self
    {
        if ($fiscalYearStart < 1 || $fiscalYearStart > 12) {
            throw new RuntimeException("Bad fiscal year start.");
        }

        return self::composeFunction('YEAR_' . strval($fiscalYearStart), $date);
    }

    /**
     * 'QUARTER' function. A quarter number of a passed date or date-time. 1..4.
     *
     * @param Expression $date
     */
    public static function quarter(Expression $date): self
    {
        return self::composeFunction('QUARTER_NUMBER', $date);
    }

    /**
     * 'HOUR' function. A hour number of a passed date-time. 0..23.
     *
     * @param Expression $dateTime
     */
    public static function hour(Expression $dateTime): self
    {
        return self::composeFunction('HOUR', $dateTime);
    }

    /**
     * 'MINUTE' function. A minute number of a passed date-time. 0..59.
     *
     * @param Expression $dateTime
     */
    public static function minute(Expression $dateTime): self
    {
        return self::composeFunction('MINUTE', $dateTime);
    }

    /**
     * 'SECOND' function. A second number of a passed date-time. 0..59.
     *
     * @param Expression $dateTime
     */
    public static function second(Expression $dateTime): self
    {
        return self::composeFunction('SECOND', $dateTime);
    }

    /**
     * 'UNIX_TIMESTAMP' function. Seconds.
     *
     * @param Expression $dateTime
     * @since 9.0.0
     */
    public static function unixTimestamp(Expression $dateTime): self
    {
        return self::composeFunction('UNIX_TIMESTAMP', $dateTime);
    }

    /**
     * 'NOW' function. A current date and time.
     */
    public static function now(): self
    {
        return self::composeFunction('NOW');
    }

    /**
     * 'DATE' function. Returns a date part of a date-time.
     *
     * @param Expression $dateTime
     */
    public static function date(Expression $dateTime): self
    {
        return self::composeFunction('DATE', $dateTime);
    }

    /**
     * Time zone conversion function. Converts a passed data-time applying a hour offset.
     *
     * @param Expression $date
     */
    public static function convertTimezone(Expression $date, float $offset): self
    {
        return self::composeFunction('TZ', $date, $offset);
    }

    /**
     * 'CONCAT' function. Concatenates multiple strings.
     *
     * @param Expression|string ...$strings Strings.
     */
    public static function concat(Expression|string ...$strings): self
    {
        return self::composeFunction('CONCAT', ...$strings);
    }

    /**
     * 'LEFT' function. Returns a specified number of characters from the left of a string.
     */
    public static function left(Expression $string, int $offset): self
    {
        return self::composeFunction('LEFT', $string, $offset);
    }

    /**
     * 'LOWER' function. Converts a string to a lower case.
     */
    public static function lowerCase(Expression $string): self
    {
        return self::composeFunction('LOWER', $string);
    }

    /**
     * 'UPPER' function. Converts a string to an upper case.
     */
    public static function upperCase(Expression $string): self
    {
        return self::composeFunction('UPPER', $string);
    }

    /**
     * 'TRIM' function. Removes leading and trailing spaces.
     */
    public static function trim(Expression $string): self
    {
        return self::composeFunction('TRIM', $string);
    }

    /**
     * 'BINARY' function. Converts a string value to a binary string.
     */
    public static function binary(Expression $string): self
    {
        return self::composeFunction('BINARY', $string);
    }

    /**
     * 'CHAR_LENGTH' function. A number of characters in a string.
     */
    public static function charLength(Expression $string): self
    {
        return self::composeFunction('CHAR_LENGTH', $string);
    }

    /**
     * 'REPLACE' function. Replaces all the occurrences of a sub-string within a string.
     *
     * @param Expression $haystack A subject.
     * @param Expression|string $needle A string to be replaced.
     * @param Expression|string $replaceWith A string to replace with.
     */
    public static function replace(
        Expression $haystack,
        Expression|string $needle,
        Expression|string $replaceWith
    ): self {

        return self::composeFunction('REPLACE', $haystack, $needle, $replaceWith);
    }

    /**
     * 'FIELD' operator (in MySQL). Returns an index (position) of an expression
     * in a list. Returns `0` if not found. The first index is `1`.
     *
     * @param Expression $expression
     * @param Expression[]|string[]|int[]|float[] $list
     */
    public static function positionInList(Expression $expression, array $list): self
    {
        return self::composeFunction('POSITION_IN_LIST', $expression, ...$list);
    }

    /**
     * 'ADD' function. Adds two or more numbers.
     *
     * @param Expression|int|float ...$arguments
     */
    public static function add(Expression|int|float ...$arguments): self
    {
        if (count($arguments) < 2) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('ADD', ...$arguments);
    }

    /**
     * 'SUB' function. Subtraction.
     *
     * @param Expression|int|float ...$arguments
     */
    public static function subtract(Expression|int|float ...$arguments): self
    {
        if (count($arguments) < 2) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('SUB', ...$arguments);
    }

    /**
     * 'MUL' function. Multiplication.
     *
     * @param Expression|int|float ...$arguments
     */
    public static function multiply(Expression|int|float ...$arguments): self
    {
        if (count($arguments) < 2) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('MUL', ...$arguments);
    }

    /**
     * 'DIV' function. Division.
     *
     * @param Expression|int|float ...$arguments
     */
    public static function divide(Expression|int|float ...$arguments): self
    {
        if (count($arguments) < 2) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('DIV', ...$arguments);
    }

    /**
     * 'MOD' function. Returns a remainder of a number divided by another number.
     *
     * @param Expression|int|float ...$arguments
     */
    public static function modulo(Expression|int|float ...$arguments): self
    {
        if (count($arguments) < 2) {
            throw new RuntimeException("Too few arguments.");
        }

        return self::composeFunction('MOD', ...$arguments);
    }

    /**
     * 'FLOOR' function. The largest integer value not greater than the argument.
     */
    public static function floor(Expression $number): self
    {
        return self::composeFunction('FLOOR', $number);
    }

    /**
     * 'CEIL' function. The largest integer value not greater than the argument.
     */
    public static function ceil(Expression $number): self
    {
        return self::composeFunction('CEIL', $number);
    }

    /**
     * 'ROUND' function. Rounds a number to a specified number of decimal places.
     */
    public static function round(Expression $number, int $precision = 0): self
    {
        return self::composeFunction('ROUND', $number, $precision);
    }

    /**
     * 'GREATEST' function. A max value from a list of expressions.
     */
    public static function greatest(Expression ...$arguments): self
    {
        return self::composeFunction('GREATEST', ...$arguments);
    }

    /**
     * 'LEAST' function. A min value from a list of expressions.
     */
    public static function least(Expression ...$arguments): self
    {
        return self::composeFunction('LEAST', ...$arguments);
    }

    /**
     * 'ANY_VALUE' function.
     *
     * @since 9.1.6
     */
    public function anyValue(Expression $expression): self
    {
        return self::composeFunction('ANY_VALUE', $expression);
    }

    /**
     * 'AND' operator. Returns TRUE if all arguments are TRUE.
     */
    public static function and(Expression ...$arguments): self
    {
        return self::composeFunction('AND', ...$arguments);
    }

    /**
     * 'OR' operator. Returns TRUE if at least one argument is TRUE.
     */
    public static function or(Expression ...$arguments): self
    {
        return self::composeFunction('OR', ...$arguments);
    }

    /**
     * 'NOT' operator. Negates an expression.
     */
    public static function not(Expression $argument): self
    {
        return self::composeFunction('NOT', $argument);
    }

    /**
     * 'ROW' constructor.
     */
    public static function row(Expression ...$arguments): self
    {
        return self::composeFunction('ROW', ...$arguments);
    }

    private static function composeFunction(
        string $function,
        Expression|bool|int|float|string|null ...$arguments
    ): self {

        return Util::composeFunction($function, ...$arguments);
    }

    private static function stringifyArgument(Expression|bool|int|float|string|null $argument): string
    {
        return Util::stringifyArgument($argument);
    }
}
