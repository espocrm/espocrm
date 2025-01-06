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

namespace Espo\ORM\Query\Part\Expression;

use Espo\ORM\Query\Part\Expression;

class Util
{
    /**
     * Compose an expression by a function name and arguments.
     *
     * @param Expression|bool|int|float|string|null ...$arguments Arguments
     */
    public static function composeFunction(
        string $function,
        Expression|bool|int|float|string|null ...$arguments
    ): Expression {

        $stringifiedItems = array_map(
            function ($item) {
                return self::stringifyArgument($item);
            },
            $arguments
        );

        $expression = $function . ':(' . implode(', ', $stringifiedItems) . ')';

        return Expression::create($expression);
    }

    /**
     * Stringify an argument.
     *
     * @param Expression|bool|int|float|string|null $argument
     */
    public static function stringifyArgument(Expression|bool|int|float|string|null $argument): string
    {

        if ($argument instanceof Expression) {
            return $argument->getValue();
        }

        if (is_null($argument)) {
            return 'NULL';
        }

       if (is_bool($argument)) {
            return $argument ? 'TRUE': 'FALSE';
        }

       if (is_int($argument)) {
           return strval($argument);
       }

       if (is_float($argument)) {
           return strval($argument);
       }

       return '\'' . str_replace('\'', '\\\'', $argument) . '\'';
    }
}
