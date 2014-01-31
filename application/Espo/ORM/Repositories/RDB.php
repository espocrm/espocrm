<?php

namespace Espo\ORM\Repositories;

use \Espo\ORM\EntityManager;
use \Espo\ORM\EntityFactory;
use \Espo\ORM\EntityCollection;
use \Espo\ORM\Entity;
use \Espo\ORM\IEntity;


class RDB extends \Espo\ORM\Repository
{
	
	public static $mapperClassName = '\\Espo\\ORM\\DB\\MysqlMapper';
	
	/**
	 * @var Object Mapper.
	 */
	protected $mapper;	
	
	/**
	 * @var array Where clause array. To be used in further find operation.
	 */	
	protected $whereClause = array();

	/**
	 * @var array Parameters to be used in further find operations.
	 */	
	protected $listParams = array();	
	
	public function __construct($entityName, EntityManager $entityManager, EntityFactory $entityFactory)
	{
		$this->entityName = $entityName;		
		$this->entityFactory = $entityFactory;		
		$this->seed = $this->entityFactory->create($entityName);		
		$this->entityClassName = get_class($this->seed);
		$this->entityManager = $entityManager;		
	}
	
	protected function getMapper()
	{
		if (empty($this->mapper)) {
			$this->mapper = $this->getEntityManager()->getMapper(self::$mapperClassName);
		}	
		return $this->mapper;
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
		if ($this->getMapper()->selectById($entity, $id, $params)) {
			$entity->setAsFetched();
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
	
	protected function beforeSave(Entity $entity)
	{		
	}
	
	protected function afterSave(Entity $entity)
	{		
	}
	
	public function save(Entity $entity)
	{	
		$this->beforeSave($entity);
		if ($entity->isNew()) {
			$result = $this->getMapper()->insert($entity);
			if ($result) {
				$entity->setIsNew(false);
			}
		} else {
			$result = $this->getMapper()->update($entity);
		}
		if ($result) {	
			$this->afterSave($entity);
		}
		return $result;
	}
	
	protected function beforeRemove(Entity $entity)
	{		
	}
	
	protected function afterRemove(Entity $entity)
	{		
	}	
		
	public function remove(Entity $entity)
	{	
		$this->beforeRemove($entity);
		$result = $this->getMapper()->delete($entity);
		if ($result) {
			$this->afterRemove($entity);
		}
		return $result;
	}

	public function find(array $params = array())
	{	
		$this->handleSelectParams($params);
		$params = $this->getSelectParams($params);		

		$dataArr = $this->getMapper()->select($this->seed, $params);
			
		$collection = new EntityCollection($dataArr, $this->entityName, $this->entityFactory);
		$this->reset();
		
		return $collection;
	}
	
	public function findOne(array $params = array())
	{	
		$collection = $this->limit(0, 1)->find($params);		
		if (count($collection)) {
			return $collection[0];
		}
		return null;
	}
	
	public function findRelated(Entity $entity, $relationName, array $params = array())
	{
		$entityName = $entity->relations[$relationName]['entity'];
		$this->handleSelectParams($params, $entityName);		
		
		$dataArr = $this->getMapper()->selectRelated($entity, $relationName, $params);
		
		$collection = new EntityCollection($dataArr, $entityName, $this->entityFactory);	
		return $collection;
	}	
		
	public function countRelated(Entity $entity, $relationName, array $params = array())
	{		
		$entityName = $entity->relations[$relationName]['entity'];
		$this->handleSelectParams($params, $entityName);
		
		return $this->getMapper()->countRelated($entity, $relationName, $params);
	}
	
	public function relate(Entity $entity, $relationName, $foreign)
	{
		if ($foreign instanceof Entity) {
			return $this->getMapper()->relate($entity, $relationName, $foreign);
		}
		if (is_string($foreign)) {
			return $this->getMapper()->addRelation($entity, $relationName, $foreign);
		}
		return false;
	}
	
	public function unrelate(Entity $entity, $relationName, $foreign)
	{
		if ($foreign instanceof Entity) {
			return $this->getMapper()->unrelate($entity, $relationName, $foreign);
		}
		if (is_string($foreign)) {
			return $this->getMapper()->removeRelation($entity, $relationName, $foreign);
		}
		if ($foreign === true) {
			return $this->getMapper()->removeAllRelations($entity, $relationName);
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
		return $this->getMapper()->count($this->seed, $params);
	}
	
	public function max($field)
	{	
		$params = $this->getSelectParams();		
		return $this->getMapper()->max($this->seed, $params, $field);
	}
	
	public function min($field)
	{	
		$params = $this->getSelectParams();		
		return $this->getMapper()->min($this->seed, $params, $field);
	}
	
	public function sum($field)
	{	
		$params = $this->getSelectParams();		
		return $this->getMapper()->sum($this->seed, $params, $field);
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
					$this->listParams['joins'][] = $v;				
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

