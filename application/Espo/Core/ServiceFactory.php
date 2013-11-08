<?php

namespace Espo\Core;

class ServiceFactory
{

	private $container;
	
	private $metadata;
	
    public function __construct(Container $container)
    {
    	$this->container = $container;

    }    
    
    public function create($name)
    {
    	// TODO lookup in metadata which module to use
    	$className = '\\Espo\\Services\\' . $name;
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
}
