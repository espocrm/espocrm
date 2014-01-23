<?php

namespace Espo\Core\Controllers;

use \Espo\Core\Container;
use \Espo\Core\ServiceFactory;
use \Espo\Core\Utils\Util;

abstract class Base
{
	protected $name;
	
	private $container;
	
	public static $defaultAction = 'index';

	public function __construct(Container $container)
	{
		$this->container = $container;
		
		if (empty($this->name)) {
			$name = get_class($this);
			if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
		    	$name = $matches[1];
			}
			$this->name = $name;
    	}
    	
    	$this->checkGlobalAccess();
	}
	
	protected function checkGlobalAccess()
	{
		return;
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
	
	protected function getPreferences()
	{
		return $this->container->get('preferences');
	}
	
	protected function getMetadata()
	{
		return $this->container->get('metadata');
	}

	protected function getServiceFactory()
	{
		return $this->container->get('serviceFactory');
	}
	
	protected function getService($className)
	{
		return $this->getServiceFactory()->createByClassName($className);
	}
}

