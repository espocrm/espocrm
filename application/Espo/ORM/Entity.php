<?php

namespace Espo\ORM;

abstract class Entity implements IEntity
{
	public $id = null;
	
	private $isNew = false;

	/**
	 * Entity name.
	 * @var string
	 */
	protected $entityName;
	
	/**
	 * @var array Defenition of fields.
	 * @todo make protected
	 */	
	public $fields = array();
	
	/**
	 * @var array Defenition of relations.
	 * @todo make protected
	 */	
	public $relations = array();	
	
	/**
	 * @var array Field-Value pairs.
	 */
	protected $valuesContainer = array();
	
	/**
	 * @var array Field-Value pairs of initial values (fetched from DB).
	 */
	protected $initialValuesContainer = array();
	
	/**
	 * @var EntityManager Entity Manager.
	 */
	protected $entityManager;
	
		
	
	public function __construct($defs = array(), EntityManager $entityManager = null)
	{
		if (empty($this->entityName)) {
			$this->entityName = end(explode('\\', get_class($this)));
		}
		
		$this->entityManager = $entityManager;
		
		if (!empty($defs['fields'])) {
			$this->fields = $defs['fields'];
		}
		
		if (!empty($defs['relations'])) {
			$this->relations = $defs['relations'];
		}		
	}	
	
	public function clear($name = null)
	{
		if (is_null($name)) {
			$this->reset();
		}
		unset($this->valuesContainer[$name]);
	}
		
	public function reset()
	{
		$this->valuesContainer = array();
	}	
	
	public function set($p1, $p2 = null)
	{
		if (is_array($p1)) {
			if ($p2 === null) {
				$p2 = false;
			}
			$this->populateFromArray($p1, $p2);
		} else {
			$name = $p1;
			$value = $p2;
			if ($name == 'id') {
				$this->id = $value;
			}
			if (array_key_exists($name, $this->fields)) {	
				$this->valuesContainer[$name] = $value;
			}
		}
	}
	
	public function get($name, $params = array())
	{
		if ($name == 'id') {
			return $this->id;
		}
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->$method();		
		}
		
		if (isset($this->valuesContainer[$name])) {
			return $this->valuesContainer[$name];
		}
		
		if (!empty($this->relations[$name])) {
			$value = $this->entityManager->getRepository($this->entityName)->findRelated($this, $name, $params);
			return $value;
		}
		
		return null;
	}
	
	public function has($name)
	{
		if ($name == 'id') {
			return isset($this->id);
		}
		if (array_key_exists($name, $this->valuesContainer)) {
			return true;
		}
		return false;
	}	
	
	public function populateFromArray(array $arr, $onlyAccessible = true, $reset = false)
	{
		if ($reset) {
			$this->reset();
		}
	
		foreach ($this->fields as $field => $fieldDefs) {
			if (array_key_exists($field, $arr)) {
				if ($field == 'id') {
					$this->id = $arr[$field]; 
					continue;
				}
				if ($onlyAccessible) {
					if (isset($fieldDefs['notAccessible']) && $fieldDefs['notAccessible'] == true) {
						continue;
					}
				}
				
				$value = $arr[$field];
				
				if (!is_null($value)) {
					switch ($fieldDefs['type']) {
						case self::VARCHAR:						
							break;
						case self::BOOL:
							$value = ($value === 'true' || $value === '1' || $value === true);
							break;
						case self::INT:
							$value = intval($value);
							break;
						case self::FLOAT:
							$value = floatval($value);
							break;
						default:
							break;
					}
				}
				
				$this->valuesContainer[$field] = $value;
			}
		}
	}
	
	public function isNew()
	{
		return $this->isNew;
	}
	
	public function setIsNew($isNew)
	{
		$this->isNew = $isNew;
	}	
		
	public function getEntityName()
	{
		return $this->entityName;
	}	
	
	public function hasField($fieldName)
	{
		return isset($this->fields[$fieldName]);
	}	
	
	public function hasRelation($relationName)
	{
		return isset($this->relations[$relationName]);
	}
	
	public function toArray()
	{		
		$arr = array();
		if (isset($this->id)) {
			$arr['id'] = $this->id;
		}
		foreach ($this->fields as $field => $defs) {		
			if ($field == 'id') {
				continue;
			}
			$arr[$field] = $this->get($field);

		}		
		return $arr;
	}
	
	public function getFields()
	{
		return $this->fields;
	}
	
	public function getRelations()
	{
		return $this->relations;
	}
	
	public function getFetchedValue($fieldName)
	{
		if (isset($this->initialValuesContainer[$fieldName])) {
			return $this->initialValuesContainer[$fieldName];
		}
		return null;
	}
	
	public function resetFetchedValues()
	{
		$this->initialValuesContainer = array();
	}
	
	public function setFresh()
	{
		$this->initialValuesContainer = $this->valuesContainer;
	}
}

