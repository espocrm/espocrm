<?php

namespace Espo\Core\Services;

use \Espo\Core\Interfaces\Injectable;

abstract class Base implements Injectable
{
	protected $dependencies = array();
	
	protected $injections = array();
	
	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}
	
	public function __construct()
	{
		$this->init();
	}	
	
	protected function init()
	{	
	}
	
	protected function getInjection($name)
	{
		return $this->injections[$name];
	}
	
	public function getDependencyList()
	{
		return $this->dependencies;
	}
}

