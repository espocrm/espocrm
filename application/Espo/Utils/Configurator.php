<?php

namespace Espo\Utils;
use Espo\Utils as Utils;

class Configurator extends BaseUtils
{
	/**
	* Path of system config file
    *
	* @access private
	* @var string
	*/
	private $systemConfigPath = 'application/Espo/Core/systemConfig.php';

    /**
	* Array of admin items
    *
	* @access protected
	* @var array
	*/
	protected $adminItems= array();


	/**
	* Contains content of system config
    *
	* @access private
	* @var string
	*/
	private $lastConfigObj;


	/**
    * Get an option from system config
	*
	* @param string $name
	* @return string | array
	*/
	public function get($name)
	{
		$contentObj = $this->getConfig();

        if (isset($contentObj->$name)) {
        	return $contentObj->$name;
        }

		return null;
	}


	/**
    * Set an option to the system config
	*
	* @param string $name
	* @param string $value
	* @return bool
	*/
	public function set($name, $value='')
	{
        if (Utils\JSON::isJSON($value)) {
        	$value= Utils\JSON::decode($value);
        }

        $content= array($name => $value);
		$status= $this->getObject('FileManager')->mergeContentPHP($content, $this->get('configPath'), '', true);
        $this->getConfig(true);

		return $status;
	}


	/**
    * Set options from array
	*
	* @param array $values
	* @return bool
	*/
	public function setArray($values)
	{
		if (Utils\JSON::isJSON($values)) {
        	$values= Utils\JSON::decode($values);
        }

		if (!is_array($values)) {
        	return false;
		}

		$status= $this->getObject('FileManager')->mergeContentPHP($values, $this->get('configPath'), '', true);
        $this->getConfig(true);

		return $status;
	}

    /**
    * Return an Object of all configs
	*
	* @return object
	*/
	function getConfig($reload=false)
	{
		if (!$reload && isset($this->lastConfigObj) && !empty($this->lastConfigObj)) {
        	return $this->lastConfigObj;
		}

		$fileManager= $this->getObject('FileManager');
		$systemConfig= $fileManager->getContent($this->systemConfigPath);

		$config= $fileManager->getContent($systemConfig['configPath']);
		if (empty($config)) {
			$this->getObject('Log')->add('FATAL', 'Check syntax or permission of your '.$systemConfig['configPath']);
		}

		$this->lastConfigObj = $this->arrayToObject( $this->merge((array) $systemConfig, (array) $config) );
		$this->adminItems= $this->getRestrictItems();

		return $this->lastConfigObj;
	}


	/**
    * Get JSON config acording to restrictions for a user
	*
	* @param $isAdmin
	* @return object
	*/
	public function getJSON($isAdmin=false, $encode=true)
	{
        $configObj = $this->getConfig();

		$restrictedConfig= $configObj;
		foreach($this->getRestrictItems($isAdmin) as $name) {
			if (isset($restrictedConfig->$name)) {
            	unset($restrictedConfig->$name);
			}
		}

		if (!$encode) {
			return $restrictedConfig;
		}

		return Utils\JSON::encode( $this->objectToArray($restrictedConfig) );
	}


	/**
    * Set JSON data acording to restrictions for a user
	*
	* @param $isAdmin
	* @return bool
	*/
	//HERE
	public function setJSON($json, $isAdmin=false)
	{
		$decoded= Utils\JSON::decode($json, true);

		$restrictItems= $this->getRestrictItems($isAdmin);

		$values= array();
        foreach($decoded as $key => $item) {
        	if (!in_array($key, $restrictItems)) {
				$values[$key]= $item;
        	}
        }

		return $this->setArray($values);
	}

	/**
    * Get admin items
	*
	* @return object
	*/
	function getRestrictItems($onlySystemItems=false)
	{
    	if ($onlySystemItems) {
        	return ((array) $this->getConfig()->systemItems);
    	}

		if (empty($this->adminItems)) {
        	//$this->adminItems= array_merge( (array) $this->getConfig()->systemItems, (array) $this->getConfig()->adminItems );
        	$this->adminItems= $this->merge( (array) $this->getConfig()->systemItems, (array) $this->getConfig()->adminItems );
		}

		return $this->adminItems;
	}


    /**
    * Check if an item is allowed to get and save
	*
	* @param $name
	* @param $isAdmin
	* @return bool
	*/
	protected function isAllowed($name, $isAdmin=false)
	{
        if (in_array($name, $this->getRestrictItems($isAdmin))) {
        	return false;
        }

		return true;
	}
}

?>