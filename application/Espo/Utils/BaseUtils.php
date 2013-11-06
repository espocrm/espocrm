<?php

namespace Espo\Utils;

use Espo\Utils as Utils;

class BaseUtils
{
    /**
	* @var string - default directory separator
	*/
	protected $separator= DIRECTORY_SEPARATOR;

	/**
	* @var array - scope list
	*/
	protected $scopes= array();


	/**
    * Get a folder separator
	*
	* @return string
	*/
    public function getSeparator()
	{
		return $this->separator;
	}


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
	* @param string $scopeName
	*
	* @return string
	*/
	public function getScopeModuleName($scopeName)
	{
    	$scopeModuleMap= $this->getScopes();

		$lowerEntityName= strtolower($scopeName);
		foreach($scopeModuleMap as $rowEntityName => $rowModuleName) {
			if ($lowerEntityName==strtolower($rowEntityName)) {
				return $rowModuleName;
			}
		}

		return '';
	}


	/**
    * Get Scopes
	*
	* @param string $moduleName
	* @param bool $reload
	*
	* @return array
	*/
	//NEED TO CHANGE
	public function getScopes($moduleName= '', $reload = false)
	{
    	if (!$reload && !empty($this->scopes)) {
    		return $this->scopes;
    	}


		$this->scopes = array(
			'customTest' => '',
			'Attachment' => '',
			'Comment' => '',
			'Attachment' => '',
			'EmailTemplate' => '',
			'Role' => '',
			'Team' => '',
			'User' => '',
			'OutboundEmail' => '',
			'Product' => 'Crm',
			'Account' => 'Crm',
			'Contact' => 'Crm',
			'Lead' => 'Crm',
			'Opportunity' => 'Crm',
			'Calendar' => 'Crm',
			'Meeting' => 'Crm',
			'Call' => 'Crm',
			'Task' => 'Crm',
			'Case' => 'Crm',
			'Prospect' => 'Crm',
			'Email' => 'Crm',
			'emailTemplate' => 'Crm',
			'inboundEmail' => 'Crm',
		);

		return $this->scopes;
	}


	/**
    * Get Scope path, ex. "Modules/Crm" for Account
    *
	* @param string $scopeName
	* @param string $delim - delimiter
	*
	* @return string
	*/
	public function getScopePath($scopeName, $delim= '/')
	{
    	$moduleName= $this->getScopeModuleName($scopeName);

		$config = new Utils\Configurator();
    	$path= $config->get('espoPath');
		if (!empty($moduleName)) {
			$path= str_replace('{*}', $moduleName, $config->get('espoModulePath'));
		}

		if ($delim!='/') {
           $path = str_replace('/', $delim, $path);
		}

		return $path;
	}


	/**
    * Get Full Scope path, ex. "application/Modules/Crm" for Account
    *
	* @param string $scopeName
	* @param string $delim - delimiter
	*
	* @return string
	*/
	public function getScopePathFull($scopeName, $delim= '/')
	{
		return $this->concatPath('application', $this->getScopePath($scopeName, $delim));
	}

	/**
    * Check if scope exists
	*
	* @param string $scopeName
	*
	* @return bool
	*/
	public function isScopeExists($scopeName)
	{
    	$scopeModuleMap= $this->getScopes();

		$lowerEntityName= strtolower($scopeName);
		foreach($scopeModuleMap as $rowEntityName => $rowModuleName) {
			if ($lowerEntityName==strtolower($rowEntityName)) {
				return true;
			}
		}

		return false;
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
	public function toFormat($name, $delim= '/')
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
    * Get a full path of the file
	*
	* @param string $folderPath - Folder path, Ex. myfolder
	* @param string $filePath - File path, Ex. file.json
	*
	* @return string
	*/
	public function concatPath($folderPath, $filePath='')
	{
		if (empty($filePath)) {
        	return $folderPath;
    	}
		else {
            if (substr($folderPath, -1)==$this->getSeparator()) {
            	return $folderPath . $filePath;
            }
        	return $folderPath . $this->getSeparator() . $filePath;
		}
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
