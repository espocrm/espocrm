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

use League\HTMLToMarkdown\HtmlConverter;

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

    /**
     * Strip HTML.
     *
     * @since 9.1.0
     */
    static public function stripHtml(string $string): string
    {
        if (!$string) {
            return '';
        }

        $converter = new HtmlConverter();
        $converter->setOptions([
            'remove_nodes' => 'img',
            'strip_tags' => true,
        ]);

        $string = $converter->convert($string) ?: '';

        $string = (string) preg_replace('~\R~u', "\r\n", $string);

        $reList = [
            '&(quot|#34);',
            '&(amp|#38);',
            '&(lt|#60);',
            '&(gt|#62);',
            '&(nbsp|#160);',
            '&(iexcl|#161);',
            '&(cent|#162);',
            '&(pound|#163);',
            '&(copy|#169);',
            '&(reg|#174);',
        ];

        $replaceList = [
            '',
            '&',
            '<',
            '>',
            ' ',
            '¡',
            '¢',
            '£',
            '©',
            '®',
        ];

        foreach ($reList as $i => $re) {
            $string = (string) mb_ereg_replace($re, $replaceList[$i], $string, 'i');
        }



        return $string;
    }

    /**
     * Strip a quote part in a plain text.
     *
     * @since 9.0.0
     */
    static public function stripPlainTextQuotePart(string $string): string
    {
        if (!$string) {
            return '';
        }

        $lines = preg_split("/\r\n|\n|\r/", $string);

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
