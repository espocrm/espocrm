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
		if (version_compare(phpversion(), '5.5.0', '>=')) {
	        $json = json_encode($value, $options, $depth);
	    }
	    elseif (version_compare(phpversion(), '5.3.0', '>=')) {
			/*Check if options are supported for this version of PHP*/
	    	if (is_int($options)) {
            	$json = json_encode($value, $options);
	    	}
			else {
				$json = json_encode($value);
			}
	    }
	    else {
	        $json = json_encode($value);
	    }

		if ($json===null) {
			$GLOBALS['log']->error('Value cannot be decoded to JSON - '.print_r($value, true));
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
		if (is_array($json)) {
			$GLOBALS['log']->warning('JSON:decode() - JSON cannot be decoded - '.$json);
			return false;
		}

	    if(version_compare(phpversion(), '5.4.0', '>=')) {
	        $json = json_decode($json, $assoc, $depth, $options);
	    }
	    elseif(version_compare(phpversion(), '5.3.0', '>=')) {
	        $json = json_decode($json, $assoc, $depth);
	    }
	    else {
	        $json = json_decode($json, $assoc);
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
	public static function getArrayData($data)
	{
		if (is_array($data)) {
        	return $data;
		}
		else if (static::isJSON($data)) {
        	return static::decode($data, true);
        }

		return array();
	}

}

