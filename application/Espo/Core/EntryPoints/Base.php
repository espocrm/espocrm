<?php

namespace Espo\Core\EntryPoints;

use \Espo\Core\Container;

use \Espo\Core\Exceptions\Forbidden;

abstract class Base
{
	private $container;
	
	protected $authRequired = true;
	
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
		$this->getContainer()->get('entityManager');
	}
	
	protected function getServiceFactory()
	{
		$this->getContainer()->get('serviceFactory');
	}	
	
	protected function getConfig()
	{
		return $this->getContainer()->get('config');
	}
	
	protected function getMetadata()
	{
		return $this->getContainer()->get('metadata');
	}
	
	protected function checkAccess()
	{
		if ($this->authRequired) {
			return $this->getUser()->isFetched();
		}
		return false;
	}
	
	public function __construct(Container $container)
	{
		$this->container = $container;
    	if (!$this->checkAccess()) {
    		throw new Forbidden();
    	}
	}
	
	abstract public function run();	

}

