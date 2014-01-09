<?php

namespace Espo\ORM\DB;

use Espo\ORM\Entity;
use Espo\ORM\IEntity;
use Espo\ORM\EntityFactory;
use PDO;

/**
 * Abstraction for DB.
 * Mapping of Entity to DB.
 * Should be used internally only.
 */
abstract class Mapper implements IMapper
{
	public $pdo;
	
	protected $entityFactroy;
	
	protected $fieldsMapCache = array();
	protected $aliasesCache = array();
	
	protected $returnCollection = true;
	
	protected $collectionClass = "\\Espo\\ORM\\EntityCollection";
	
	protected static $sqlOperators = array(
		'OR',
		'AND',
	);
	
	protected static $comparisonOperators = array(			
		'!=' => '<>',
		'*' => 'LIKE',
		'>=' => '>=',
		'<=' => '<=',
		'>' => '>',
		'<' => '<',
		'=' => '=',
	);	
	
	// @todo whereClause ? 
	protected static $selectParamList = array(
		'offset',
		'limit',			
		'order',
		'orderBy',
		'customWhere',
		'customJoin',
		'joins',
		'leftJoins',
		'distinct',
	);
	
	public function __construct(PDO $pdo, \Espo\ORM\EntityFactory $entityFactory) {
		$this->pdo = $pdo;
		$this->entityFactory = $entityFactory;
	}
	
	public function selectById(IEntity $entity, $id, $params = array())
	{		
		if (!array_key_exists('whereClause', $params)) {
			$params['whereClause'] = array();
		}
		
		$params['whereClause']['id'] = $id;
		$params['whereClause']['deleted'] = 0;		
		
		$sql = $this->createSelectQuery($entity, $params);		
		
		$ps = $this->pdo->query($sql);
		
		if ($ps) {
			foreach ($ps as $row) {
				$entity = $this->fromRow($entity, $row);
				return true;
			}
		}
		return false;
	}	
	
	public function count(IEntity $entity, $params = array())
	{
		return $this->aggregate($entity, $params, 'COUNT', 'id');
	}
	
	public function max(IEntity $entity, $params = array(), $field, $deleted = false)
	{
		return $this->aggregate($entity, $params, 'MAX', $field, true);		
	}
	
	public function min(IEntity $entity, $params = array(), $field, $deleted = false)
	{
		return $this->aggregate($entity, $params, 'MIN', $field, true);
	}	
	
	public function sum(IEntity $entity, $params = array())
	{
		return $this->aggregate($entity, $params, 'SUM', 'id');
	}

	public function select(IEntity $entity, $params = array())
	{
		$sql = $this->createSelectQuery($entity, $params);

		$dataArr = array();		
		$ps = $this->pdo->query($sql);
		if ($ps) {
			$dataArr = $ps->fetchAll();
		}
		
		if ($this->returnCollection) {		
			$collectionClass = $this->collectionClass;
			$entityArr = new $collectionClass($dataArr, $entity, $this->entityFactory);			
			return $entityArr;
		} else {
			return $dataArr;
		}
	}
	
	public function aggregate(IEntity $entity, $params = array(), $aggregation, $aggregationBy, $deleted = false)
	{	
		if (empty($aggregation) || !isset($entity->fields[$aggregationBy])) {
			return false;
		}
		
		$sql = $this->createSelectQuery($entity, $params, $aggregation, $aggregationBy, $deleted);

		$ps = $this->pdo->query($sql);
		
		if ($ps) {
			foreach ($ps as $row) {				
				return $row['AggregateValue'];
			}
		}
		return false;
	}
	
