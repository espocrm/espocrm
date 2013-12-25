<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Error;

class Record extends \Espo\Core\Services\Base
{
	static public $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl'
	);
	
	protected $entityName;

	private $user;

	private $entityManager;
	
	private $metadata;
	
	private $selectManager;
	
	private $acl;

	public function setEntityName($entityName)
	{
		$this->entityName = $entityName;
	}

	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function setUser($user)
	{
		$this->user = $user;
	}
	
	public function setAcl($acl)
	{
		$this->acl = $acl;
	}	
	
	public function setMetadata($metadata)
	{
		$this->metadata = $metadata;
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getUser()
	{
		return $this->user;
	}
	
	protected function getAcl()
	{
		return $this->acl;
	}
	
	protected function getMetadata()
	{
		return $this->metadata;
	}
	
	protected function getRepository()
	{		
		return $this->getEntityManager()->getRepository($this->entityName);
	}

	public function getEntity($id = null)
	{
		$entity = $this->getRepository()->get($id);		
		
		if (!empty($entity) && !empty($id)) {
			if ($entity->hasRelation('teams') && $entity->hasField('teamsIds')) {
				$teams = $entity->get('teams');
				$ids = array();
				$names = array();				
				foreach ($teams as $team) {
					$id = $team->id;
					$ids[] = $id;
					$names[$id] = $team->get('name');
				}
				
				$entity->set('teamsIds', $ids);
				$entity->set('teamsNames', $names);
			}
		}
		
		return $entity;
	}
	
	protected function getSelectManager()
	{
		if (empty($this->selectManager)) {
			$this->selectManager = new \Espo\Core\SelectManager($this->entityManager, $this->getUser(), $this->getAcl());
		}		
		return $this->selectManager;
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
		$selectParams = $this->getSelectManager()->getSelectParams($this->name, $params, true);
		$collection = $this->getRepository()->find($selectParams);

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
    	    	
		$selectParams = $this->getSelectManager()->getSelectParams($foreignEntityName, $params, true);
		$collection = $this->getRepository()->findRelated($entity, $link, $selectParams);
		
		// TODO
		// $repository->via($entity, $link)->find($selectParams);
		
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

}

