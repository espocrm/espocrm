<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Utils\InlineAttachment;

/**
 * @since 10.2.0
 * @internal
 */
class InlineAttachmentUtil
{
    private const string REG_EXP = "/\?entryPoint=attachment(?:&|&amp;)id=([A-Za-z0-9\-]+)/";

    private const string TOKEN = '?entryPoint=attachment';
    /** @noinspection SpellCheckingInspection */
    private const string NEUTRALIZED_TOKEN = '?entryPoint=%61ttachment';

    private const array NAMED_REFS = [
        '?' => 'quest',
        '=' => 'equals',
    ];

    private static ?string $tolerantTokenPattern = null;

    /**
     * @internal
     * @return string[]
     */
    public static function extractIds(string $body): array
    {
        if (!preg_match_all(self::REG_EXP, $body, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }

    /**
     * @internal
     * @param array<string, string> $map Old ID => new ID.
     */
    public static function replaceIds(string $content, array $map): string
    {
        if (!$map) {
            return $content;
        }

        $result = preg_replace_callback(self::REG_EXP, function ($matches) use ($map): string {
            $id = $matches[1];

            if (!isset($map[$id])) {
                return $matches[0];
            }

            return substr($matches[0], 0, -strlen($id)) . $map[$id];
        }, $content);

        return $result ?? $content;
    }

    /**
     * @internal
     * @param callable(string $id): ?string $replacer
     */
    public static function replace(string $content, callable $replacer, bool $onlyQuoted = true): string
    {
        $result = preg_replace_callback(
            pattern: self::REG_EXP,
            callback: function ($matches) use ($content, $replacer, $onlyQuoted): string {
                [$whole, $offset] = $matches[0];

                if ($onlyQuoted) {
                    $before = $offset > 0 ? $content[$offset - 1] : '';
                    $after = $content[$offset + strlen($whole)] ?? '';

                    $isQuoted = ($before === '"' || $before === "'") && $before === $after;

                    if (!$isQuoted) {
                        return $whole;
                    }
                }

                return $replacer($matches[1][0]) ?? $whole;
            },
            subject: $content,
            flags: PREG_OFFSET_CAPTURE,
        );

        return $result ?? $content;
    }

    /**
     * Neutralizes entity-encoded references, which a browser (and the editor) would decode into a live reference.
     * Literal references are left unchanged.
     *
     * @internal
     */
    public static function neutralizeEncoded(string $content): string
    {
        // No encoded form is possible without these.
        if (
            !str_contains($content, '&#') &&
            !str_contains($content, '&equals;') &&
            !str_contains($content, '&quest;')
        ) {
            return $content;
        }

        $result = preg_replace_callback(
            pattern: self::getTolerantTokenPattern(),
            callback: fn (array $matches) => $matches[0] === self::TOKEN ? $matches[0] : self::NEUTRALIZED_TOKEN,
            subject: $content,
        );

        return $result ?? $content;
    }

    /**
     * A pattern matching the token where each character may be written as a character reference.
     */
    private static function getTolerantTokenPattern(): string
    {
        if (self::$tolerantTokenPattern !== null) {
            return self::$tolerantTokenPattern;
        }

        $parts = [];

        foreach (str_split(self::TOKEN) as $char) {
            $code = ord($char);
            $hex = dechex($code);

            $alternatives = [
                preg_quote($char, '/'),
                // Decimal. Browsers also decode it without ';' if no digit follows.
                "&#0*+$code(?:;|(?![0-9]))",
                // Hexadecimal. 'x' or 'X', hex digits in any case.
                "&#[xX]0*+(?i:$hex)(?:;|(?![0-9a-fA-F]))",
            ];

            if (isset(self::NAMED_REFS[$char])) {
                $alternatives[] = '&' . self::NAMED_REFS[$char] . ';';
            }

            $parts[] = '(?:' . implode('|', $alternatives) . ')';
        }

        self::$tolerantTokenPattern = '/' . implode('', $parts) . '/';

        return self::$tolerantTokenPattern;
    }
}