	protected function createSelectQuery(IEntity $entity, $params = array(), $aggregation = null, $aggregationBy = null, $deleted = false)
	{
		$whereClause = array();
		if (array_key_exists('whereClause', $params)) {
			$whereClause = $params['whereClause'];
		}
		
		if (!$deleted) {
			$whereClause = $whereClause + array('deleted' => 0);
		}
		
		foreach (self::$selectParamList as $k) {
			$$k = array_key_exists($k, $params) ? $params[$k] : null;
		}	
	
		if (empty($aggregation)) {
			$selectPart = $this->getSelect($entity);
			$orderPart = $this->getOrder($entity, $orderBy, $order);
		} else {
			$aggDist = false;
			if ($distinct && $aggregation == 'COUNT') {
				$aggDist = true;
			}
			$selectPart = $this->getAggregationSelect($entity, $aggregation, $aggregationBy, $aggDist);
		}
		$joinsPart = $this->getBelongsToJoins($entity);
		$wherePart = $this->getWhere($entity, $whereClause);
		
		if (!empty($customWhere)) {
			$wherePart .= ' ' . $customWhere;
		}
		
		if (!empty($customJoin)) {
			$joinsPart .= ' ' . $customJoin;
		}
		
		if (!empty($joins) && is_array($joins)) {
			$joinsRelated = $this->getJoins($entity, $joins);
			if (!empty($joinsRelated)) {
				if (!empty($joinsPart)) {
					$joinsPart .= ' ';
				}
				$joinsPart .= $joinsRelated;
			}
		}
		
		if (!empty($leftJoins) && is_array($leftJoins)) {
			$joinsRelated = $this->getJoins($entity, $leftJoins, true);
			if (!empty($joinsRelated)) {
				if (!empty($joinsPart)) {
					$joinsPart .= ' ';
				}
				$joinsPart .= $joinsRelated;
			}
		}		
		
		if (empty($aggregation)) {
			return $this->composeSelectQuery($this->toDb($entity->getEntityName()), $selectPart, $joinsPart, $wherePart, $orderPart, $offset, $limit, $distinct);
		} else {
			return $this->composeSelectQuery($this->toDb($entity->getEntityName()), $selectPart, $joinsPart, $wherePart, null, null, null, $distinct);
		}
	}
	
	protected function getAggregationSelect(IEntity $entity, $aggregation, $aggregationBy, $distinct = false)
	{
		if (!isset($entity->fields[$aggregationBy])) {
			return false;
		}
		
		$aggregation = strtoupper($aggregation);
		
		$distinctPart = '';		
		if ($distinct) {
			$distinctPart = 'DISTINCT ';
		}
		
		$selectPart = "{$aggregation}({$distinctPart}" . $this->toDb($entity->getEntityName()) . "." . $this->toDb($aggregationBy) . ") AS AggregateValue";
		return $selectPart;	
	}
	
	protected function getJoins(IEntity $entity, array $joins, $left = false)
	{
		$joinsArr = array();
		foreach ($joins as $relationName) {
			if ($joinRelated = $this->getJoinRelated($entity, $relationName, $left)) {
				$joinsArr[] = $joinRelated;
			}
		}
		return implode(' ', $joinsArr);	
	}
	

