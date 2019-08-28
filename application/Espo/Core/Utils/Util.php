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

use \Espo\Core\Exceptions\Error;

class Util
{
    /**
     * @var string - default directory separator
     */
    protected static $separator = DIRECTORY_SEPARATOR;

    protected static $reservedWordList = ['Case'];


    /**
     * Get a folder separator
     *
     * @return string
     */
    public static function getSeparator()
    {
        return static::$separator;
    }

    public static function camelCaseToUnderscore(string $string) : string
    {
        return static::toUnderScore($string);
    }

    public static function hyphenToCamelCase(string $string) : string
    {
        return self::toCamelCase($string, '-');
    }


    /**
     * Convert to format with defined delimeter
     * ex. Espo/Utils to Espo\Utils
     *
     * @param string $name
     * @param string $delim - delimiter
     *
     * @return string
     */
    public static function toFormat($name, $delim = '/')
    {
        return preg_replace("/[\/\\\]/", $delim, $name);
    }


    /**
     * Convert name to Camel Case format, ex. camel_case to camelCase
     *
     * @param  string  $name
     * @param  string | array  $symbol
     * @param  boolean $capitaliseFirstChar
     *
     * @return string
     */
    public static function toCamelCase($name, $symbol = '_', $capitaliseFirstChar = false)
    {
        if (is_array($name)) {
            foreach ($name as &$value) {
                $value = static::toCamelCase($value, $symbol, $capitaliseFirstChar);
            }

            return $name;
        }

        $name = lcfirst($name);
        if ($capitaliseFirstChar) {
            $name = ucfirst($name);
        }

        return preg_replace_callback('/'.$symbol.'([a-zA-Z])/', 'static::toCamelCaseConversion', $name);
    }

    protected static function toCamelCaseConversion($matches)
    {
        return strtoupper($matches[1]);
    }

    /**
     * Convert name from Camel Case format.
     * ex. camelCase to camel-case
     *
     * @param string | array $name
     *
     * @return string
     */
    public static function fromCamelCase($name, $symbol = '_')
    {
        if (is_array($name)) {
            foreach ($name as &$value) {
                $value = static::fromCamelCase($value, $symbol);
            }

            return $name;
        }

        $name[0] = strtolower($name[0]);
        return preg_replace_callback('/([A-Z])/', function ($matches) use ($symbol) {
                     return $symbol . strtolower($matches[1]);
                }, $name);
    }

    /**
     * Convert name from Camel Case format to underscore.
     * ex. camelCase to camel_case
     *
     * @param string | array $name
     *
     * @return string
     */
    public static function toUnderScore($name)
    {
        return static::fromCamelCase($name, '_');
    }

    /**
     * Merge arrays recursively (default PHP function is not suitable)
     *
     * @param array $currentArray
     * @param array $newArray - chief array (priority is same as for array_merge())
     *
     * @return array
     */
    public static function merge($currentArray, $newArray)
    {
        $mergeIdentifier = '__APPEND__';

        if (is_array($currentArray) && !is_array($newArray)) {
            return $currentArray;
        } else if (!is_array($currentArray) && is_array($newArray)) {
            return $newArray;
        } else if ((!is_array($currentArray) || empty($currentArray)) && (!is_array($newArray) || empty($newArray))) {
            return array();
        }

        foreach ($newArray as $newName => $newValue) {

            if (is_array($newValue) && array_key_exists($newName, $currentArray) && is_array($currentArray[$newName])) {

                // check __APPEND__ identifier
                $appendKey = array_search($mergeIdentifier, $newValue, true);
                if ($appendKey !== false) {
                    unset($newValue[$appendKey]);
                    $newValue = array_merge($currentArray[$newName], $newValue);
                } else if (!static::isSingleArray($newValue) || !static::isSingleArray($currentArray[$newName])) {
                    $newValue = static::merge($currentArray[$newName], $newValue);
                }

            }

            //check if exists __APPEND__ identifier and remove its
            if (!isset($currentArray[$newName]) && is_array($newValue)) {
                $newValue = static::unsetInArrayByValue($mergeIdentifier, $newValue);
            }

            $currentArray[$newName] = $newValue;
        }

        return $currentArray;
    }

    /**
     * Unset a value in array recursively
     *
     * @param  string $needle
     * @param  array  $haystack
     * @param  bool   $reIndex
     * @return array
     */
    public static function unsetInArrayByValue($needle, array $haystack, $reIndex = true)
    {
        $doReindex = false;

        foreach($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = static::unsetInArrayByValue($needle, $value);
            } else if ($needle === $value) {

                unset($haystack[$key]);

                if ($reIndex) {
                    $doReindex = true;
                }
            }
        }

