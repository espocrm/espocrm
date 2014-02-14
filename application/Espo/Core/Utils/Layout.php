<?php

namespace Espo\Core\Utils;

class Layout
{
	private $config;
	private $fileManager;
	private $metadata;   
	
	/**
     * @var string - uses for loading default values
     */
	private $name = 'layout';       	
	
	/**
     * @var array - path to layout files
     */
	private $paths = array(
		'corePath' => 'application/Espo/Resources/layouts',
    	'modulePath' => 'application/Espo/Modules/{*}/Resources/layouts',		                              			
	);  
	
	/**
     * @var array - path to layout files in custom folder
     */
	private $customPaths = array(
		'corePath' => 'application/Espo/Custom/Resources/layouts',
   		'modulePath' => 'application/Espo/Custom/Modules/{*}/Resources/layouts',	
	); 


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
		$fileFullPath = Util::concatPath($this->getLayoutPath($controller, true), $name.'.json');   		
		if (!file_exists($fileFullPath)) {
			$fileFullPath = Util::concatPath($this->getLayoutPath($controller), $name.'.json');	
		}                                                                               		

		if (!file_exists($fileFullPath)) {     
			//load defaults
			$defaultPath = $this->getConfig()->get('defaultsPath');
			$fileFullPath =  Util::concatPath( Util::concatPath($defaultPath, $this->name), $name.'.json' );
			//END: load defaults

			if (!file_exists($fileFullPath)) {
            	return false;
			}
		}

		return $this->getFileManager()->getContents($fileFullPath);
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
		
		$layoutPath = $this->getLayoutPath($controller, true);
		
		if (!Json::isJSON($data)) {
			$data = Json::encode($data);	
		}   

        return $this->getFileManager()->putContents(array($layoutPath, $name.'.json'), $data);
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
		$prevData = $this->get($controller, $name);
		
		$prevDataArray= Json::getArrayData($prevData);
		$dataArray= Json::getArrayData($data);

        $data= Util::merge($prevDataArray, $dataArray);   		
	    $data= Json::encode($data);   		

        return $this->set($data, $controller, $name);
	}  	

    /**
     * Get Layout path, ex. application/Modules/Crm/Layouts/Account
     *
	 * @param string $entityName
	 * @param bool $isCustom - if need to check custom folder
	 *
	 * @return string
	 */
	public function getLayoutPath($entityName, $isCustom = false)
	{                                                                    
		$paths = $isCustom ? $this->customPaths : $this->paths;
	
    	$moduleName = $this->getMetadata()->getScopeModuleName($entityName);
		
    	$path = $paths['corePath'];
		if ($moduleName !== false) {
			$path = str_replace('{*}', $moduleName, $paths['modulePath']);
		}
        $path = Util::concatPath($path, $entityName);

		return $path;
	}  
 

}


?>