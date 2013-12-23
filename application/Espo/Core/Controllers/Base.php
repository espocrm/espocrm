<?php

namespace Espo\Core\Controllers;

use \Espo\Core\Container;
use \Espo\Core\ServiceFactory;
use \Espo\Core\Utils\Util;

abstract class Base
{
	protected $name;
	
	private $container;
	
	private $serviceFactory;
	
	protected $serviceClassName = null;
	
	protected $service = null;
	
	public $defaultAction = 'index';

	public function __construct(Container $container, ServiceFactory $serviceFactory)
	{
		$this->container = $container;
		$this->serviceFactory = $serviceFactory;
		
		if (empty($this->name)) {
			$name = get_class($this);
			if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
		    	$name = $matches[1];
			}
			$this->name = $name;
    	}  	
    	
    	if (empty($this->serviceClassName)) {
    		$moduleName = $this->getMetadata()->getScopeModuleName($this->name);
			if ($moduleName) {
				$className = '\\Espo\\Modules\\' . $moduleName . '\\Services\\' . Util::normilizeClassName($this->name);
			} else {
				$className = '\\Espo\\Services\\' . Util::normilizeClassName($this->name);
			}
    	}
	}
	
	protected function getContainer()
	{
		return $this->container;
	}
	
	protected function getUser()
	{
		return $this->container->get('user');
	}
	
	protected function getAcl()
	{
		return $this->container->get('acl');
	}
	
	protected function getConfig()
	{
		return $this->container->get('config');
	}
	
	protected function getMetadata()
	{
		return $this->container->get('metadata');
	}

	protected function getServiceFactory()
	{
		return $this->serviceFactory;
	}
	
	protected function loadService()
	{
		$this->service = $this->getServiceFactory()->createByClassName($this->serviceClassName);
	}
	
	protected function getService()
	{
		if (!empty($this->service)) {
			return $this->service;
		}
		$this->loadService();
		return $this->service;    	
	}
	 
}
