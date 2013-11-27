<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

class ServiceFactory
{
	
	private $container;

	private $metadata;

    public function __construct(Container $container)
    {
    	$this->container = $container;
    }

	public function createByClassName($className)
	{
    	if (class_exists($className)) {
    		$service = new $className();
    		$dependencies = $service::$dependencies;
    		foreach ($dependencies as $name) {
    			$setMethod = 'set' . ucfirst($name);
    			$service->$setMethod($this->container->get($name));
    		}
    		return $service;
    	}
    	throw new Error("Class '$className' does not exist");
	}
}
