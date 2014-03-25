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
    	//preg_match_all('/[\/]/', $name, $match);
    	//preg_match_all('/(.*)[\/\\\](.*)/', $name, $match);
		//return $match;

		return preg_replace('/[\/\\\]/', $delim, $name);
	}


	/**
	 * Convert name to Camel Case format, ex. camel-case to camelCase
	 * @param  string  $name
	 * @param  boolean $capitaliseFirstChar
	 * @param  string  $symbol
	 * @return string
	 */
	public static function toCamelCase($name, $capitaliseFirstChar = false, $symbol = '-')
	{
		if($capitaliseFirstChar) {
			$name[0] = strtoupper($name[0]);
		}
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/'.$symbol.'([a-z])/', $func, $name);
	}

	/**
    * Convert name from Camel Case format.
	* ex. camelCase to camel-case
	*
	* @param string $name
	*
	* @return string
	*/
	public static function fromCamelCase($name, $symbol = '-')
	{
		$name[0] = strtolower($name[0]);
		$func = create_function('$c', 'return "'.$symbol.'" . strtolower($c[1]);');
		return preg_replace_callback('/([A-Z])/', $func, $name);
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
	* Merge arrays (default PHP function is not suitable)
	*
	* @param array $array
	* @param array $mainArray - chief array (priority is same as for array_merge())
	* @return array
	*/
	public static function merge($array, $mainArray)
	{
		if (is_array($array) && !is_array($mainArray)) {
    		return $array;
    	} else if (!is_array($array) && is_array($mainArray)) {
    		return $mainArray;
    	} else if (!is_array($array) && !is_array($mainArray)) {
        	return array();
		}

		foreach($mainArray as $maKey => $maVal) {
			$found = false;
			foreach($array as $aKey => $aVal) {
				if ((string)$maKey == (string)$aKey){  
					$found = true;
					if (is_array($maVal) && is_array($aVal)){
						$array[$maKey] = static::merge($aVal, $maVal);
					}
					else {

						if (is_array($aVal)){
							$array[$maKey] = static::merge($aVal, array($maVal));
						}
						elseif (is_array($maVal)){
							$array[$maKey] = static::merge(array($aVal), $maVal);
						}
						else {
							//merge logic
							if (!is_numeric($maKey)){
								$array[$maKey] = $maVal;
							}
							elseif (!in_array($maVal, $array)) {
								$array[] = $maVal;
							}
							//END: merge ligic
						}
					}

					break;
				}
			}
			// add an item if key not found
			if (!$found){
				$array[$maKey] = $maVal;
			}

		}

		return $array;
	}


	/**
    * Get a full path of the file
	*
	* @param string $folderPath - Folder path, Ex. myfolder
	* @param string $filePath - File path, Ex. file.json
	*
	* @return string
	*/
	public static function concatPath($folderPath, $filePath='')
	{
		if (empty($filePath)) {
        	return $folderPath;
    	}
		if (empty($folderPath)) {
        	return $filePath;
    	}

		else {
            if (substr($folderPath, -1) == static::getSeparator()) {
            	return $folderPath . $filePath;
            }
        	return $folderPath . static::getSeparator() . $filePath;
		}
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
	public static function getNaming($name, $prePostFix, $type = 'prefix', $symbol = '-')
	{
		if ($type == 'prefix') {
        	return static::toCamelCase($prePostFix.$symbol.$name, false, $symbol);
		} else if ($type == 'postfix') {
        	return static::toCamelCase($name.$symbol.$prePostFix, false, $symbol);
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
	 * @param array $unsets in format
	 *   array(
	 * 		'EntityName1' => array( 'unset1', 'unset2' ),                             
	 * 		'EntityName2' => array( 'unset1', 'unset2' ),                             
	 *  )
	 * 	OR
	 * 	array('EntityName1.unset1', 'EntityName1.unset2', .....)
	 *
	 * @return array
	 */
	public static function unsetInArray(array $content, array $unsets)
	{
		foreach($unsets as $rootKey => $unsetItem){
			$unsetItem = is_array($unsetItem) ? $unsetItem : (array) $unsetItem;
			
			foreach($unsetItem as $unsetSett){
				if (!empty($unsetSett)){
					$keyItems = explode('.', $unsetSett);
					$currVal = isset($content[$rootKey]) ? "\$content['{$rootKey}']" : "\$content";
					foreach($keyItems as $keyItem){
						$currVal .= "['{$keyItem}']";
					}

					$currVal = "if (isset({$currVal})) unset({$currVal});";
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
	 * @param  mixed $returns 
	 * @return mixed 
	 */
	public static function getValueByKey(array $array, $key = null, $returns = null)
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
        		return $returns;
        	}
		}

		return $lastItem;
	}

}


?>
