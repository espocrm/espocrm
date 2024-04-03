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

namespace Espo\Core\Upgrades\Migration;

use RuntimeException;
use const SORT_STRING;

class StepsExtractor
{
    /**
     * @param string[] $fullList
     * @return string[]
     */
    public function extract(string $from, string $to, array $fullList): array
    {
        sort($fullList, SORT_STRING);

        $aFrom = self::split($from);
        $aTo = self::split($to);

        $isHotfix = $aFrom[0] === $aTo[0] && $aFrom[1] === $aTo[1];

        $list = [];

        foreach ($fullList as $item) {
            $a = self::split($item);

            $v1 = $a[0] . '.' . $a[1] . '.' . ($a[2] ?? '0');

            if (version_compare($v1, $from) <= 0) {
                continue;
            }

            if (version_compare($v1, $to) > 0) {
                continue;
            }

            if (!$isHotfix && $a[2] !== null) {
                continue;
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @return array{string, string, ?string}
     */
    private static function split(string $version): array
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
}
