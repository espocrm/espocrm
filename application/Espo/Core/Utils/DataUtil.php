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

namespace Espo\Core\Utils;

use InvalidArgumentException;
use LogicException;
use stdClass;

class DataUtil
{
    /**
     * @param array<string|int, mixed>|stdClass $data
     * @param array<int, string|string[]>|string $unsetList
     * @return array<string|int, mixed>|stdClass
     */
    public static function unsetByKey(&$data, $unsetList, bool $removeEmptyItems = false)
    {
        if (empty($unsetList)) {
            return $data;
        }

        if (is_string($unsetList)) {
            $unsetList = [$unsetList];
        } else if (!is_array($unsetList)) {
            throw new InvalidArgumentException();
        }

        foreach ($unsetList as $unsetItem) {
            if (is_array($unsetItem)) {
                $arr = $unsetItem;
            } else if (is_string($unsetItem)) {
                $arr = explode('.', $unsetItem);
            } else {
                throw new LogicException('Bad unset parameter');
            }

            $pointer = &$data;

            $elementArr = [];
            $elementArr[] = &$pointer;

            foreach ($arr as $i => $key) {
                if ($i === count($arr) - 1) {
                    if (is_array($pointer)) {
                        if (array_key_exists($key, $pointer)) {
                            unset($pointer[$key]);
                        }

                        continue;
                    }

                    if (!is_object($pointer)) {
                        continue;
                    }

                    unset($pointer->$key);

                    if (!$removeEmptyItems) {
                        continue;
                    }

                    for ($j = count($elementArr); $j > 0; $j--) {
                        $pointerBack =& $elementArr[$j];

                        if (is_object($pointerBack) && count(get_object_vars($pointerBack)) === 0) {
                            $previous =& $elementArr[$j - 1];

                            if (is_object($previous)) {
                                $key = $arr[$j - 1];
                                unset($previous->$key);
                            }
                        }
                    }

                    continue;
                }

                if (is_array($pointer)) {
                    $pointer = &$pointer[$key];
                } else if (is_object($pointer)) {
                    $pointer = &$pointer->$key;
                }

                $elementArr[] = &$pointer;
            }
        }

        return $data;
    }

    /**
     * @param array<string|int, mixed>|stdClass $data
     * @param mixed $needle
     * @return array<string|int, mixed>|stdClass
     */
    public static function unsetByValue(&$data, $needle)
    {
        if (is_object($data)) {
            foreach (get_object_vars($data) as $key => $value) {
                self::unsetByValue($data->$key, $needle);

                if ($data->$key === $needle) {
                    unset($data->$key);
                }
            }
        } else if (is_array($data)) {
            $doReindex = false;

            foreach ($data as $key => $value) {
                self::unsetByValue($data[$key], $needle);

                if ($data[$key] === $needle) {
                    unset($data[$key]);
                    $doReindex = true;
                }
            }

            if ($doReindex) {
                $data = array_values($data);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed>|stdClass $data
     * @param array<string, mixed>|stdClass $overrideData
     * @return array<string|int, mixed>|stdClass
     */
    public static function merge($data, $overrideData)
    {
        $appendIdentifier = '__APPEND__';

        /** @var mixed $data */
        /** @var mixed $overrideData */

        if (empty($data) && empty($overrideData)) {
            if (is_array($data) || is_array($overrideData)) {
                return [];
            }

            /** @var array<string|int, mixed>|stdClass */
            return $overrideData; /** @phpstan-ignore-line */
        }

        if (is_object($overrideData)) {
            if (empty($data)) {
                $data = (object) [];
            }

            foreach (get_object_vars($overrideData) as $key => $value) {
                if (isset($data->$key)) {
                    $data->$key = self::merge($data->$key, $overrideData->$key);
                } else {
                    $data->$key = $overrideData->$key;
                    self::unsetByValue($data->$key, $appendIdentifier);
                }
            }

            return $data;
        }

        if (is_array($overrideData)) {
            if (empty($data)) {
                $data = [];
            }

            /** @var array<string, mixed> $data */

            if (in_array($appendIdentifier, $overrideData)) {
                foreach ($overrideData as $item) {
                    if ($item === $appendIdentifier) {
                        continue;
                    }

                    $data[] = $item;
                }

                return $data;
            }

            return $overrideData;
        }

        /** @var array<string|int, mixed>|stdClass */
        return $overrideData;
    }
}
