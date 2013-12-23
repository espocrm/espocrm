<?php

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
    * Convert name to Camel Case format
   	* ex. camel-case to camelCase
	*
	* @param string $name
	*
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

}


?>
