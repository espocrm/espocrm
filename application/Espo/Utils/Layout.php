<?php

namespace Espo\Utils;

use Espo\Utils as Utils,
	Espo\Core,
	Doctrine\ORM\Tools;

class Layout extends FileManager
{

	protected $layoutConfig;

	/**
    * Get Layout context
	*
	* @param $controller
	* @param $name
	*
	* @return json
	*/
	function getLayout($controller, $name)
	{
		$fileFullPath = $this->concatPath($this->getLayoutPath($controller), $name.'.json');

		if (!file_exists($fileFullPath)) {

			//load defaults
			$defaultPath = $this->getObject('Configurator')->get('defaultsPath');
			$fileFullPath =  $this->concatPath( $this->concatPath($defaultPath, $this->getConfig()->name), $name.'.json' );
			//END: load defaults

			if (!file_exists($fileFullPath)) {
            	return false;
			}
		}

		return $this->getContent($fileFullPath);
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
	function mergeLayout($data, $controller, $name)
	{
		$layoutPath = $this->getLayoutPath($controller);

        /*//merge data with defaults values
        $defaults = $this->loadDefaultValues($name, $this->getConfig()->name);

        $decoded = $this->getArrayData($data);
        $mergedValues= $this->merge($defaults, $decoded);
		$data= $this->getObject('JSON')->encode($mergedValues);
        //END: merge data with defaults values */

        return $this->mergeContent($data, $layoutPath, $name.'.json', true);
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
	function setLayout($data, $controller, $name)
	{
		$layoutPath = $this->getLayoutPath($controller);

        return $this->setContent($data, $layoutPath, $name.'.json');
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
    	$moduleName= $this->getScopeModuleName($entityName);

    	$path= $this->getConfig()->corePath;
		if (!empty($moduleName)) {
			$path= str_replace('{*}', $moduleName, $this->getConfig()->customPath);
		}
        $path= $this->concatPath($path, $entityName);

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
	function getConfig()
	{
		if (isset($this->layoutConfig) && is_object($this->layoutConfig)) {
    		return $this->layoutConfig;
    	}

		$this->layoutConfig = $this->getObject('Configurator')->get('layoutConfig');

		return $this->layoutConfig;
	}

}


?>