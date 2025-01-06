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

namespace Espo\Tools\Email;

class Util
{
    static public function parseFromName(string $string): string
    {
        $fromName = '';

        if ($string && stripos($string, '<') !== false) {
            /** @var string $replacedString */
            $replacedString = preg_replace('/(<.*>)/', '', $string);

            $fromName = trim($replacedString, '" ');
        }

        return $fromName;
    }

    static public function parseFromAddress(string $string): string
    {
        if (!$string) {
            return '';
        }

        if (stripos($string, '<') !== false) {
            $fromAddress = '';

            if (preg_match('/<(.*)>/', $string, $matches)) {
                $fromAddress = trim($matches[1]);
            }

            return $fromAddress;
        }

        return $string;
    }

    static public function stripBodyPlainQuotePart(string $body): string
    {
        if (!$body) {
            return '';
        }

        $lines = preg_split("/\r\n|\n|\r/", $body);

        if (!is_array($lines)) {
            return '';
        }

        $endIndex = count($lines) - 1;

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = $lines[$i];

            if (str_starts_with($line, '>') || $line === '') {
                $endIndex = $i;

                continue;
            }

            break;
        }

        $lines = array_slice($lines, 0, $endIndex);

        if (count($lines) > 2) {
            $lastIndex = count($lines) - 1;

            if (str_ends_with($lines[$lastIndex], ':') && $lines[$lastIndex - 1] === '') {
                $lines = array_slice($lines, 0, count($lines) - 2);
            }
        }

        return implode("\r\n", $lines);
    }
}
