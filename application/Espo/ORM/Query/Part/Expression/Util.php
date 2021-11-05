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

namespace Espo\ORM\Query\Part\Expression;

use Espo\ORM\Query\Part\Expression as Expr;

use InvalidArgumentException;

class Util
{
    /**
     * Compose an expression by a function name and arguments.
     */
    public static function composeFunction(string $function, ...$argumentList): Expr
    {
        $stringifiedItemList = array_map(
            function ($item) {
                return self::stringifyArgument($item);
            },
            $argumentList
        );

        $expression = $function . ':(' . implode(', ', $stringifiedItemList) . ')';

        return Expr::create($expression);
    }

    /**
     * Stringify an argument.
     *
     * @param Expr|bool|int|float|string|null $arg
     * @throws InvalidArgumentException
     */
    public static function stringifyArgument($arg): string
    {
        /** @phpstan-var mixed $arg */

        if ($arg instanceof Expr) {
            return $arg->getValue();
        }

        if (is_null($arg)) {
            return 'NULL';
        }

       if (is_bool($arg)) {
            return $arg ? 'TRUE': 'FALSE';
        }

        if (is_int($arg)) {
            return strval($arg);
        }

        if (is_float($arg)) {
            return strval($arg);
        }

        if (is_string($arg)) {
            return '\'' . str_replace('\'', '\\\'', $arg) . '\'';
        }

        throw new InvalidArgumentException("Bad argument type.");
    }
}
