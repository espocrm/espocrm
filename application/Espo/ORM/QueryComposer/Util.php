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

namespace Espo\ORM\QueryComposer;

class Util
{
    public static function isComplexExpression(string $string): bool
    {
        if (
           self::isArgumentString($string) ||
           self::isArgumentNumeric($string) ||
           self::isArgumentBoolOrNull($string)
        ) {
            return true;
        }

        if (str_contains($string, '.')) {
            return true;
        }

        if (str_contains($string, ':')) {
            return true;
        }

        if (str_starts_with($string, '#')) {
            return true;
        }

        return false;
    }

    public static function isArgumentString(string $argument): bool
    {
        return
            str_starts_with($argument, '\'') && str_ends_with($argument, '\'')
            ||
            str_starts_with($argument, '"') && str_ends_with($argument, '"');
    }

    public static function isArgumentNumeric(string $argument): bool
    {
        return is_numeric($argument);
    }

    public static function isArgumentBoolOrNull(string $argument): bool
    {
        return in_array(strtoupper($argument), ['NULL', 'TRUE', 'FALSE']);
    }

    /**
     * @param string $expression
     * @return string[]
     */
    public static function getAllAttributesFromComplexExpression(string $expression): array
    {
        return self::getAllAttributesFromComplexExpressionImplementation($expression);
    }

    /**
     * @param string[]|null $list
     * @return string[]
     */
    private static function getAllAttributesFromComplexExpressionImplementation(
        string $expression,
        ?array &$list = null
    ): array {

        if (!$list) {
            $list = [];
        }

        if (!strpos($expression, ':')) {
            if (
                !self::isArgumentString($expression) &&
                !self::isArgumentNumeric($expression) &&
                !self::isArgumentBoolOrNull($expression) &&
                !str_contains($expression, '#')
            ) {
                $list[] = $expression;
            }

            return $list;
        }

        $delimiterPosition = strpos($expression, ':');
        $arguments = substr($expression, $delimiterPosition + 1);

        if (str_starts_with($arguments, '(') && str_ends_with($arguments, ')')) {
            $arguments = substr($arguments, 1, -1);
        }

        $argumentList = self::parseArgumentListFromFunctionContent($arguments);

        foreach ($argumentList as $argument) {
            self::getAllAttributesFromComplexExpressionImplementation($argument, $list);
        }

        return $list ?? [];
    }

    /**
     * @return string[]
     */
    static public function parseArgumentListFromFunctionContent(string $functionContent): array
    {
        $functionContent = trim($functionContent);

        $isString = false;
        $isSingleQuote = false;

        if ($functionContent === '') {
            return [];
        }

        $commaIndexList = [];
        $braceCounter = 0;

        for ($i = 0; $i < strlen($functionContent); $i++) {
            if ($functionContent[$i] === "'" && ($i === 0 || $functionContent[$i - 1] !== "\\")) {
                if (!$isString) {
                    $isString = true;
                    $isSingleQuote = true;
                } else {
                    if ($isSingleQuote) {
                        $isString = false;
                    }
                }
            } else if ($functionContent[$i] === "\"" && ($i === 0 || $functionContent[$i - 1] !== "\\")) {
                if (!$isString) {
                    $isString = true;
                    $isSingleQuote = false;
                } else {
                    if (!$isSingleQuote) {
                        $isString = false;
                    }
                }
            }

            if (!$isString) {
                if ($functionContent[$i] === '(') {
                    $braceCounter++;
                } else if ($functionContent[$i] === ')') {
                    $braceCounter--;
                }
            }

            if ($braceCounter === 0 && !$isString && $functionContent[$i] === ',') {
                $commaIndexList[] = $i;
            }
        }

        $commaIndexList[] = strlen($functionContent);

        $argumentList = [];

        for ($i = 0; $i < count($commaIndexList); $i++) {
            if ($i > 0) {
                $previousCommaIndex = $commaIndexList[$i - 1] + 1;
            } else {
                $previousCommaIndex = 0;
            }

            $argument = trim(
                substr($functionContent, $previousCommaIndex, $commaIndexList[$i] - $previousCommaIndex)
            );

            $argumentList[] = $argument;
        }

        return $argumentList;
    }
}
