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
	);  
	
	/**
     * @var array - path to entryPoint files in a custom folder
     */
	private $customPaths = array(
		'corePath' => 'application/Espo/Custom/EntryPoints',
   		'modulePath' => 'application/Espo/Custom/Modules/{*}/EntryPoints',	
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
		$className = $this->get($name);
		if ($className === false) {
			throw new NotFound();
		}
		return $className::$authRequired;		
	}

	public function run($name) 
	{
		$className = $this->get($name);
		if ($className === false) {
			throw new NotFound();
		}
		$entryPoint = new $className($this->container);

		$entryPoint->run();
	}

	protected function get($name = '')
	{
		if (!isset($this->data)) {
			$this->init();
		}

		if (empty($name)) {
			return $this->data; 	
		}

		$name = ucfirst($name);
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		
        return false; 
	}


	protected function getAll()
	{		
        return $this->get();        
	}

	protected function init()
	{
		if (file_exists($this->cacheFile) && $this->getContainer()->get('config')->get('useCache')) {
			$this->data = $this->getFileManager()->getContent($this->cacheFile);
		} else {		

			$this->data = $this->getData( array($this->paths['corePath'], $this->customPaths['corePath']) );
	    	foreach ($this->getContainer()->get('metadata')->getModuleList() as $moduleName) {
	    		$path = str_replace('{*}', $moduleName, $this->paths['modulePath']);
	    		$customPath = str_replace('{*}', $moduleName, $this->customPaths['modulePath']);

				$this->data = array_merge($this->data, $this->getData(array($path, $customPath)));
	    	}

			$result = $this->getFileManager()->setContentPHP($this->data, $this->cacheFile);
			 if ($result == false) {
			 	$GLOBALS['log']->add('EXCEPTION', 'EntryPoint::init() - Cannot save EntryPoints to a file');
	         	throw new \Espo\Core\Exceptions\Error();
			 }
		}
	}

	protected function getData(array $dirs)
	{
		$entryPoints = array();

		foreach ($dirs as $dir) {
	      
			if (file_exists($dir)) {
	        	$fileList = $this->getFileManager()->getFileList($dir, false, '\.php$', 'file');

	            foreach ($fileList as $file) {
					
					$filePath = Util::concatPath($dir, $file);

                	$className = Util::getClassName($filePath);       
                	$fileName = $this->getFileManager()->getFileName($filePath);  

					foreach ($this->allowMethods as $methodName) {	
						if (method_exists($className, $methodName)) {							
							$entryPoints[$fileName] = $className;
						}
					}					
					
	            }
			}

		}

		return $entryPoints;
	}   

}

