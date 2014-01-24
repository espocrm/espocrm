<?php

namespace Espo\Core\ORM;

use \Espo\Core\Interfaces\Injectable;

abstract class Repository extends \Espo\ORM\Repository implements Injectable
{
	protected $dependencies = array();
	
	protected $injections = array();
	
	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}	
	
	protected function getInjection($name)
	{
		return $this->injections[$name];
	}
	
	public function getDependencyList()
	{
		return $this->dependencies;
	}	
	
	protected function getMetadata()
	{
		return $this->metadata;
	}	
	
	public function setMetadata($metadata)
	{
		$this->metadata = $metadata;
	}
}

