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
    private const string REG_EXP = "/\?entryPoint=attachment(?:&|&amp;)id=([^&=\"']+)/";

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
     * Replaces only if quoted.
     * @internal
     * @param callable(string $id): ?string $replacer
     */
    public static function replace(string $content, callable $replacer): string
    {
        $result = preg_replace_callback(self::REG_EXP, function ($matches) use ($content, $replacer): string {
            [$whole, $offset] = $matches[0];

            $before = $offset > 0 ? $content[$offset - 1] : '';
            $after = $content[$offset + strlen($whole)] ?? '';

            $isQuoted = ($before === '"' || $before === "'") && $before === $after;

            if (!$isQuoted) {
                return $whole;
            }

            return $replacer($matches[1][0]) ?? $whole;
        }, $content, flags: PREG_OFFSET_CAPTURE);

        return $result ?? $content;
    }
}
