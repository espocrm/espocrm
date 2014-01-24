<?php

namespace Espo\ORM;

class EntityManager
{

	protected $pdo;

	protected $entityFactory;

	protected $repositoryFactory;

	protected $mappers = array();

	protected $metadata;

	protected $repositoryHash = array();
	
	protected $params = array();

	public function __construct($params)
	{
		$this->params = $params;

		$this->metadata = new Metadata();

		if (!empty($params['metadata'])) {
			$this->setMetadata($params['metadata']);
		}

		$entityFactoryClassName = '\\Espo\\ORM\\EntityFactory';
		if (!empty($params['entityFactoryClassName'])) {
			$entityFactoryClassName = $params['entityFactoryClassName'];
		}
		$this->entityFactory = new $entityFactoryClassName($this, $this->metadata);
	

		$repositoryFactoryClassName = '\\Espo\\ORM\\RepositoryFactory';
		if (!empty($params['repositoryFactoryClassName'])) {
			$repositoryFactoryClassName = $params['repositoryFactoryClassName'];
		}
		$this->repositoryFactory = new $repositoryFactoryClassName($this, $this->entityFactory);
		
		$this->init();
	}
	
	public function getMapper($className)
	{
		if (empty($this->mappers[$className])) {
			$this->mappers[$className] = new $className($this->getPDO(), $this->entityFactory);
		}
		return $this->mappers[$className];
	}

	protected function initPDO()
	{
		$params = $this->params;
		$this->pdo = new \PDO('mysql:host='.$params['host'].';dbname=' . $params['dbname'], $params['user'], $params['password']);
	}

	public function getEntity($name, $id = null)
	{
		return $this->getRepository($name)->get($id);
	}
	
	public function saveEntity(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		return $this->getRepository($entityName)->save($entity);
	}
	
	public function removeEntity(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		return $this->getRepository($entityName)->remove($entity);
	}

	public function getRepository($name)
	{
		if (empty($this->repositoryHash[$name])) {
			$this->repositoryHash[$name] = $this->repositoryFactory->create($name);
		}
		return $this->repositoryHash[$name];
	}

	public function setMetadata(array $data)
	{
		$this->metadata->setData($data);
	}
	
	public function getMetadata()
	{
		return $this->metadata;
	}

	public function getPDO()
	{
		if (empty($this->pdo)) {
			$this->initPDO();
		}
		return $this->pdo;
	}

	public function normalizeRepositoryName($name)
	{
		return $name;
	}

	public function normalizeEntityName($name)
	{
		return $name;
	}
	
	public function createCollection($entityName, $data = array())
	{
		$seed = $this->getEntity($entityName);		
		$collection = new EntityCollection($data, $seed, $this->entityFactory);		
		return $collection;
	}
	
	protected function init()
	{
	}
}

