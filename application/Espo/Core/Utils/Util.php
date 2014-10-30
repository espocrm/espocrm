<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace Espo\Core\Utils;


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
     * @param  string  $symbol
     * @param  boolean $capitaliseFirstChar
     *
     * @return string
     */
    public static function toCamelCase($name, $symbol = '_', $capitaliseFirstChar = false)
    {
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
     * @param string $name
     *
     * @return string
     */
    public static function fromCamelCase($name, $symbol = '_')
    {
        $name[0] = strtolower($name[0]);
        return preg_replace_callback('/([A-Z])/', function ($matches) use ($symbol) {
                     return $symbol . strtolower($matches[1]);
                }, $name);
    }

    /**
     * Convert name from Camel Case format to underscore.
     * ex. camelCase to camel_case
     *
     * @param string $name
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

        /** add root items from currentArray */
        foreach ($currentArray as $currentName => $currentValue) {

            if (!array_key_exists($currentName, $newArray)) {

                $newArray[$currentName] = $currentValue;

            } else if (is_array($currentValue) && is_array($newArray[$currentName])) {

                /** check __APPEND__ identifier */
                $appendKey = array_search($mergeIdentifier, $newArray[$currentName], true);
                if ($appendKey !== false) {
                    unset($newArray[$currentName][$appendKey]);
                    $newArray[$currentName] = array_merge($currentValue, $newArray[$currentName]);
                } else if (!static::isSingleArray($newArray[$currentName])) {
                    $newArray[$currentName] = static::merge($currentValue, $newArray[$currentName]);
                }

            }
        }

        return $newArray;
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
            return $fullPath;
        }

        if (empty($filePath)) {
            return $folderPath;
        }
        if (empty($folderPath)) {
            return $filePath;
        }

        if (substr($folderPath, -1) == static::getSeparator()) {
            return $folderPath . $filePath;
        }
        return $folderPath . static::getSeparator() . $filePath;
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
     *
     * @return array
     */
    public static function unsetInArray(array $content, $unsets)
    {
        if (empty($unsets)) {
            return $content;
        }

        if (is_string($unsets)) {
            $unsets = (array) $unsets;
        }

        foreach($unsets as $rootKey => $unsetItem){
            $unsetItem = is_array($unsetItem) ? $unsetItem : (array) $unsetItem;

            foreach($unsetItem as $unsetSett){
                if (!empty($unsetSett)){
                    $keyItems = explode('.', $unsetSett);
                    $currVal = isset($content[$rootKey]) ? "\$content['{$rootKey}']" : "\$content";

                    $lastKey = array_pop($keyItems);
                    foreach($keyItems as $keyItem){
                        $currVal .= "['{$keyItem}']";
                    }

                    $unsetElem = $currVal . "['{$lastKey}']";

                    $currVal = "
                    if (isset({$unsetElem}) || ( is_array({$currVal}) && array_key_exists({$lastKey}, {$currVal}) )) {
                        unset({$unsetElem});
                    } ";
                    eval($currVal);
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
     * @param  array $array
     * @param  string $key     Ex. of key is "entityDefs", "entityDefs.User"
     * @param  mixed $default
     * @return mixed
     */
    public static function getValueByKey(array $array, $key = null, $default = null)
    {
        if (!isset($key) || empty($key)) {
            return $array;
        }

        $keys = explode('.', $key);

        $lastItem = $array;
        foreach($keys as $keyName) {
            if (isset($lastItem[$keyName]) && is_array($lastItem)) {
                $lastItem = $lastItem[$keyName];
            } else {
                return $default;
            }
        }

        return $lastItem;
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

}


?>
