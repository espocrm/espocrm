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

namespace Espo\ORM;

use Espo\ORM\Type\AttributeType;

/**
 * @internal
 */
class Util
{
    /**
     * @internal
     */
    public static function areValuesEqual(string $type, mixed $v1, mixed $v2, bool $isUnordered = false): bool
    {
        if ($type === AttributeType::JSON_ARRAY) {
            if (is_array($v1) && is_array($v2)) {
                if ($isUnordered) {
                    sort($v1);
                    sort($v2);
                }

                if ($v1 != $v2) {
                    return false;
                }

                foreach ($v1 as $i => $itemValue) {
                    if (is_object($itemValue) && is_object($v2[$i])) {
                        if (!self::areValuesEqual(AttributeType::JSON_OBJECT, $itemValue, $v2[$i])) {
                            return false;
                        }

                        continue;
                    }

                    if ($itemValue !== $v2[$i]) {
                        return false;
                    }
                }

                return true;
            }
        } else if ($type === AttributeType::JSON_OBJECT) {
            if (is_object($v1) && is_object($v2)) {
                if ($v1 != $v2) {
                    return false;
                }

                $a1 = get_object_vars($v1);
                $a2 = get_object_vars($v2);

                foreach (get_object_vars($v1) as $key => $itemValue) {
                    if (is_object($a1[$key]) && is_object($a2[$key])) {
                        if (!self::areValuesEqual(AttributeType::JSON_OBJECT, $a1[$key], $a2[$key])) {
                            return false;
                        }

                        continue;
                    }

                    if (is_array($a1[$key]) && is_array($a2[$key])) {
                        if (!self::areValuesEqual(AttributeType::JSON_ARRAY, $a1[$key], $a2[$key])) {
                            return false;
                        }

                        continue;
                    }

                    if ($a1[$key] !== $a2[$key]) {
                        return false;
                    }
                }

                return true;
            }
        }

        return $v1 === $v2;
    }
}
