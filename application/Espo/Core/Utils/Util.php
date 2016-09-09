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

use \Espo\Core\Exceptions\Error;

class Util
{
    /**
     * @var string - default directory separator
     */
    protected static $separator = DIRECTORY_SEPARATOR;

    protected static $reservedWords = array('Case');


    /**
     * Get a folder separator
     *
     * @return string
     */
    public static function getSeparator()
    {
        return static::$separator;
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

        if($capitaliseFirstChar) {
            $name[0] = strtoupper($name[0]);
        }
        return preg_replace_callback('/'.$symbol.'([a-z])/', 'static::toCamelCaseConversion', $name);
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

        if (is_array($currentArray) && (!is_array($newArray) || empty($newArray))) {
            return $currentArray;
        } else if ((!is_array($currentArray) || empty($currentArray)) && is_array($newArray)) {
            return $newArray;
        } else if ((!is_array($currentArray) || empty($currentArray)) && (!is_array($newArray) || empty($newArray))) {
            return array();
        }

        foreach ($newArray as $newName => $newValue) {

            if (is_array($newValue) && empty($newValue)) {
                continue;
            }

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
        if (in_array($name, self::$reservedWords)) {
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
        foreach (self::$reservedWords as $reservedWord) {
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
        $className = preg_replace('/^(application|custom)\//i', '', $className);
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

    public static function sanitizeFileName($fileName)
    {
        return preg_replace("/([^\w\s\d\-_~,;:\[\]\(\).])/u", '_', $fileName);
    }

}

