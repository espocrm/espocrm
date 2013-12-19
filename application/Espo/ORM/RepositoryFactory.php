<?php

namespace Espo\ORM;

class RepositoryFactory
{
	protected $entityFactroy;
	
	protected $entityManager;
	
	protected $defaultRepositoryClassName = '\\Espo\\ORM\\Repository';	

	public function __construct(EntityManager $entityManager, EntityFactory $entityFactroy, DB\IMapper $mapper)
	{
		$this->entityManager = $entityManager;
		$this->entityFactroy = $entityFactroy;
		$this->mapper = $mapper;		
	}
	
	public function create($name)
	{
		$className = $this->entityManager->normalizeRepositoryName($name);
		
		if (!class_exists($className)) {
			$className = $this->defaultRepositoryClassName;
		}
		$repository = new $className($name, $this->entityManager, $this->entityFactroy, $this->mapper);	
		return $repository;
	}
	
	protected function normalizeName($name)
	{
		return $name;
	}
	
	public function setDefaultRepositoryClassName($defaultRepositoryClassName)
	{
		$this->defaultRepositoryClassName = $defaultRepositoryClassName;
	}
}