	public function selectRelated(IEntity $entity, $relationName, $params = array(), $totalCount = false)
	{		
		$relOpt = $entity->relations[$relationName];		
		
		if (!isset($relOpt['entity']) || !isset($relOpt['type'])) {
			throw new \LogicException("Not appropriate defenition for relationship {$relationName} in " . $entity->getEntityName() . " entity");
		}
		
		$relEntityName = (!empty($relOpt['class'])) ? $relOpt['class'] : $relOpt['entity'];		
		$relEntity = $this->entityFactory->create($relEntityName);

		$whereClause = array();
		if (array_key_exists('whereClause', $params)) {
			$whereClause = $params['whereClause'];
		}
		
		$whereClause = $whereClause + array('deleted' => 0);
		
		foreach (self::$selectParamList as $k) {
			$$k = array_key_exists($k, $params) ? $params[$k] : null;			
			if (is_null($$k) && isset($relOpt[$k])) {
				$$k = $relOpt[$k];
			}
		}
		
		if (!$totalCount) {
			$selectPart = $this->getSelect($relEntity);
			$joinsPart = $this->getBelongsToJoins($relEntity);			
			$orderPart = $this->getOrder($relEntity, $orderBy, $order);			
		
		} else {
			$selectPart = $this->getAggregationSelect($relEntity, 'COUNT', 'id');
			$joinsPart = '';
			$orderPart = '';	
			$offset = null;
			$limit = null;
		}
		
		$relType = $relOpt['type'];
		
		$keySet = $this->getKeys($entity, $relationName);
		
		$key = $keySet['key'];
		$foreignKey = $keySet['foreignKey'];
		
		switch ($relType) {
		
			case IEntity::BELONGS_TO:

				$whereClause[$foreignKey] = $entity->get($key);
				$wherePart = $this->getWhere($relEntity, $whereClause);
		
				if (!empty($customWhere)) {
					$wherePart .= ' ' . $customWhere;
				}
			
				$sql = $this->composeSelectQuery($this->toDb($relEntity->getEntityName()), $selectPart, $joinsPart, $wherePart, $orderPart, 0, 1);
				$ps = $this->pdo->query($sql);

				if ($ps) {
					foreach ($ps as $row) {
						if (!$totalCount) {
							$relEntity = $this->fromRow($relEntity, $row);
							return $relEntity;
						} else {
							return $row['AggregateValue'];
						}
					}
				}
			break;
				
			case IEntity::HAS_MANY:
			case IEntity::HAS_CHILDREN:
				$whereClause[$foreignKey] = $entity->get($key);
				
				if ($relType == IEntity::HAS_CHILDREN) {
					$foreignType = $keySet['foreignType'];
					$whereClause[$foreignType] = $entity->getEntityName();
				}
								
				$wherePart = $this->getWhere($relEntity, $whereClause);
			
				if (!empty($customWhere)) {
					$wherePart .= ' ' . $customWhere;
				}
				$dataArr = array();
				
				$sql = $this->composeSelectQuery($this->toDb($relEntity->getEntityName()), $selectPart, $joinsPart, $wherePart, $orderPart, $offset, $limit);
			
				$ps = $this->pdo->query($sql);		
				if ($ps) {					
					if (!$totalCount) {
						$dataArr = $ps->fetchAll();
					
					} else {
						foreach ($ps as $row) {
							return $row['AggregateValue'];
						}
					}					
				}
				if ($this->returnCollection) {
					$collectionClass = $this->collectionClass;
					return new $collectionClass($dataArr, $relEntity, $this->entityFactory);
				} else {
					return $dataArr;
				}
			break;		
				
			case IEntity::MANY_MANY:
				
				$MMJoinPart = $this->getMMJoin($entity, $relationName, $keySet);
				$wherePart = $this->getWhere($relEntity, $whereClause);				
				if ($joinsPart != '') {
					$MMJoinPart = ' ' . $MMJoinPart;
				}

				$dataArr = array();
			
				$sql = $this->composeSelectQuery($this->toDb($relEntity->getEntityName()), $selectPart, $joinsPart . $MMJoinPart, $wherePart, $orderPart, $offset, $limit);

				$ps = $this->pdo->query($sql);		
				if ($ps) {					
					if (!$totalCount) {
						$dataArr = $ps->fetchAll();
					
					} else {
						foreach ($ps as $row) {
							return $row['AggregateValue'];
						}
					}					
				}
				if ($this->returnCollection) {
					$collectionClass = $this->collectionClass;
					return new $collectionClass($dataArr, $relEntity, $this->entityFactory);
				} else {
					return $dataArr;
				}
			break;
		}
		
		return false;
	}
	
	protected function getKeys(IEntity $entity, $relationName)
	{
		$relOpt = $entity->relations[$relationName];
		$relType = $relOpt['type'];
		
		switch ($relType) {
		
			case IEntity::BELONGS_TO:
				$key = $this->toDb($entity->getEntityName()) . 'Id';
				if (isset($relOpt['key'])) {
					$key = $relOpt['key'];
				}
				$foreignKey = 'id';
				if(isset($relOpt['foreignKey'])){
					$foreignKey = $relOpt['foreignKey'];
				}
				return array(
					'key' => $key,
					'foreignKey' => $foreignKey,
				);
				
			case IEntity::HAS_MANY:			
				$key = 'id';
				if (isset($relOpt['key'])){
					$key = $relOpt['key'];
				}
				$foreignKey = $this->toDb($entity->getEntityName()) . 'Id';
				if (isset($relOpt['foreignKey'])) {
					$foreignKey = $relOpt['foreignKey'];
				}	
				return array(
					'key' => $key,
					'foreignKey' => $foreignKey,
				);
			case IEntity::HAS_CHILDREN:
				$key = 'id';
				if (isset($relOpt['key'])){
					$key = $relOpt['key'];
				}
				$foreignKey = 'parentId';
				if (isset($relOpt['foreignKey'])) {
					$foreignKey = $relOpt['foreignKey'];
				}
				$foreignType = 'parentType';
				if (isset($relOpt['foreignType'])) {
					$foreignType = $relOpt['foreignType'];
				}	
				return array(
					'key' => $key,
					'foreignKey' => $foreignKey,
					'foreignType' => $foreignType,
				);
				
			case IEntity::MANY_MANY:
				$key = 'id';
				if(isset($relOpt['key'])){
					$key = $relOpt['key'];
				}
				$foreignKey = 'id';
				if(isset($relOpt['foreignKey'])){
					$foreignKey = $relOpt['foreignKey'];
				}
				$nearKey = $this->toDb($entity->getEntityName()) . 'Id';
				$distantKey = $this->toDb($relOpt['entity']) . 'Id';				
				if (isset($relOpt['midKeys']) && is_array($relOpt['midKeys'])){
					$nearKey = $relOpt['midKeys'][0];
					$distantKey = $relOpt['midKeys'][1];
				}
				return array(
					'key' => $key,
					'foreignKey' => $foreignKey,
					'nearKey' => $nearKey,
					'distantKey' => $distantKey,
				);			
		}			
	}

