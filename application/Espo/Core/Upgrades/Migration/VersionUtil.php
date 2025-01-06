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

namespace Espo\Core\Upgrades\Migration;

use RuntimeException;

class VersionUtil
{
    /**
     * @param string[] $fullList
     * @return string[]
     */
    public static function extractSteps(string $from, string $to, array $fullList): array
    {
        usort($fullList, fn ($v1, $v2) => version_compare($v1, $v2));

        $isPatch = self::isPatch($from, $to);

        $list = [];
        $nextMinorIsPassed = false;

        foreach ($fullList as $item) {
            $a = self::split($item);

            if ($isPatch && $a[2] === null) {
                continue;
            }

            $v1 = $a[0] . '.' . $a[1] . '.' . ($a[2] ?? '0');

            if (version_compare($v1, $from) <= 0) {
                continue;
            }

            if (version_compare($v1, $to) > 0) {
                continue;
            }

            $isItemPatch = $a[2] !== null;

            if (!$isPatch && $isItemPatch && $nextMinorIsPassed) {
                continue;
            }

            if (!$nextMinorIsPassed && !self::isPatch($from, $v1)) {
                $nextMinorIsPassed = true;
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @return array{string, string, ?string}
     */
    public static function split(string $version): array
    {
        $array = explode('.', $version, 3);

        if (count($array) === 2) {
            return [$array[0], $array[1], null];
        }

        if (count($array) !== 3) {
            throw new RuntimeException("Bad version number $version.");
        }

        /** @var array{string, string, string} */
        return $array;
    }

    public static function isPatch(string $from, string $to): bool
    {
        $aFrom = self::split($from);
        $aTo = self::split($to);

        return $aFrom[0] === $aTo[0] && $aFrom[1] === $aTo[1];
    }

    public static function stepToVersion(string $step): string
    {
        $a = self::split($step);

        if ($a[2] === null) {
            $a[2] = '0';
        }

        return implode('.', $a);
    }
}
