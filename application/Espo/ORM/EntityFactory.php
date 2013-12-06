<?php

namespace Espo\ORM;

class EntityFactory
{	
	protected $metadata;
	
	public function __construct(Metadata $metadata)
	{
		$this->metadata = $metadata;
	
	}
	public function create($name)
	{
		$className = $this->normalizeName($name);		
		$defs = $this->metdata->get($name);		
		$entity = new $className($defs);
		return $entity;
	}
	
	protected function normalizeName($name)
	{
		return $name;
	}
}


