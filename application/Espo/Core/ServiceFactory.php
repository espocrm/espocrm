<?php

namespace Espo\Core;

class ServiceFactory
{

	private $container;

	private $metadata;

    public function __construct(Container $container)
    {
    	$this->container = $container;
    	$this->metadata = $this->container->get('metadata');
    }

    protected function getCotainer()
	{
    	return $this->container;
	}

	public function createByClassName()
	{
    	if (class_exists($className)) {
    		$service = new $className();
    		$dependencies = $service->dependencies;
    		foreach ($dependencies as $name) {
    			$setMethod = 'set' . ucfirst($name);
    			$service->$setMethod($this->container->get($name));
    		}
    		return $service;
    	}
    	// TODO throw an exception
    	return null;
	}


    public function create($name)
    {
    	$moduleName = $this->metadata->getScopeModuleName($name);
    	if ($moduleName) {
    		$className = '\\Espo\\Modules\\' . $moduleName . '\\Services\\' . $name;
    	} else {
    		$className = '\\Espo\\Services\\' . $name;
    	}
    	return $this->createByClassName($className);
    }

}