	public function countRelated(IEntity $entity, $relationName, $params = array())
	{
		return $this->selectRelated($entity, $relationName, $params, true);
	}
	
	public function relate(IEntity $entityFrom, $relationName, IEntity $entityTo)
	{
		$this->addRelation($entityFrom, $relationName, null, $entityTo);
	}
	
	public function unrelate(IEntity $entityFrom, $relationName, IEntity $entityTo)
	{
		$this->removeRelation($entityFrom, $relationName, null, false, $entityTo);
	}
	
	public function addRelation(IEntity $entity, $relationName, $id = null, $relEntity = null)
	{
		if (!is_null($relEntity)) {
			$id = $relEntity->id;
		}
	
		if (empty($id) || empty($relationName)) {
			return false;
		}
	
		$relOpt = $entity->relations[$relationName];
		
		if (!isset($relOpt['entity']) || !isset($relOpt['type'])) {
			throw new \LogicException("Not appropriate defenition for relationship {$relationName} in " . $entity->getEntityName() . " entity");
		}
		
		$relType = $relOpt['type'];
		
		$className = (!empty($relOpt['class'])) ? $relOpt['class'] : $relOpt['entity'];
		
		if (is_null($relEntity)) {
			$relEntity = $this->entityFactory->create($className);		
			$relEntity->id = $id;
		}
		
		$keySet = $this->getKeys($entity, $relationName);
		
		switch ($relType) {
			case IEntity::BELONGS_TO:
			case IEntity::HAS_ONE:
				return false;
			break;
			
			case IEntity::HAS_CHILDREN:
			case IEntity::HAS_MANY:				
				$key = $keySet['key'];
				$foreignKey = $keySet['foreignKey'];
				
				if ($this->count($relEntity, array('whereClause' => array('id' => $id))) > 0) {
				
					$setPart = $this->toDb($foreignKey) . " = " . $this->pdo->quote($entity->get($key));
					
					if ($relType == IEntity::HAS_CHILDREN) {
						$foreignType = $keySet['foreignType'];
						$setPart .= ", " . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityName());
					}
					
					$wherePart = $this->getWhere($relEntity, array('id' => $id, 'deleted' => 0));		
					$sql = $this->composeUpdateQuery($this->toDb($relEntity->getEntityName()), $setPart, $wherePart);
					
					if ($this->pdo->query($sql)) {
						return true;
					}
				} else {
					return false;
				}				
			break;
				
			case IEntity::MANY_MANY:
				$key = $keySet['key'];
				$foreignKey = $keySet['foreignKey'];
				$nearKey = $keySet['nearKey'];
				$distantKey = $keySet['distantKey'];
				
				if ($this->count($relEntity, array('whereClause' => array('id' => $id))) > 0) {			
					$relTable = $this->toDb($relOpt['relationName']);
					
					$wherePart = 
						$this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . " ".
						"AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($relEntity->id);						
					if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
						foreach ($relOpt['conditions'] as $f => $v) {
							$wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
						}					
					}
					
					$sql = $this->composeSelectQuery($relTable, '*', '', $wherePart);

					$ps = $this->pdo->query($sql);	
					
					if ($ps->rowCount() == 0) {						
						$fieldsPart = $this->toDb($nearKey) . ", " . $this->toDb($distantKey);
						$valuesPart = $this->pdo->quote($entity->id) . ", " . $this->pdo->quote($relEntity->id);
						
						if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
							foreach ($relOpt['conditions'] as $f => $v) {
								$fieldsPart .= ", " . $this->toDb($f);
								$valuesPart .= ", " . $this->pdo->quote($v);
							}					
						}
						
						$sql = $this->composeInsertQuery($relTable, $fieldsPart, $valuesPart);
						
						if ($this->pdo->query($sql)) {
							return true;
						}					
					} else {
						$setPart = 'deleted = 0';
						$wherePart =
							$this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . "
							AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($relEntity->id) . "
							";
							
						if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
							foreach ($relOpt['conditions'] as $f => $v) {
								$wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
							}					
						}
							
