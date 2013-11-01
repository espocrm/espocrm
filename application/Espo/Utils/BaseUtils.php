<?php

namespace Espo\Utils;

use Espo\Utils as Utils;

class BaseUtils
{

	function getObject($name)
	{
		if (isset($this->$name) && is_object($this->$name)) {
    		return $this->$name;
    	}      

		$fullName= 'Espo\\Utils\\'.$name;
		if (class_exists($fullName)) {
			$this->$name= new $fullName();
			return $this->$name;
		}

		return false;
	}

	/**
    * Get module name if it's a custom module or empty string for core entity
	*
	* @param string $entityName
	*
	* @return string
	*/
	public function getScopeModuleName($entityName)
	{
    	$scopeModuleMap= (array) $this->getObject('Configurator')->get('scopeModuleMap');

		$lowerEntityName= strtolower($entityName);
		foreach($scopeModuleMap as $rowEntityName => $rowModuleName) {
			if ($lowerEntityName==strtolower($rowEntityName)) {
				return $rowModuleName;
			}
		}

		return '';
	}

	/**
    * Convert name to Camel Case format
   	* ex. camel-case to camelCase
	*
	* @param string $name
	*
	* @return string
	*/
	public function toCamelCase($name, $capitaliseFirstChar=false)
	{
		if($capitaliseFirstChar) {
			$name[0] = strtoupper($name[0]);
		}
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/-([a-z])/', $func, $name);
	}

	/**
    * Convert name from Camel Case format.
	* ex. camelCase to camel-case
	*
	* @param string $name
	*
	* @return string
	*/
	public function fromCamelCase($name)
	{
		$name[0] = strtolower($name[0]);
		$func = create_function('$c', 'return "-" . strtolower($c[1]);');
		return preg_replace_callback('/([A-Z])/', $func, $name);
	}


	/**
	* Merge arrays (default PHP function is not suitable)
	*
	* @param array $array
	* @param array $mainArray - chief array (priority is same as for array_merge())
	* @return array
	*/
	function merge($array, $mainArray)
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
						$array[$maKey] = $this->merge($aVal, $maVal);
					}
					else {

						if (is_array($aVal)){
							$array[$maKey] = $this->merge($aVal, array($maVal));
						}
						elseif (is_array($maVal)){
							$array[$maKey] = $this->merge(array($aVal), $maVal);
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
    * Return content of PHP file
	*
	* @param string $varName - name of variable which contains the content
	* @param array $content
	*
	* @return string | false
	*/
	function getPHPFormat($content)
	{
		if (empty($content)) {
            return false;
		}

        	return '<?php

return '.var_export($content, true).';

?>';
	}


	/**
    * Convert array to object format recursively
	*
	* @param array $array
	* @return object
	*/
	function arrayToObject($array)
	{
		if (is_array($array)) {
			return (object) array_map(array($this, "arrayToObject"), $array);
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
	function objectToArray($object)
	{
    	if (is_object($object)) {
			$object = (array) $object;
    	}

        return is_array($object) ? array_map(array($this, "objectToArray"), $object) : $object;
	}

}


?>