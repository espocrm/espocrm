<?php

namespace Espo\Core\Utils;

class Config 
{
	/**
	 * Path of default config file
     *
	 * @access private
	 * @var string
	 */
	private $defaultConfigPath = 'application/Espo/Core/defaults/config.php';

    /**
	 * Array of admin items
     *
	 * @access protected
	 * @var array
	 */
	protected $adminItems = array();


	/**
	 * Contains content of config
     *
	 * @access private
	 * @var array
	 */
	private $configData;

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
     * Get an option from config
	 *
	 * @param string $name
	 * @return string | array
	 */
	public function get($name)
	{
		$keys = explode('.', $name);

		$lastBranch = $this->loadConfig();
		foreach ($keys as $keyName) {
        	if (isset($lastBranch[$keyName]) && is_array($lastBranch)) {
            	$lastBranch = $lastBranch[$keyName];
        	} else {
        		return null;
        	}
		}

		return $lastBranch;
	}


	/**
     * Set an option to the config
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

        $content = array($name => $value);
		$status = $this->getFileManager()->mergeContentsPHP($this->get('configPath'), $content, true);
        $this->loadConfig(true);

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
        	$values = Json::decode($values);
        }

		if (!is_array($values)) {
        	return false;
		}

		$status = $this->getFileManager()->mergeContentsPHP($this->get('configPath'), $values, true);
        $this->loadConfig(true);

		return $status;
	}

    /**
     * Return an Object of all configs
     * @param  boolean $reload 
     * @return array()
     */
	protected function loadConfig($reload = false)
	{
		if (!$reload && isset($this->configData) && !empty($this->configData)) {
        	return $this->configData;
		}

		$defaultConfig = $this->getFileManager()->getContents($this->defaultConfigPath);

		$config = $this->getFileManager()->getContents($defaultConfig['configPath']);
		if (empty($config)) {
			$config = array();
		}

		$this->configData =  Util::merge((array) $defaultConfig, (array) $config);
		$this->adminItems = $this->getRestrictItems();

		return $this->configData;
	}


	/**
     * Get config acording to restrictions for a user
	 *
	 * @param $isAdmin
	 * @return array
	 */
	public function getData($isAdmin=false)
	{
        $configData = $this->loadConfig();

		$restrictedConfig = $configData;
		foreach($this->getRestrictItems($isAdmin) as $name) {
			if (isset($restrictedConfig[$name])) {
            	unset($restrictedConfig[$name]);
			}
		}		

		return $restrictedConfig;
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

		$restrictItems = $this->getRestrictItems($isAdmin);

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
	protected function getRestrictItems($onlySystemItems = false)
	{
		$configData = $this->loadConfig();

    	if ($onlySystemItems) {    		
        	return $configData['systemItems'];
    	}

		if (empty($this->adminItems)) {
        	$this->adminItems= Util::merge($configData['systemItems'], $configData['adminItems']);
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
