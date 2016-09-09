<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
     * @param int $depth Default 512
     * @return string
     */
    public static function encode($value, $options = 0, $depth = 512)
    {
        $json = json_encode($value, $options, $depth);

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
     * @param int $depth Default 512
     * @param int $options Default 0
     * @return object
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        if (is_null($json) || $json === false) {
            return $json;
        }

        if (is_array($json)) {
            $GLOBALS['log']->warning('Json::decode() - JSON cannot be decoded - '.$json);
            return false;
        }

        $json = json_decode($json, $assoc, $depth, $options);

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

    protected static function getLastError($error = null)
    {
        if (!isset($error)) {
            $error = json_last_error();
        }

        switch ($error) {
            case JSON_ERROR_NONE:
                return false;
            break;

            case JSON_ERROR_DEPTH:
                return 'The maximum stack depth has been exceeded';
            break;

            case JSON_ERROR_STATE_MISMATCH:
                return 'Invalid or malformed JSON';
            break;

            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error, possibly incorrectly encoded';
            break;

            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            break;

            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;

            /* Only for PHP 5.5.0
            case JSON_ERROR_RECURSION:
                return 'One or more recursive references in the value to be encoded';
            break;
            case JSON_ERROR_INF_OR_NAN:
                return 'One or more NAN or INF values in the value to be encoded';
            break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                return 'A value of a type that cannot be encoded was given';
            break;
            */

            default:
                return 'Unknown error';
            break;
          }
    }

}

