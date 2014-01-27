<?php

namespace Espo\Core\EntryPoints;

use \Espo\Core\Container;

use \Espo\Core\Exceptions\Forbidden;

abstract class Base
{
	private $container;
	
	public static $authRequired = true;
	
	protected function getContainer()
	{
		return $this->container;
	}
	
	protected function getUser()
	{
		return $this->getContainer()->get('user');
	}
	
	protected function getAcl()
	{
		return $this->getContainer()->get('acl');
	}
	
	protected function getEntityManager()
	{
		return $this->getContainer()->get('entityManager');
	}
	
	protected function getServiceFactory()
	{
		return $this->getContainer()->get('serviceFactory');
	}	
	
	protected function getConfig()
	{
		return $this->getContainer()->get('config');
	}
	
	protected function getMetadata()
	{
		return $this->getContainer()->get('metadata');
	}	
	
	public function __construct(Container $container)
	{
		$this->container = $container;
	}
	
	abstract public function run();	

}

