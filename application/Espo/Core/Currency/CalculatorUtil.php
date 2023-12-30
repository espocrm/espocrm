<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Currency;

use DivisionByZeroError;

class CalculatorUtil
{
    private const SCALE = 14;

    public static function add(string $arg1, string $arg2): string
    {
        if (!function_exists('bcadd')) {
            return (string) (
                (float) $arg1 + (float) $arg2
            );
        }

        return bcadd(
            $arg1,
            $arg2,
            self::SCALE
        );
    }

    public static function subtract(string $arg1, string $arg2): string
    {
        if (!function_exists('bcsub')) {
            return (string) (
                (float) $arg1 - (float) $arg2
            );
        }

        return bcsub(
            $arg1,
            $arg2,
            self::SCALE
        );
    }

    public static function multiply(string $arg1, string $arg2): string
    {
        if (!function_exists('bcmul')) {
            return (string) (
                (float) $arg1 * (float) $arg2
            );
        }

        return bcmul(
            $arg1,
            $arg2,
            self::SCALE
        );
    }

    public static function divide(string $arg1, string $arg2): string
    {
        if (!function_exists('bcdiv')) {
            return (string) (
                (float) $arg1 / (float) $arg2
            );
        }

        /** @var ?string $result */
        $result = bcdiv(
            $arg1,
            $arg2,
            self::SCALE
        );

        if ($result === null) {
            throw new DivisionByZeroError();
        }

        return $result;
    }

    public static function round(string $arg, int $precision = 0): string
    {
        if (!function_exists('bcadd')) {
            return (string) round((float) $arg, $precision);
        }

        $addition = '0.' . str_repeat('0', $precision) . '5';

        if ($arg[0] === '-') {
            $addition = '-' . $addition;
        }

        return bcadd(
            $arg,
            $addition,
            $precision
        );
    }

    public static function compare(string $arg1, string $arg2): int
    {
        if (!function_exists('bccomp')) {
            return (float) $arg1 <=> (float) $arg2;
        }

        return bccomp(
            $arg1,
            $arg2,
            self::SCALE
        );
    }
}
