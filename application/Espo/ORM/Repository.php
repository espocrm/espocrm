<?php

namespace Espo\ORM;

abstract class Repository
{	
	/**
	 * @var EntityFactory EntityFactory object.
	 */
	protected $entityFactory;
	
	/**
	 * @var EntityManager EntityManager object.
	 */
	protected $entityManager;
	
	
	/**
	 * @var iModel Seed entity.
	 */	
	protected $seed;
	
	/**
	 * @var string Class Name of aggregate root.
	 */	
	protected $entityClassName;
	
	/**
	 * @var string Model Name of aggregate root.
	 */	
	protected $entityName;
	
	public function __construct($entityName, EntityManager $entityManager, EntityFactory $entityFactory)
	{
		$this->entityName = $entityName;		
		$this->entityFactory = $entityFactory;		
		$this->seed = $this->entityFactory->create($entityName);		
		$this->entityClassName = get_class($this->seed);
		$this->entityManager = $entityManager;		
	}
		
	protected function getEntityFactory()
	{
		return $this->entityFactory;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	abstract public function get($id = null);	
	
	abstract public function save(Entity $entity);	
		
	abstract public function remove(Entity $entity);

	abstract public function find(array $params);
	
	abstract public function findOne(array $params);	

	abstract public function getAll();
	
	abstract public function count(array $params);
}

