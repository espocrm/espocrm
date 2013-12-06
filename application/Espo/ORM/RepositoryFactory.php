<?php

namespace Espo\ORM;

class RepositoryFactory
{
	protected $entityFactroy;	

	public function __construct(EntityFactory $entityFactroy, DB\IMapper $mapper)
	{
		$this->entityFactroy = $entityFactroy;
		$this->mapper = $mapper;		
	}
	
	public function create($name)
	{
		$className = $this->normalizeName($name);		
		$repository = new $className($name, $this->entityFactroy, $this->mapper);	
		return $repository;
	}
	
	protected function normalizeName($name)
	{
		return $name;
	}
}


