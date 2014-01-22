<?php

namespace Espo\Core\Utils;

class Config 
{
	/**
	* Path of system config file
    *
	* @access private
	* @var string
	*/
	private $systemConfigPath = 'application/Espo/Core/defaults/systemConfig.php';

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

	private $fileManager;


	public function __construct(\Espo\Core\Utils\File\Manager $fileManager) //TODO
	{
		$this->fileManager = $fileManager;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}



	/**
    * Get an option from system config
	*
	* @param string $name
	* @return string | array
	*/
	public function get($name)
	{
		$keys = explode('.', $name);

		$lastBranch = $this->getConfig();
		foreach($keys as $keyName) {
        	if (isset($lastBranch->$keyName) && is_object($lastBranch)) {
            	$lastBranch = $lastBranch->$keyName;
        	} else {
        		return null;
        	}
		}

		return $lastBranch;
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
        if (Json::isJSON($value)) {
        	$value= Json::decode($value);
        }

        $content= array($name => $value);
		$status= $this->getFileManager()->mergeContentPHP($content, $this->get('configPath'), '', true);
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
		if (Json::isJSON($values)) {
        	$values= Json::decode($values);
        }

		if (!is_array($values)) {
        	return false;
		}

		$status= $this->getFileManager()->mergeContentPHP($values, $this->get('configPath'), '', true);
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

		$systemConfig= $this->getFileManager()->getContent($this->systemConfigPath);

		$config= $this->getFileManager()->getContent($systemConfig['configPath']);
		if (empty($config)) {
			$GLOBALS['log']->add('FATAL', 'Check syntax or permission of your '.$systemConfig['configPath']);
		}

		$this->lastConfigObj = Util::arrayToObject( Util::merge((array) $systemConfig, (array) $config) );
		$this->adminItems= $this->getRestrictItems();

		return $this->lastConfigObj;
	}


	/**
    * Get JSON config acording to restrictions for a user
	*
	* @param $isAdmin
	* @return object
	*/
	public function getData($isAdmin=false, $encode=true)
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

		return Util::objectToArray($restrictedConfig);
	}


	/**
    * Set JSON data acording to restrictions for a user
	*
	* @param $isAdmin
	* @return bool
	*/
	//HERE
	public function setData($data, $isAdmin=false)
	{

		$restrictItems= $this->getRestrictItems($isAdmin);

		$values= array();
        foreach($data as $key => $item) {
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
	protected function getRestrictItems($onlySystemItems=false)
	{
    	if ($onlySystemItems) {
        	return ((array) $this->getConfig()->systemItems);
    	}

		if (empty($this->adminItems)) {
        	$this->adminItems= Util::merge( (array) $this->getConfig()->systemItems, (array) $this->getConfig()->adminItems );
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
