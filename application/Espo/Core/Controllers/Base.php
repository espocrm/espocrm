<?php

namespace Espo\Core\Controllers;

class Base
{
	private $container;
	private $serviceFactory;

	public function __construct(\Espo\Core\Container $container, \Espo\Core\ServiceFactory $serviceFactory)
	{
		$this->container = $container;
		$this->serviceFactory = $serviceFactory;
	}

	protected function getContainer()
	{
		return $this->container;
	}
	
	protected function getUser()
	{
		return $this->container->get('user');
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
 
}
