<?php

namespace Espo\ORM;

class EntityFactory
{	
	public function create($className)
	{
		$className = $this->normalizeName($name);
		
		$entity = new $className();	
		return $entity;
	}
	
	protected function normalizeName($name)
	{
		return $name;
	}
}


