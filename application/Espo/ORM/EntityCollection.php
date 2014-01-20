<?php

namespace Espo\ORM;

class EntityCollection implements \Iterator, \Countable, \ArrayAccess, \SeekableIterator
{
	private $entityFactory = null;	
	
	private $entityName;
	
	private $position = 0;
	
	protected $container = array();
	
	public function __construct($data = array(), $entityName, EntityFactory $entityFactory = null)
	{
		$this->container = $data;		
		$this->entityName = $entityName;
		$this->entityFactory = $entityFactory;
	}
	
	public function rewind()
	{
		$this->position = 0;

		while (!$this->valid() && $this->position < count($this->container)) {
			$this->position ++;
		}
	}
	
	public function current()
	{	 
		return $this->getEntityByOffset($this->position);
	}
	
	public function key()
	{		
		return $this->position;
	}
	
	public function next()
	{
		do {
			$this->position ++;
			$next = false;
			if (!$this->valid() && $this->position < count($this->container)) {
				$next = true;
			}
		} while ($next);
	}
	
	public function valid()
	{	
		return isset($this->container[$this->position]);
	}
	
	public function offsetExists($offset)
	{
		return isset($this->container[$offset]);
	}
	
	public function offsetGet($offset)
	{
		if (!isset($this->container[$offset])) {
			return null;
		}
		return $this->getEntityByOffset($offset);
	}
	
	public function offsetSet($offset, $value)
	{
		if (!($value instanceof Entity)) {
			throw new \InvalidArgumentException('Only Entity is allowed to be added to EntityCollection.');
		}
		
		if (is_null($offset)) {
			$this->container[] = $value;
		} else {
			$this->container[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{	
		unset($this->container[$offset]);
	}

	public function count()
	{
		return count($this->container);
	}
	
	public function seek($offset)
	{
		$this->position = $offset;
		if (!$this->valid()) {
			throw new \OutOfBoundsException("Invalid seek offset ($offset).");
		}
	}	

	public function append(Entity $entity)
	{
		$this->container[] = $entity;
	}
	
	private function getEntityByOffset($offset)
	{
		$value = $this->container[$offset];

		if ($value instanceof Entity) {
			return $value;
		} else if (is_array($value)) {			
			$this->container[$offset] = $this->buildEntityFromArray($value);			
		} else {
			return null;
		}
		
		return $this->container[$offset];
	}
	
	protected function buildEntityFromArray(array $dataArray)
	{
		$entity = $this->entityFactory->create($this->entityName);
		$entity->set($dataArray);
		$entity->setAsFetched();		
		return $entity;
	}

	public function getEntityName()
	{
		return $this->entityName;
	}	

	public function getInnerContainer()
	{
		return $this->container;
	}
	
	public function merge(EntityCollection $collection)
	{
		$newData = $this->container;		
		$incomingData = $collection->getInnerContainer();
		
		foreach ($incomingData as $v) {
			if (!$this->contains($v)) {
				$this->container[] = $v;
			}
		}
	}
	
	public function contains($value)
	{
		if ($this->indexOf($value) !== false) {
			return true;
		}
		return false;
	}
	
	public function indexOf($value)
	{
		$index = 0;
		if (is_array($value)) {
			foreach ($this->container as $v) {				
				if (is_array($v)) {
					if ($value['id'] == $v['id']) {
						return $index;
					}
				} else if ($v instanceof Entity) {
					if ($value['id'] == $v->id) {
						return $index;
					}
				}
				$index ++;
			}
		} else if ($value instanceof Entity) {			
			foreach ($this->container as $v) {
				if (is_array($v)) {
					if ($value->id == $v['id']) {
						return $index;
					}
				} else if ($v instanceof Entity) {
					if ($value === $v) {
						return $index;
					}
				}
				$index ++;
			}
		}
		return false;
	}
	
	public function toArray()
	{
		$arr = array();
		foreach ($this as $entity) {
			$arr[] = $entity->toArray();
		}
		return $arr;
	}

}

