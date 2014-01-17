<?php

namespace Espo\Core\Hooks;

use \Espo\Core\Interfaces\Injectable;

class Base implements Injectable
{	
	protected $dependencies = array(
		'entityManager',
		'config',
		'metadata',
		'acl',
		'user',
	);
	
	protected $injections = array();
	
	public static $order = 9;
	
	public function __construct()
	{
		$this->init();
	}
	
	protected function init()
	{	
	}
	
	public function getDependencyList()
	{
		return $this->dependencies;
	}
	
	protected function getInjection($name)
	{
		return $this->injections[$name];
	}
	
	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}
		
	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}
	
	protected function getAcl()
	{
		return $this->injections['acl'];
	}
	
	protected function getConfig()
	{
		return $this->injections['config'];
	}
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	protected function getRepository()
	{		
		return $this->getEntityManager()->getRepository($this->entityName);
	}	

}

