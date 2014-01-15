<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Utils\Util;

class Record extends \Espo\Core\Services\Base
{
	static public $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl',
		'config'
	);
	
	protected $entityName;

	public function setEntityName($entityName)
	{
		$this->entityName = $entityName;
	}

	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}
	
	protected function getAcl()
	{
		return $this->injections['acl'];
	}
	
	protected function getConfig()
	{
		return $this->injections['config'];
	}
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	protected function getRepository()
	{		
		return $this->getEntityManager()->getRepository($this->entityName);
	}

	public function getEntity($id = null)
	{
		$entity = $this->getRepository()->get($id);		
		if (!empty($entity) && !empty($id)) {		
			$this->loadLinkMultipleFields($entity);			
			$this->loadParentNameFields($entity);
		}
		
		if (!empty($entity) && !empty($id)) {			
			if (!$this->getAcl()->check($entity, 'read')) {
				throw new Forbidden();
			}
		}
				
		return $entity;
	}
	
	protected function loadLinkMultipleFields(Entity $entity)
	{
		$fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.fields', array());
		foreach ($fieldDefs as $field => $defs) {
			if ($defs['type'] == 'linkMultiple') {
				$entity->loadLinkMultipleField($field);	
			}
		}
	}
	
	protected function loadParentNameFields(Entity $entity)
	{
		$fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.fields', array());
		foreach ($fieldDefs as $field => $defs) {
			if ($defs['type'] == 'linkParent') {								
				$id = $entity->get($field . 'Id');
				$scope = $entity->get($field . 'Type');
				
				if ($scope) {				
					if ($foreignEntity = $this->getEntityManager()->getEntity($scope, $id)) {
						$entity->set($field . 'Name', $foreignEntity->get('name'));
					}
				}
			}
		}
	}
	
	protected function getSelectManager($entityName)
	{
    	$moduleName = $this->getMetadata()->getScopeModuleName($entityName);
		if ($moduleName) {
			$className = '\\Espo\\Modules\\' . $moduleName . '\\SelectManagers\\' . Util::normilizeClassName($entityName);
		} else {
			$className = '\\Espo\\SelectManagers\\' . Util::normilizeClassName($entityName);
		}    	
    	if (!class_exists($className)) {
    		$className = '\\Espo\\Core\\SelectManager';
    	}
		
		$selectManager = new $className($this->getEntityManager(), $this->getUser(), $this->getAcl(), $this->getMetadata());
		$selectManager->setEntityName($entityName);
				
		return $selectManager;
	}

	public function createEntity($data)
	{
		// TODO validate $data
		$entity = $this->getEntity();
		
		$entity->set($data);		
		
		if ($this->getRepository()->save($entity)) {
			return $entity;
		}		
		
		throw new Error();
	}

	public function updateEntity($id, $data)
	{	
		// TODO validate $data
		$entity = $this->getEntity($id);
		
		if (!$this->getAcl()->check($entity, 'edit')) {
			throw new Forbidden();
		}
		
		$entity->set($data);		
		
		$this->getRepository()->save($entity);
		return $entity;
	}

	public function deleteEntity($id)
	{
		$entity = $this->getEntity($id);

		if (!$this->getAcl()->check($entity, 'delete')) {
			throw new Forbidden();
		}
	
		return $this->getRepository()->remove($entity);
	}
	
	public function findEntities($params)
	{	
		$selectParams = $this->getSelectManager($this->entityName)->getSelectParams($params, true);
		$collection = $this->getRepository()->find($selectParams);		
		
		foreach ($collection as $e) {
			$this->loadParentNameFields($e);
		}
		
    	return array(
    		'total' => $this->getRepository()->count($selectParams),
    		'collection' => $collection,
    	);
	}

    public function findLinkedEntities($id, $link, $params)
    {    	
    	$entity = $this->getEntity($id);    	
    	$foreignEntityName = $entity->relations[$link]['entity'];
    	
		if (!$this->getAcl()->check($entity, 'read')) {
			throw new Forbidden();
		}
		if (!$this->getAcl()->check($foreignEntityName, 'read')) {
			throw new Forbidden();
		}
    	    	
		$selectParams = $this->getSelectManager($foreignEntityName)->getSelectParams($params, true);
		$collection = $this->getRepository()->findRelated($entity, $link, $selectParams);
		
		foreach ($collection as $e) {
			$this->loadParentNameFields($e);
		}
		
    	return array(
    		'total' => $this->getRepository()->countRelated($entity, $link, $selectParams),
    		'collection' => $collection,
    	);
    }
    
    public function linkEntity($id, $link, $foreignId)
    {    
		$entity = $this->getEntity($id);	
    
    	$entityName = $entity->getEntityName($entity);
    	$foreignEntityName = $entity->relations[$link]['entity'];    	   	
    	    	
		if (!$this->getAcl()->check($entity, 'edit')) {
			throw new Forbidden();
		}
    	
    	if (empty($foreignEntityName)) {
    		throw new Error();
    	}
    	
    	$foreignEntity = $this->getEntityManager()->getEntity($foreignEntityName, $foreignId);
    	
		if (!$this->getAcl()->check($foreignEntity, 'edit')) {
			throw new Forbidden();
		}		
    	
    	if (!empty($foreignEntity)) {
			$this->getRepository()->relate($entity, $link, $foreignEntity);
			return true;    	
    	}
    }
    
    public function unlinkEntity($id, $link, $foreignId)
    {
		$entity = $this->getEntity($id);	
    
    	$entityName = $entity->getEntityName($entity);
    	$foreignEntityName = $entity->relations[$link]['entity'];    	   	
    	    	
		if (!$this->getAcl()->check($entity, 'edit')) {
			throw new Forbidden();
		}
    	
    	if (empty($foreignEntityName)) {
    		throw new Error();
    	}
    	
    	$foreignEntity = $this->getEntityManager()->getEntity($foreignEntityName, $foreignId);
    	
		if (!$this->getAcl()->check($foreignEntity, 'edit')) {
			throw new Forbidden();
		}
     	
    	if (!empty($foreignEntity)) {
			$this->getRepository()->unrelate($entity, $link, $foreignEntity);
			return true;    	
    	}
    }
    
    public function massUpdate($attributes = array(), $ids = array(), $where = array())
    {
    	$idsUpdated = array();    	
    	$repository = $this->getRepository();
    	    	
    	if (!empty($ids)) {
    		foreach ($ids as $id) {
    			$entity = $this->getEntity($id);
    			if ($this->getAcl()->check($entity, 'edit')) {
    				$entity->set($attributes);
    				if ($repository->save($entity)) {
    					$idsUpdated[] = $id;
    				}
    			}
    		}
    	}
    	
    	return $idsUpdated;
    	
    	// TODO update $where
    }

}

