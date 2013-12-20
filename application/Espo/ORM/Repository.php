<?php

namespace Espo\ORM;


class Repository
{
	
	/**
	 * @var EntityFactory EntityFactory object.
	 */
	private $entityFactory;
	
	/**
	 * @var EntityManager EntityManager object.
	 */
	protected $entityManager;
	
	/**
	 * @var \MyApp\DB\iMapper DB Mapper.
	 */
	private $mapper;	
	
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
	
	public function reset()
	{
		$this->whereClause = array();
		$this->listParams = array();
	}
	
	public function get($id = null)
	{
		$entity = $this->entityFactory->create($this->entityName);		
		if (empty($id)) {
			$entity->setIsNew(true);
			return $entity;	
		}					
		if ($this->mapper->selectById($entity, $id)) {
			$entity->setFresh();
			return $entity;
		}		
		return null;
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
		$params = $this->getSelectParams($params);		

		$dataArr = $this->mapper->select($this->seed, $params);				
		$collection = new EntityCollection($dataArr, $this->seed, $this->entityFactory);
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
		$dataArr = $this->mapper->selectRelated($entity, $relationName, $params);
		
		$entityName = $entity->relations[$relationName]['entity'];		
		$seed = $this->entityFactory->create($entityName);
		
		$collection = new EntityCollection($dataArr, $seed, $this->entityFactory);	
		return $collection;
	}	
		
	public function countRelated(Entity $entity, $relationName, array $params = array())
	{
		return $this->mapper->countRelated($entity, $relationName, $params);				
	}
	
	public function relate(Entity $entity, $relationName, $foreignEntity)
	{
		return $this->mapper->relate($entity, $relationName, $foreignEntity);
	}
	
	public function unrelate(Entity $entity, $relationName, $foreignEntity)
	{
		return $this->mapper->unrelate($entity, $relationName, $foreignEntity);
	}
	
	public function getAll()
	{
		$this->reset();		
		return $this->find();
	}
	
	public function count(array $params = array())
	{	
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

	// @todo use abstract class for list params
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
	
	public function commit()
	{		
		$this->unitOfWork->commit();
		
		foreach ($this->getLastInserted() as $entity) {
			$this->identityMap->add($entity);
		}
	}
}


