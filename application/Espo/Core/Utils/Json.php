<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils;

class Json
{
    /**
     * JSON encode a string
     *
     * @param string $value
     * @param int $options Default 0
     * @return string
     */
    public static function encode($value, $options = 0)
    {
        $json = json_encode($value, $options);

        $error = self::getLastError();
        if ($json === null || !empty($error)) {
            $GLOBALS['log']->error('Json::encode():' . $error . ' - ' . print_r($value, true));
        }

        return $json;
    }

    /**
     * JSON decode a string (Fixed problem with "\")
     *
     * @param string $json
     * @param bool $assoc Default false
     * @return object|array
     */
    public static function decode($json, $assoc = false)
    {
        if (is_null($json) || $json === false) {
            return $json;
        }

        if (is_array($json)) {
            $GLOBALS['log']->warning('Json::decode() - JSON cannot be decoded - '.$json);
            return false;
        }

        $json = json_decode($json, $assoc);

        $error = self::getLastError();
        if ($error) {
            $GLOBALS['log']->error('Json::decode():' . $error);
        }

        return $json;
    }

    /**
     * Check if the string is JSON
     *
     * @param string $json
     * @return bool
     */
    public static function isJSON($json)
    {
        if ($json === '[]' || $json === '{}') {
            return true;
        } else if (is_array($json)) {
            return false;
        }

        return static::decode($json) != null;
    }

    /**
    * Get an array data (if JSON convert to array)
    *
    * @param mixed $data - can be JSON, array
    *
    * @return array
    */
    public static function getArrayData($data, $returns = array())
    {
        if (is_array($data)) {
            return $data;
        }
        else if (static::isJSON($data)) {
            return static::decode($data, true);
        }

        return $returns;
    }

    protected static function getLastError()
    {
        $error = json_last_error();

        if (!empty($error)) {
            return json_last_error_msg();
        }
    }
}