<?php

namespace Espo\ORM;


class Repository
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
	 * @var \MyApp\DB\iMapper DB Mapper.
	 */
	protected $mapper;	
	
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
	
	/**
	 * @var array Where clause array. To be used in further find operation.
	 */	
	protected $whereClause = array();	

	/**
	 * @var array Parameters to be used in further find operations.
	 */	
	protected $listParams = array();
	
	public function __construct($entityName, EntityManager $entityManager, EntityFactory $entityFactory, DB\iMapper $mapper)
	{
		$this->entityName = $entityName;		
		$this->entityFactory = $entityFactory;		
		$this->seed = $this->entityFactory->create($entityName);		
		$this->entityClassName = get_class($this->seed);
		$this->entityManager = $entityManager;
		
		$this->mapper = $mapper;
	}	
	
	protected function handleSelectParams(&$params)
	{

	}
	
	protected function getEntityFactory()
	{
		return $this->entityFactory;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	public function reset()
	{
		$this->whereClause = array();
		$this->listParams = array();
	}
	
	protected function getNewEntity()
	{
		$entity = $this->entityFactory->create($this->entityName);
		$entity->setIsNew(true);
		return $entity;	
	}
	
	protected function getEntityById($id)
	{
		$params = array();
		$this->handleSelectParams($params);
		
		$entity = $this->entityFactory->create($this->entityName);
		if ($this->mapper->selectById($entity, $id, $params)) {
			$entity->setFresh();
			return $entity;
		}
		return null;
	}
	
	public function get($id = null)
	{				
		if (empty($id)) {
			return $this->getNewEntity();
		}					
		return $this->getEntityById($id);
	}
	
	public function save(Entity $entity)
	{	
		if ($entity->isNew()) {
			$result = $this->mapper->insert($entity);
			if ($result) {
				$entity->setIsNew(false);
			}
			return $result;
		} else {
			return $this->mapper->update($entity);
		}
	}
		
	public function remove(Entity $entity)
	{	
		return $this->mapper->delete($entity);
	}

	public function find(array $params = array())
	{	
		$this->handleSelectParams($params);
		$params = $this->getSelectParams($params);		

		$dataArr = $this->mapper->select($this->seed, $params);
			
		$collection = new EntityCollection($dataArr, $this->entityName, $this->entityFactory);
		$this->reset();
		
		return $collection;
	}
	
	public function findOne(array $params = array())
	{	
		$collection = $this->find($params);		
		if (count($collection)) {
			return $collection[0];
		}
		return null;
	}
	
	public function findRelated(Entity $entity, $relationName, array $params = array())
	{
		$entityName = $entity->relations[$relationName]['entity'];
		$this->handleSelectParams($params, $entityName);		
		
		$dataArr = $this->mapper->selectRelated($entity, $relationName, $params);
		
		$collection = new EntityCollection($dataArr, $entityName, $this->entityFactory);	
		return $collection;
	}	
		
	public function countRelated(Entity $entity, $relationName, array $params = array())
	{		
		$entityName = $entity->relations[$relationName]['entity'];
		$this->handleSelectParams($params, $entityName);
		
		$this->mapper->countRelated($entity, $relationName, $params);
	}
	
	public function relate(Entity $entity, $relationName, $foreign)
	{
		if ($foreign instanceof Entity) {
			return $this->mapper->relate($entity, $relationName, $foreign);
		}
		if (is_string($foreign)) {
			return $this->mapper->addRelation($entity, $relationName, $foreign);
		}
		return false;
	}
	
	public function unrelate(Entity $entity, $relationName, $foreign)
	{
		if ($foreign instanceof Entity) {
			return $this->mapper->unrelate($entity, $relationName, $foreign);
		}
		if (is_string($foreign)) {
			return $this->mapper->removeRelation($entity, $relationName, $foreign);
		}
		if ($foreign === true) {
			return $this->mapper->removeAllRelations($entity, $relationName);
		}
		return false;
	}
	
	public function getAll()
	{
		$this->reset();		
		return $this->find();
	}
	
	public function count(array $params = array())
	{	
		$this->handleSelectParams($params);
		
		$params = $this->getSelectParams($params);		
		return $this->mapper->count($this->seed, $params);
	}
	
	public function max($field)
	{	
		$params = $this->getSelectParams();		
		return $this->mapper->max($this->seed, $params, $field);
	}
	
	public function min($field)
	{	
		$params = $this->getSelectParams();		
		return $this->mapper->min($this->seed, $params, $field);
	}
	
	public function sum($field)
	{	
		$params = $this->getSelectParams();		
		return $this->mapper->sum($this->seed, $params, $field);
	}

	// @TODO use abstract class for list params
	// @TODO join conditions
	public function join()
	{	
		$args = func_get_args();
		
		if (empty($this->listParams['joins'])) {
			$this->listParams['joins'] = array();
		}
		
		foreach ($args as &$param) {
			if (is_array($param)) {
				foreach ($param as $k => $v) {
					$this->listParams['joins'][] = array();				
				}			
			} else {
				$this->listParams['joins'][] = $param;
			}
		}
		
		return $this;
	}
	
	public function distinct()
	{
		$this->listParams['distinct'] = true;
		return $this;
	}
	
	public function where($param1 = array(), $param2 = null)
	{
		if (is_array($param1)) {
			$this->whereClause = $param1 + $this->whereClause;			
		
		} else {
			if (!is_null($param2)) {
				$this->whereClause[$param1] = $param2;
			}
		}
		
		return $this;
	}
	
	public function order($field = 'id', $direction = "ASC")
	{
		$this->listParams['orderBy'] = $field;
		$this->listParams['order'] = $direction;
		
		return $this;
	}
	
	public function limit($offset, $limit)
	{
		$this->listParams['offset'] = $offset;
		$this->listParams['limit'] = $limit;
		
		return $this;
	}
	
	public function setListParams(array $params = array())
	{
		$this->listParams = $params;
	}
	
	public function getListParams()
	{
		return $this->listParams;
	}
	
	protected function getSelectParams(array $params = array())
	{
		if (isset($params['whereClause'])) {
			$params['whereClause'] = $params['whereClause'] + $this->whereClause;		
		} else {
			$params['whereClause'] = $this->whereClause;
		}		
		$params = $params + $this->listParams;
		
		return $params;
	}	
	
	protected function getPDO()
	{
		return $this->getEntityManager()->getPDO();	
	}
}