						$sql = $this->composeUpdateQuery($relTable, $setPart, $wherePart);
						if ($this->pdo->query($sql)) {
							return true;
						}
					}
				} else {
					return false;
				}				
			break;			
		}
	}	
		
	public function removeRelation(IEntity $entity, $relationName, $id = null, $all = false, IEntity $relEntity = null)
	{	
		if (!is_null($relEntity)) {
			$id = $relEntity->id;
		}
	
		if (empty($id) && empty($all) || empty($relationName)) {
			return false;
		}
	
		$relOpt = $entity->relations[$relationName];
		
		if (!isset($relOpt['entity']) || !isset($relOpt['type'])) {
			throw new \LogicException("Not appropriate defenition for relationship {$relationName} in " . $entity->getEntityName() . " entity");
		}
		
		$relType = $relOpt['type'];
		
		$className = (!empty($relOpt['class'])) ? $relOpt['class'] : $relOpt['entity'];
		
		if (is_null($relEntity)) {
			$relEntity = $this->entityFactory->create($className);
			$relEntity->id = $id;
		}
		
		$keySet = $this->getKeys($entity, $relationName);
		
		switch ($relType) {
		
			case IEntity::BELONGS_TO:
				/*$foreignKey = $keySet['foreignKey'];
				$relEntity->$foreignKey = null;
				$this->
				break;*/
			
			case IEntity::HAS_ONE:				
				return false;
			
			
			case IEntity::HAS_MANY:
			case IEntity::HAS_CHILDREN:			
				$key = $keySet['key'];
				$foreignKey = $keySet['foreignKey'];

				$setPart = $this->toDb($foreignKey) . " = " . "NULL";
				
				$whereClause = array('deleted' => 0);
				if (empty($all)) {
					$whereClause['id'] = $id;
				} else {
					$whereClause[$foreignKey] = $entity->id;
				}
				
				if ($relType == IEntity::HAS_CHILDREN) {
					$foreignType = $keySet['foreignType'];
					$whereClause[$foreignType] = $entity->getEntityName();
				}
				
				$wherePart = $this->getWhere($relEntity, $whereClause);		
				$sql = $this->composeUpdateQuery($this->toDb($relEntity->getEntityName()), $setPart, $wherePart);
				if ($this->pdo->query($sql)) {
					return true;
				}
				break;
				
			case IEntity::MANY_MANY:
				$key = $keySet['key'];
				$foreignKey = $keySet['foreignKey'];
				$nearKey = $keySet['nearKey'];
				$distantKey = $keySet['distantKey'];

				$relTable = $this->toDb($relOpt['relationName']);
				
				$setPart = 'deleted = 1';
				$wherePart = $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id);				

					
				if (empty($all)) {
					$wherePart .= " AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($id) . "";
				}
				
				if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
					foreach ($relOpt['conditions'] as $f => $v) {
						$wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
					}					
				}
				
				$sql = $this->composeUpdateQuery($relTable, $setPart, $wherePart);
				
				if ($this->pdo->query($sql)) {
					return true;
				}
				break;			
		}
	}
	
	public function removeAllRelations(IEntity $entity, $relationName)
	{
		$this->removeRelation($entity, $relationName, null, true);
	}

	public function insert(IEntity $entity)
	{	
		$dataArr = $this->toArray($entity);
		
	
		$fieldArr = array();
		$valArr = array();
		foreach ($dataArr as $field => $value) {
			$fieldArr[] = $this->toDb($field);			
			$valArr[] = $this->pdo->quote($value);			
		}		
		$fieldsPart = implode(", ", $fieldArr);
		$valuesPart = implode(", ", $valArr);
		
		$sql = $this->composeInsertQuery($this->toDb($entity->getEntityName()), $fieldsPart, $valuesPart);
		
		if ($this->pdo->query($sql)) {			
			return $entity->id;
		}
		
		return false;
	}
	
	public function update(IEntity $entity)
	{		
		$dataArr = $this->toArray($entity);
				
		$setArr = array();
		foreach ($dataArr as $field => $value) {
			if ($field == 'id') {
				continue;
			}						
			if ($entity->fields[$field]['type'] == IEntity::FOREIGN) {
				continue;
			}
			
			if ($entity->getFetchedValue($field) === $value) {
				continue;
			}
			
			$setArr[] = $this->toDb($field) . " = " . $this->pdo->quote($value);
		}
		
		if (count($setArr) == 0) {
			return false;
		}
		
		$setPart = implode(', ', $setArr);		
		$wherePart = $this->getWhere($entity, array('id' => $entity->id, 'deleted' => 0));
		
		$sql = $this->composeUpdateQuery($this->toDb($entity->getEntityName()), $setPart, $wherePart);

		if ($this->pdo->query($sql)) {
			return $entity->id;
		}
		
		return false;
	}
	
	public function delete(IEntity $entity)
	{
		$entity->set('deleted', true);
		return $this->update($entity);
	}
	
	protected function toArray(IEntity $entity, $onlyStorable = true)
	{	
		$arr = array();
		foreach ($entity->fields as $field => $fieldDefs) {
			if ($entity->has($field)) {		
				if ($onlyStorable) {
					if (!empty($fieldDefs['notStorable']) || isset($fieldDefs['source']) && $fieldDefs['source'] != 'db')
						continue;				
					if ($fieldDefs['type'] == IEntity::FOREIGN)
						continue;
				}				
				$arr[$field] = $entity->get($field);
			}
		}
		return $arr;
	}	
		
	protected function fromRow(IEntity $entity, $data)
	{	
		$entity->set($data);
		return $entity;
	}
	
	protected function getAlias(IEntity $entity, $key)
	{	
		if (!isset($this->aliasesCache[$entity->getEntityName()])) {
			$this->aliasesCache[$entity->getEntityName()] = $this->getTableAliases($entity);
		}
		
		if (isset($this->aliasesCache[$entity->getEntityName()][$key])) {
			return $this->aliasesCache[$entity->getEntityName()][$key];
		} else {
			return false;
		}		
	}
	
	protected function getTableAliases(IEntity $entity)
	{	
		$aliases = array();
		$c = 0;		
		foreach ($entity->relations as $r) {
			if ($r['type'] == IEntity::BELONGS_TO) {
				$key = $r['key'];
				if (!array_key_exists($key, $aliases)) {
					$c++;
					$suffix = '_' . $c;					
					$aliases[$key] = $this->toDb($r['entity']) . $suffix;
					
				}			
			}
		}
		return $aliases;
	}
	
	protected function getFieldPath(IEntity $entity, $field)
	{	
		if (isset($entity->fields[$field])) {
			$f = $entity->fields[$field];
			
			if (isset($f['source'])) {
				if ($f['source'] != 'db') {
					return false;
				}
			}
					
			if (!empty($f['notStorable'])) {
				return false;
			}			
			
			$fieldPath = '';

			switch($f['type']) {				
				case 'foreign':
					if (isset($f['relation'])) {
						$relationName = $f['relation'];
						
						$keySet = $this->getKeys($entity, $relationName);
						$key = $keySet['key'];
						
						$foreigh = $f['foreign'];
						
						if (is_array($foreigh)) {
							foreach ($foreigh as $i => $value) {
								if ($value == ' ') {
									$foreigh[$i] = '\' \'';
								} else {
									$foreigh[$i] = $this->getAlias($entity, $key) . '.' . $this->toDb($value);
								}
							}
							$fieldPath = 'TRIM(CONCAT(' . implode(', ', $foreigh). '))';
						} else {						
							$fieldPath = $this->getAlias($entity, $key) . '.' . $this->toDb($foreigh);
						}
					}
					break;
				default:
					$fieldPath = $this->toDb($entity->getEntityName()) . '.' . $this->toDb($field) ;
			}
				
			return $fieldPath;			
		}
		
		return false;
	}
	
	protected function getWhere(IEntity $entity, $whereClause, $sqlOp = 'AND')
	{
		$whereParts = array();
		
		foreach ($whereClause as $field => $value) {
		
			if (is_int($field)) {
				$field = 'AND';
			}
		
			if (!in_array($field, self::$sqlOperators)) {							
				
				$inRelated = false;

				if (strpos($field, '.') !== false) {
					list($entityName, $field) = array_map('trim', explode('.', $field));					
					$inRelated = true;
				}
		
				$operator = '=';

				if (!preg_match('/^[a-z0-9]+$/i', $field)) {		
					foreach (self::$comparisonOperators as $op => $opDb) {				
						if (strpos($field, $op) !== false) {
							$field = trim(str_replace($op, '', $field));						
							$operator = $opDb;
							break;
						}
					}
				}				
			
				if (!$inRelated) {				
					
					if (!isset($entity->fields[$field])) {
						continue;
					}					
					
					$fieldDefs = $entity->fields[$field];
					
					if (!empty($fieldDefs['where']) && !empty($fieldDefs['where'][$operator])) {
						$whereParts[] = str_replace('{text}', $value, $fieldDefs['where'][$operator]);
					} else {					
						if ($fieldDefs['type'] == IEntity::FOREIGN) {
							$leftPart = '';
							if (isset($fieldDefs['relation'])) {
								$relationName = $fieldDefs['relation'];
								if (isset($entity->relations[$relationName])) {
									$keySet = $this->getKeys($entity, $relationName);
									$key = $keySet['key'];
								
									$alias = $this->getAlias($entity, $key);
									if ($alias) {
										$leftPart = $alias . '.' . $this->toDb($fieldDefs['foreign']);
									}
								}
							}
						} else {
							$leftPart = $this->toDb($entity->getEntityName()) . '.' . $this->toDb($field);
						}
					}
				} else {
					$leftPart = $this->toDb($entityName) . '.' . $this->toDb($field);
				}
				
				if (!empty($leftPart)) {
					if (!is_array($value)) {
						$whereParts[] = $leftPart . " " . $operator . " " . $this->pdo->quote($value);
					
					} else {
						$valArr = $value;
						foreach ($valArr as $k => $v) {
							$valArr[$k] = $this->pdo->quote($valArr[$k]);
						}
						$oppose = '';
						if ($operator == '<>') {
							$oppose = 'NOT';
						}
						$whereParts[] = $leftPart . " {$oppose} IN " . "(" . implode(',', $valArr) . ")";
					}				
				}				
			} else {
				$whereParts[] = "(" . $this->getWhere($entity, $value, $field) . ")";
			}
		}	
		return implode(" " . $sqlOp . " ", $whereParts);
	}
	
	protected function getBelongsToJoins(IEntity $entity)
	{		
		$joinsArr = array();
		
		foreach ($entity->relations as $relationName => $r) {						
			if ($r['type'] == IEntity::BELONGS_TO) {
				$keySet = $this->getKeys($entity, $relationName);
				$key = $keySet['key'];
				$foreignKey = $keySet['foreignKey'];
				
				$alias = $this->getAlias($entity, $key);
				
				if ($alias) {
					$joinsArr[] = 
						"LEFT JOIN `" . $this->toDb($r['entity']) . "` AS " . $alias . " ON ". 
						$this->toDb($entity->getEntityName()) . "." . $this->toDb($key) . " = " . $alias . "." . $this->toDb($foreignKey);
				}
			}
		}
		
		return implode(' ', $joinsArr);
	}
	
	protected function getJoinRelated(IEntity $entity, $relationName, $left = false)
	{
		$relOpt = $entity->relations[$relationName];
		$keySet = $this->getKeys($entity, $relationName);
		
		$pre = ($left) ? 'LEFT ' : '';
		
		if ($relOpt['type'] == IEntity::MANY_MANY) {
			
			$key = $keySet['key'];
			$foreignKey = $keySet['foreignKey'];
			$nearKey = $keySet['nearKey'];
			$distantKey = $keySet['distantKey'];				

			$relTable = $this->toDb($relOpt['relationName']);
			$distantTable = $this->toDb($relOpt['entity']);			
			
			
			$join =
				"{$pre}JOIN `{$relTable}` ON {$this->toDb($entity->getEntityName())}." . $this->toDb($key) . " = {$relTable}." . $this->toDb($nearKey)
				. " AND "
				. "{$relTable}.deleted = " . $this->pdo->quote(0);
				
			if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
				foreach ($relOpt['conditions'] as $f => $v) {
					$join .= " AND {$relTable}." . $this->toDb($f) . " = " . $this->pdo->quote($v);
				}			
			}
			
			$join .= " {$pre}JOIN `{$distantTable}` ON {$distantTable}." . $this->toDb($foreignKey) . " = {$relTable}." . $this->toDb($distantKey)
				. " AND "
				. "{$distantTable}.deleted = " . $this->pdo->quote(0) . "";

			return $join;	
		}
		
		if ($relOpt['type'] == IEntity::HAS_MANY) {
		
			$foreignKey = $keySet['foreignKey'];
			$distantTable = $this->toDb($relOpt['entity']);			
			
			$join = 
				"{$pre}JOIN `{$distantTable}` ON {$this->toDb($entity->getEntityName())}." . $this->toDb('id') . " = {$distantTable}." . $this->toDb($foreignKey)
				. " AND "
				. "{$distantTable}.deleted = " . $this->pdo->quote(0) . "";
				
			return $join;
		}

		return false;	
	}
	
	protected function getMMJoin(IEntity $entity, $relationName, $keySet = false)
	{
		$relOpt = $entity->relations[$relationName];
		
		if (empty($keySet)) {
			$keySet = $this->getKeys($entity, $relationName);
		}
	
		$key = $keySet['key'];
		$foreignKey = $keySet['foreignKey'];
		$nearKey = $keySet['nearKey'];
		$distantKey = $keySet['distantKey'];				

		$relTable = $this->toDb($relOpt['relationName']);
		$distantTable = $this->toDb($relOpt['entity']);

		$join =
			"JOIN `{$relTable}` ON {$distantTable}." . $this->toDb($foreignKey) . " = {$relTable}." . $this->toDb($distantKey)
			. " AND "
			. "{$relTable}." . $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->get($key))
			. " AND "
			. "{$relTable}.deleted = " . $this->pdo->quote(0) . "";
			
		if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
			foreach ($relOpt['conditions'] as $f => $v) {
				$join .= " AND {$relTable}." . $this->toDb($f) . " = " . $this->pdo->quote($v);
			}	
		}
		
		return $join;				
	}
	
	protected function getSelect(IEntity $entity, $fields = null)
	{	
		$select = "";
		$arr = array();
		$specifiedList = is_array($fields) ? true : false;
		
		foreach ($entity->fields as $field => $fieldDefs) {
			if ($specifiedList) {
				if (!in_array($field, $fields)) {
					continue;
				}
			}
		
			if (!empty($fieldDefs['select'])) {
				$fieldPath = $fieldDefs['select'];
			} else {			
				if (!empty($fieldDefs['notStorable'])) {
					continue;
				}
				$fieldPath = $this->getFieldPath($entity, $field);
			}

			$arr[] = $fieldPath . ' AS ' . $field;
		}
		
		$select = implode(', ', $arr);
		
		return $select;
	}
	
	protected function getOrder(IEntity $entity, $orderBy = null, $order = null)
	{		
		$orderStr = "";
		
		if (!is_null($orderBy)) {		
			if (!is_null($order)) {
				$order = strtoupper($order);
				if (!in_array($order, array('ASC', 'DESC'))) {
					$order = 'ASC';
				}
			} else {
				$order = 'ASC';
			}
		
			$fieldDefs = $entity->fields[$orderBy];
			if (!empty($fieldDefs['orderBy'])) {
				$orderPart = str_replace('{direction}', $order, $fieldDefs['orderBy']);
				$orderStr .= "ORDER BY {$orderPart}";				
			} else {			
				$fieldPath = $this->getFieldPath($entity, $orderBy);
				$orderStr .= "ORDER BY {$fieldPath} " . $order;
			}
		}
		
		return $orderStr;
	}
	
	protected function composeInsertQuery($table, $fields, $values)
	{	
		$sql = "INSERT INTO `{$table}`";
		$sql .= " ({$fields})";
		if (!is_array($values)) {
			$sql .= " VALUES ({$values})";
		} else {
			$sql .= " VALUES (" . implode("), (", $values) . ")";
		}
		
		return $sql;
	}
	
	protected function composeUpdateQuery($table, $set, $where)
	{
		$sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";
		
		return $sql;
	}
	
	abstract protected function composeSelectQuery($table, $select, $joins = '', $where = '', $order = '', $offset = null, $limit = null, $distinct = null);
	
	abstract protected function toDb($field);
	
	public function setReturnCollection($returnCollection)
	{
		$this->returnCollection = $returnCollection;
	}
	
	public function setCollectionClass($collectionClass)
	{
		$this->collectionClass = $collectionClass;
	}
}


