<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

use \Espo\Core\Utils\Util;

class ServiceFactory
{	
	private $container;
	
	protected $cacheFile = 'data/cache/application/services.php';
	
	/**
     * @var array - path to Service files
     */
	private $paths = array(
		'corePath' => 'application/Espo/Services',
    	'modulePath' => 'application/Espo/Modules/{*}/Services',
    	'customPath' => 'application/Espo/Custom/Services',	                              			
	);
	
	protected $data;	

    public function __construct(Container $container)
    {
    	$this->container = $container;    	
    }       
    
	protected function init()
	{
		$config = $this->getContainer()->get('config');
		
		if (file_exists($this->cacheFile) && $config->get('useCache')) {
			$this->data = $this->getFileManager()->getContent($this->cacheFile);
		} else {	
			$this->data = $this->getClassNameHash(array($this->paths['corePath'], $this->paths['customPath']));

	    	foreach ($this->getContainer()->get('metadata')->getModuleList() as $moduleName) {
	    		$path = str_replace('{*}', $moduleName, $this->paths['modulePath']);
				$this->data = array_merge($this->data, $this->getClassNameHash(array($path)));
	    	}	    	
			if ($config->get('useCache')) {
				$result = $this->getFileManager()->setContentPHP($this->data, $this->cacheFile);
				if ($result == false) {
					throw new \Espo\Core\Exceptions\Error();
				}
			}
		}
	}
	
	protected function getFileManager()
	{
		return $this->container->get('fileManager');
	}
	
	protected function getContainer()
	{
		return $this->container;
	}
	
	protected function getClassName($name)
	{
		$name = Util::normilizeClassName($name);
		
		if (!isset($this->data)) {
			$this->init();
		}

		$name = ucfirst($name);
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		
        return false; 
	}
	
	public function checkExists($name) {
		$className = $this->getClassName($name);
		if (!empty($className)) {
			return true;
		}
	}
    
    public function create($name)
    {
    	$className = $this->getClassName($name);
    	if (empty($className)) {
    		throw new Error();
    	}
    	return $this->createByClassName($className);
    }  

	protected function createByClassName($className)
	{
    	if (class_exists($className)) {
    		$service = new $className();
    		$dependencies = $service->getDependencyList();
    		foreach ($dependencies as $name) {
    			$service->inject($name, $this->container->get($name));
    		}
    		return $service;
    	}
    	throw new Error("Class '$className' does not exist");
	}
	
	// TODO delegate to another class
	protected function getClassNameHash(array $dirs)
	{
		$data = array();
		
		foreach ($dirs as $dir) {	      
			if (file_exists($dir)) {
	        	$fileList = $this->getFileManager()->getFileList($dir, false, '\.php$', 'file');
	            foreach ($fileList as $file) {					
					$filePath = Util::concatPath($dir, $file);
                	$className = Util::getClassName($filePath);       
                	$fileName = $this->getFileManager()->getFileName($filePath);							
					$data[$fileName] = $className;					
	            }
			}
		}
		return $data;
	}	
}

