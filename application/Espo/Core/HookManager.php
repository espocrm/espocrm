<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

class HookManager
{	
	private $container;
	
	private $data;
	
	private $hooks;
	
	protected $cacheFile = 'data/cache/application/hooks.php';

    public function __construct(Container $container)
    {
    	$this->container = $container;
    	$this->loadHooks();     	
    }
    
    protected getConfig()
    {
    	return $this->container->get('config');
    }
    
    protected function loadHooks()
    {    
    	if ($this->getConfig()->get('useCache') && file_exists($this->cacheFile)) {
    		$this->hooks = include($this->cacheFile);
    		return;
    	} 
    	
    	$metadata = $this->container->get('metadata');
    	
    	// TODO scan Espo/Hooks/{ScopeName}/{HookName}.php
    	foreach ($metadata->getModuleList() as $moduleName) {
    		// TODO scan Espo/Modules/{$moduleName}/ScopeName/HookName.php
    	}
    
    	if ($this->getConfig()->get('useCache')) {
    		// TODO write $this->hooks into cache file
    	}
    }
    
    public function process($scope, $hookName, $injection = null)
    {	
    	if (!empty($this->data[$scope])) {
    		if (!empty($this->data[$scope][$hookName])) {
    			foreach ($this->data[$scope][$hookName] as $className) {
    				if (empty($this->hooks[$className])) {
    					$this->hooks[$className] = $this->createHookByClassName($className);
    				} 
    				$hook = $this->hooks[$className];    				
    				$hook->$hookName($injection);				
    			}
    		}
    	}    	
    }
	
	public function createHookByClassName($className)
	{
    	if (class_exists($className)) {
    		$hook = new $className();
    		$dependencies = $hook::$dependencies;
    		foreach ($dependencies as $name) {
    			$hook->inject($name, $this->container->get($name));
    		}
    		return $hook;
    	}
    	throw new Error("Class '$className' does not exist");
	}
}

