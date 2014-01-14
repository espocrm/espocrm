<?php

namespace Espo\Core\Hooks;

class Base
{	
	static public $dependencies = array();
	
	protected $injections = array(
		'entityManager',
		'config',
		'metadata',
		'acl',
		'user',
	);
	
	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}
	
	public static $order = 9;
	
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

