<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\NotFound,
	\Espo\Core\Utils\Util;


class EntryPointManager
{
	private $container;	
	
	private $fileManager;	

	protected $data = null;

	protected $cacheFile = 'data/cache/application/entryPoints.php';

	protected $allowMethods = array(
		'run',
	);	

	/**
     * @var array - path to entryPoint files
     */
	private $paths = array(
		'corePath' => 'application/Espo/EntryPoints',
    	'modulePath' => 'application/Espo/Modules/{*}/EntryPoints',
    	'customPath' => 'application/Espo/Custom/EntryPoints',	                              			
	);


	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;		
		$this->fileManager = $container->get('fileManager');		
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}

	public function checkAuthRequired($name)
	{
		$className = $this->getClassName($name);
		if ($className === false) {
			throw new NotFound();
		}
		return $className::$authRequired;		
	}

	public function run($name) 
	{
		$className = $this->getClassName($name);
		if ($className === false) {
			throw new NotFound();
		}
		$entryPoint = new $className($this->container);

		$entryPoint->run();
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


	protected function init()
	{
		$config = $this->getContainer()->get('config');
	
		if (file_exists($this->cacheFile) && $config->get('useCache')) {
			$this->data = $this->getFileManager()->getContent($this->cacheFile);
		} else {	
			$this->data = $this->getClassNameHash(array($this->paths['corePath'], $this->paths['customPath']) );
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
                	$fileName = ucfirst($fileName); 

					foreach ($this->allowMethods as $methodName) {	
						if (method_exists($className, $methodName)) {							
							$data[$fileName] = $className;
						}
					}					
					
	            }
			}
		}
		return $data;
	}   

}