        if ($doReindex) {
            $haystack = array_values($haystack);
        }

        return $haystack;
    }

    /**
     * Get a full path of the file
     *
     * @param string | array $folderPath - Folder path, Ex. myfolder
     * @param string $filePath - File path, Ex. file.json
     *
     * @return string
     */
    public static function concatPath($folderPath, $filePath = null)
    {
        if (is_array($folderPath)) {
            $fullPath = '';
            foreach ($folderPath as $path) {
                $fullPath = static::concatPath($fullPath, $path);
            }
            return static::fixPath($fullPath);
        }

        if (empty($filePath)) {
            return static::fixPath($folderPath);
        }
        if (empty($folderPath)) {
            return static::fixPath($filePath);
        }

        if (substr($folderPath, -1) == static::getSeparator() || substr($folderPath, -1) == '/') {
            return static::fixPath($folderPath . $filePath);
        }
        return $folderPath . static::getSeparator() . $filePath;
    }

    /**
     * Fix path separator
     *
     * @param  string $path
     * @return string
     */
    public static function fixPath($path)
    {
        return str_replace('/', static::getSeparator(), $path);
    }

    /**
     * Convert array to object format recursively
     *
     * @param array $array
     * @return object
     */
    public static function arrayToObject($array)
    {
        if (is_array($array)) {
            return (object) array_map("static::arrayToObject", $array);
        } else {
            return $array; // Return an object
        }
    }

    /**
     * Convert object to array format recursively
     *
     * @param object $object
     * @return array
     */
    public static function objectToArray($object)
    {
        if (is_object($object)) {
            $object = (array) $object;
        }

        return is_array($object) ? array_map("static::objectToArray", $object) : $object;
    }

    /**
     * Appends 'Obj' if name is reserved PHP word.
     *
     * @param string $name
     * @return string
     */
    public static function normilizeClassName($name)
    {
        if (in_array($name, self::$reservedWordList)) {
            $name .= 'Obj';
        }
        return $name;
    }

    /**
     * Remove 'Obj' if name is reserved PHP word.
     *
     * @param string $name
     * @return string
     */
    public static function normilizeScopeName($name)
    {
        foreach (self::$reservedWordList as $reservedWord) {
            if ($reservedWord.'Obj' == $name) {
                return $reservedWord;
            }
        }

        return $name;
    }

    /**
    * Get Naming according to prefix or postfix type
    *
    * @param string $name
    * @param string $prePostFix
    * @param string $type
    *
    * @return string
    */
    public static function getNaming($name, $prePostFix, $type = 'prefix', $symbol = '_')
    {
        if ($type == 'prefix') {
            return static::toCamelCase($prePostFix.$symbol.$name, $symbol);
        } else if ($type == 'postfix') {
            return static::toCamelCase($name.$symbol.$prePostFix, $symbol);
        }

        return null;
    }

    /**
     * Replace $search in array recursively
     *
     * @param string $search
     * @param string $replace
     * @param string $array
     * @param string $isKeys
     *
     * @return array
     */
    public static function replaceInArray($search = '', $replace = '', $array = false, $isKeys = true)
    {
        if (!is_array($array)) {
            return str_replace($search, $replace, $array);
        }

        $newArr = array();
        foreach ($array as $key => $value) {
            $addKey = $key;
            if ($isKeys) { //Replace keys
                $addKey = str_replace($search, $replace, $key);
            }

            // Recurse
            $newArr[$addKey] = static::replaceInArray($search, $replace, $value, $isKeys);
        }

        return $newArr;
    }

    /**
     * Unset content items defined in the unset.json
     *
     * @param array $content
     * @param string | array $unsets in format
     *   array(
     *      'EntityName1' => array( 'unset1', 'unset2' ),
     *      'EntityName2' => array( 'unset1', 'unset2' ),
     *  )
     *  OR
     *  array('EntityName1.unset1', 'EntityName1.unset2', .....)
     *  OR
     *  'EntityName1.unset1'
     * @param bool $unsetParentEmptyArray - If unset empty parent array after unsets
     *
     * @return array
     */
    public static function unsetInArray(array $content, $unsets, $unsetParentEmptyArray = false)
    {
        if (empty($unsets)) {
            return $content;
        }

        if (is_string($unsets)) {
            $unsets = (array) $unsets;
        }

        foreach ($unsets as $rootKey => $unsetItem) {
            $unsetItem = is_array($unsetItem) ? $unsetItem : (array) $unsetItem;

            foreach ($unsetItem as $unsetString) {
                if (is_string($rootKey)) {
                    $unsetString = $rootKey . '.' . $unsetString;
                }

                $keyArr = explode('.', $unsetString);
                $keyChainCount = count($keyArr) - 1;

                $elem = &$content;

                $elementArr = [];
                $elementArr[] = &$elem;
                for ($i = 0; $i <= $keyChainCount; $i++) {

                    if (is_array($elem) && array_key_exists($keyArr[$i], $elem)) {
                        if ($i == $keyChainCount) {
                            unset($elem[$keyArr[$i]]);

                            if ($unsetParentEmptyArray) {
                                for ($j = count($elementArr); $j > 0; $j--) {
                                    $pointer =& $elementArr[$j];
                                    if (is_array($pointer) && empty($pointer)) {
                                        $previous =& $elementArr[$j - 1];
                                        unset($previous[$keyArr[$j - 1]]);
                                    }
                                }
                            }

                        } else if (is_array($elem[$keyArr[$i]])) {
                            $elem = &$elem[$keyArr[$i]];
                            $elementArr[] = &$elem;
                        }

                    }
                }
            }
        }

        return $content;
    }


    /**
     * Get class name from the file path
     *
     * @param  string $filePath
     *
     * @return string
     */
    public static function getClassName($filePath)
    {
        $className = preg_replace('/\.php$/i', '', $filePath);
        $className = preg_replace('/^(application|custom)(\/|\\\)/i', '', $className);
        $className = '\\'.static::toFormat($className, '\\');

        return $className;
    }

    /**
     * Return values of defined $key.
     *
     * @param  mixed $data
     * @param  mixed array|string $key     Ex. of key is "entityDefs", "entityDefs.User"
     * @param  mixed $default
     * @return mixed
     */
    public static function getValueByKey($data, $key = null, $default = null)
    {
        if (!isset($key) || empty($key)) {
            return $data;
        }

        if (is_array($key)) {
            $keys = $key;
        } else {
            $keys = explode('.', $key);
        }

        $item = $data;
        foreach ($keys as $keyName) {
            if (is_array($item)) {
                if (isset($item[$keyName])) {
                    $item = $item[$keyName];
                } else {
                    return $default;
                }
            } else if (is_object($item)) {
                if (isset($item->$keyName)) {
                    $item = $item->$keyName;
                } else {
                    return $default;
                }
            }

        }

        return $item;
    }

    /**
     * Check if two variables are equals
     *
     * @param  mixed  $var1
     * @param  mixed  $var2
     * @return boolean
     */
    public static function isEquals($var1, $var2)
    {
        if (is_array($var1)) {
            static::ksortRecursive($var1);
        }
        if (is_array($var2)) {
            static::ksortRecursive($var2);
        }

        return ($var1 === $var2);
    }

    /**
     * Sort array recursively
     * @param  array $array
     * @return bool
     */
    public static function ksortRecursive(&$array)
    {
        if (!is_array($array)) {
            return false;
        }

        ksort($array);
        foreach ($array as $key => $value) {
            static::ksortRecursive($array[$key]);
        }

        return true;
    }

    public static function isSingleArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    public static function generateId()
    {
        return uniqid() . substr(md5(rand()), 0, 4);
    }

    public static function generateApiKey()
    {
        if (!function_exists('random_bytes')) {
            return self::generateId();
        }
        return bin2hex(random_bytes(16));
    }

    public static function generateKey()
    {
        return md5(uniqid(rand(), true));
    }

    public static function sanitizeFileName($fileName)
    {
        return preg_replace("/([^\w\s\d\-_~,;:\[\]\(\).])/u", '_', $fileName);
    }

    /**
     * Improved computing the difference of arrays
     *
     * @param  array  $array1
     * @param  array  $array2
     *
     * @return array
     */
    public static function arrayDiff(array $array1, array $array2)
    {
        $diff = array();

        foreach ($array1 as $key1 => $value1) {
            if (array_key_exists($key1, $array2)) {
                if ($value1 !== $array2[$key1]) {
                    $diff[$key1] = $array2[$key1];
                }
                continue;
            }

            $diff[$key1] = $value1;
        }

        $diff = array_merge($diff, array_diff_key($array2, $array1));

        return $diff;
    }

    /**
     * Fill array with specified keys
     *
     * @param  array|string $keys
     * @param  mixed $value
     *
     * @return array
     */
    public static function fillArrayKeys($keys, $value)
    {
        $arrayKeys = is_array($keys) ? $keys : explode('.', $keys);

        $array = array();
        foreach (array_reverse($arrayKeys) as $i => $key) {
            $array = array(
                $key => ($i == 0) ? $value : $array,
            );
        }

        return $array;
    }

    /**
     * Array keys exists
     *
     * @param  array  $keys
     * @param  array  $array
     *
     * @return boolean
     */
    public static function arrayKeysExists(array $keys, array $array)
    {
       return !array_diff_key(array_flip($keys), $array);
    }

    public static function convertToByte($value)
    {
        $value = trim($value);
        $last = strtoupper(substr($value, -1));

        switch ( $last )
        {
            case 'G':
            $value = (int) $value * 1024;
            case 'M':
            $value = (int) $value * 1024;
            case 'K':
            $value = (int) $value * 1024;
        }

        return $value;
    }

    public static function areValuesEqual($v1, $v2, $isUnordered = false)
    {
        if (is_array($v1) && is_array($v2)) {
            if ($isUnordered) {
                sort($v1);
                sort($v2);
            }
            if ($v1 != $v2) {
                return false;
            }
            foreach ($v1 as $i => $itemValue) {
                if (is_object($v1[$i]) && is_object($v2[$i])) {
                    if (!self::areValuesEqual($v1[$i], $v2[$i])) {
                        return false;
                    }
                    continue;
                }
                if ($v1[$i] !== $v2[$i]) {
                    return false;
                }
            }
            return true;
        }

        if (is_object($v1) && is_object($v2)) {
            if ($v1 != $v2) {
                return false;
            }
            $a1 = get_object_vars($v1);
            $a2 = get_object_vars($v2);
            foreach ($v1 as $key => $itemValue) {
                if (is_object($a1[$key]) && is_object($a2[$key])) {
                    if (!self::areValuesEqual($a1[$key], $a2[$key])) {
                        return false;
                    }
                    continue;
                }
                if (is_array($a1[$key]) && is_array($a2[$key])) {
                    if (!self::areValuesEqual($a1[$key], $a2[$key])) {
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

        return $v1 === $v2;
    }

    public static function mbUpperCaseFirst(string $string)
    {
        if (!$string) return $string;

        $length = mb_strlen($string);
        $firstChar = mb_substr($string, 0, 1);
        $then = mb_substr($string, 1, $length - 1);

        return mb_strtoupper($firstChar) . $then;
    }

    public static function mbLowerCaseFirst(string $string)
    {
        if (!$string) return $string;

        $length = mb_strlen($string);
        $firstChar = mb_substr($string, 0, 1);
        $then = mb_substr($string, 1, $length - 1);

        return mb_strtolower($firstChar) . $then;
    }

    /**
     * Sanitize Html code
     * @param  string $text
     * @param  array  $permittedHtmlTags - Allows only html tags without parameters like <p></p>, <br>, etc.
     * @return string
     */
    public static function sanitizeHtml($text, $permittedHtmlTags = ['p', 'br', 'b', 'strong', 'pre'])
    {
        if (is_array($text)) {
            foreach ($text as $key => &$value) {
                $value = self::sanitizeHtml($value, $permittedHtmlTags);
            }
            return $text;
        }

        $sanitized = htmlspecialchars($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        foreach ($permittedHtmlTags as $htmlTag) {
            $sanitized = preg_replace('/&lt;(\/)?(' . $htmlTag . ')&gt;/i', '<$1$2>', $sanitized);
        }

        return $sanitized;
    }

    public static function urlAddParam($url, $paramName, $paramValue)
    {
        $urlQuery = parse_url($url, \PHP_URL_QUERY);

        if (!$urlQuery) {
            $params = [
                $paramName => $paramValue
            ];

            $url = trim($url);
            $url = preg_replace('/\/\?$/', '', $url);
            $url = preg_replace('/\/$/', '', $url);

            return $url . '/?' . http_build_query($params);
        }

        parse_str($urlQuery, $params);

        if (!isset($params[$paramName]) || $params[$paramName] != $paramValue) {
            $params[$paramName] = $paramValue;

            return str_replace($urlQuery, http_build_query($params), $url);
        }

        return $url;
    }

    public static function urlRemoveParam($url, $paramName, $suffix = '')
    {
        $urlQuery = parse_url($url, \PHP_URL_QUERY);

        if ($urlQuery) {
            parse_str($urlQuery, $params);

            if (isset($params[$paramName])) {
                unset($params[$paramName]);

                $newUrl = str_replace($urlQuery, http_build_query($params), $url);

                if (empty($params)) {
                    $newUrl = preg_replace('/\/\?$/', '', $newUrl);
                    $newUrl = preg_replace('/\/$/', '', $newUrl);
                    $newUrl .= $suffix;
                }

                return $newUrl;
            }
        }

        return $url;
    }
}
