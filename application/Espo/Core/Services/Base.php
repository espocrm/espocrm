<?php

namespace Espo\Core\Services;

abstract class Base
{
	static public $dependencies = array();
	
	protected $injections = array();
	
	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}
 
}
