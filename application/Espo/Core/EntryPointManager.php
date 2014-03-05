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

	protected $allowedMethods = array(
		'run',
	);	

	/**
     * @var array - path to entryPoint files
     */
	private $paths = array(
		'corePath' => 'application/Espo/EntryPoints',
    	'modulePath' => 'application/Espo/Modules/{*}/EntryPoints',
    	'customPath' => 'custom/Espo/Custom/EntryPoints',	                              			
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
		$classParser = $this->getContainer()->get('classParser');
		$classParser->setAllowedMethods($this->allowedMethods);
		$this->data = $classParser->getData($this->paths, $this->cacheFile);
	}	
	 

}

