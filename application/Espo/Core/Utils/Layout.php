<?php

namespace Espo\Core\Utils;

class Layout
{

	private $layoutConfig;

	private $config;
	private $fileManager;
	private $metadata;


	public function __construct(\Espo\Core\Utils\Config $config, \Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Metadata $metadata)
	{
		$this->config = $config;
		$this->fileManager = $fileManager;
		$this->metadata = $metadata;
	}

	protected function getConfig()
	{
    	return $this->config;
	}

	protected function getFileManager()
	{
    	return $this->fileManager;
	}

	protected function getMetadata()
	{
    	return $this->metadata;
	}


	/**
    * Get Layout context
	*
	* @param $controller
	* @param $name
	*
	* @return json
	*/
	function get($controller, $name)
	{
		$fileFullPath = Util::concatPath($this->getLayoutPath($controller), $name.'.json');

		if (!file_exists($fileFullPath)) {

			//load defaults
			$defaultPath = $this->getConfig()->get('defaultsPath');
			$fileFullPath =  Util::concatPath( Util::concatPath($defaultPath, $this->getLayoutConfig()->name), $name.'.json' );
			//END: load defaults

			if (!file_exists($fileFullPath)) {
            	return false;
			}
		}

		return $this->getFileManager()->getContent($fileFullPath);
	}


	/**
	* Merge layout data
	* Ex. $controller= Account, $name= detail then will be created a file layoutFolder/Account/detail.json
    *
	* @param JSON string $data
	* @param string $controller - ex. Account
	* @param string $name - detail
	*
	* @return bool
	*/
	function merge($data, $controller, $name)
	{
		$layoutPath = $this->getLayoutPath($controller);

        /*//merge data with defaults values
        $defaults = $this->loadDefaultValues($name, $this->getLayoutConfig()->name);

        $decoded = $this->getArrayData($data);
        $mergedValues= $this->merge($defaults, $decoded);
		$data= $this->getObject('JSON')->encode($mergedValues);
        //END: merge data with defaults values */

        return $this->getFileManager()->mergeContent($data, $layoutPath, $name.'.json', true);
	}


	/**
	* Set Layout data
	* Ex. $controller= Account, $name= detail then will be created a file layoutFolder/Account/detail.json
    *
	* @param JSON string $data
	* @param string $controller - ex. Account
	* @param string $name - detail
	*
	* @return bool
	*/
	function set($data, $controller, $name)
	{
		if (empty($controller) || empty($name)) {
			return false;
		}
		
		$layoutPath = $this->getLayoutPath($controller);

        return $this->getFileManager()->setContent($data, $layoutPath, $name.'.json');
	}

    /**
    * Get Layout path, ex. application/Modules/Crm/Layouts/Account
    *
	* @param string $entityName
	* @param bool $delim - delimiter
	*
	* @return string
	*/
	public function getLayoutPath($entityName, $delim= '/')
	{
    	$moduleName= $this->getMetadata()->getScopeModuleName($entityName);

    	$path= $this->getLayoutConfig()->corePath;
		if ($moduleName !== false) {
			$path= str_replace('{*}', $moduleName, $this->getLayoutConfig()->customPath);
		}
        $path= Util::concatPath($path, $entityName);

		if ($delim!='/') {
           $path = str_replace('/', $delim, $path);
		}

		return $path;
	}


	/**
    * Get settings for Layout
	*
	* @return object
	*/
	protected function getLayoutConfig()
	{
		if (isset($this->layoutConfig) && is_object($this->layoutConfig)) {
    		return $this->layoutConfig;
    	}

		$this->layoutConfig = $this->getConfig()->get('layoutConfig');

		return $this->layoutConfig;
	}

}


?>