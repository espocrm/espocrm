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
	
	private $queryManager;
	
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

	public function getEntity($id)
	{
		return $this->getEntityManager()->getRepository($this->name)->find($id);
	}
	
	protected function getQueryManager()
	{
		if (empty($this->queryManager)) {
			$this->queryManager = new QueryManager($this->entityManager, $this->getUser(), $this->getAcl());
		}		
		return $this->queryManager;
	}

	public function createEntity($data)
	{
		// TODO validate $data
		$entity = $this->getEntityManager()->createEntity($this->name);
		$entity->fromArray($data);
		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();
		return $entity;
	}

	public function updateEntity($id, $data)
	{	
		$entity = $this->getEntity($id);
		
		if (!$this->getAcl()->check($entity, 'edit')) {
			throw new Forbidden();
		}
	
		// TODO validate $data
		$entity->fromArray($data);
		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();
		return $entity;
	}

	public function deleteEntity($id)
	{
		$entity = $this->getEntity($id);

		if (!$this->getAcl()->check($entity, 'delete')) {
			throw new Forbidden();
		}
	
		$this->getEntityManager()->remove($entity);
		$this->getEntityManager()->flush();
		return true;
	}
	
	public function findEntities($params)
	{
		$collection = $this->getEntityManager()->getRepository($this->name)->find();
    	$qu = $this->getQueryManager()->createListQuery($this->name, $params);
    	
    	$collection = $qu->getResult();
    	return $collection;
	}

    public function findLinkedEntities($id, $link, $params)
    {    
		$entity = $this->getEntity($id);
		
    	$entityName = $this->getEntityManager()->getEntityName($entity);    	
    	$foreignEntityName = $this->getMetadata()->get('entityDefs.' . $entityName . '.links.' . $link . '.entity');
		
		if (!$this->getAcl()->check($entity, 'read')) {
			throw new Forbidden();
		}
		if (!$this->getAcl()->check($foreignEntityName, 'read')) {
			throw new Forbidden();
		}    
  		
    	$qu = $this->getQueryManager()->createLinkedListQuery($entity, $link, $params);
    	
    	$collection = $qu->getResult();
    	return $collection;
    }
    
    public function linkEntity($id, $link, $foreignId)
    {    
		$entity = $this->getEntity($id);	
    
    	$entityName = $this->getEntityManager()->getEntityName($entity);    	
    	$foreignEntityName = $this->getMetadata()->get('entityDefs.' . $entityName . '.links.' . $link . '.entity');
    	
		if (!$this->getAcl()->check($entity, 'edit')) {
			throw new Forbidden();
		}
    	
    	if (empty($foreignEntityName)) {
    		throw new Error();
    	}
    	
    	$methodName = 'get' . ucfirst($link);
    	$foreignEntity = $this->getEntityManager()->getRepository($foreignEntityName)->find($foreignId);
    	
    	if (!empty($foreignEntity)) {
			$entity->$methodName()->add($foreignEntity);
			return true;    	
    	}
    }
    
    public function unlinkEntity($id, $link, $foreignId)
    {
    	$entity = $this->getEntity($id);    
    
    	$entityName = $this->getEntityManager()->getEntityName($entity);    	
    	$foreignEntityName = $this->getMetadata()->get('entityDefs.' . $entityName . '.links.' . $link . '.entity');
    	
    	if (empty($foreignEntityName)) {
    		throw new Error();
    	}
    	
    	$methodName = 'get' . ucfirst($link);    	
		$entity->$methodName()->remove($foreignId);
		return true;
    }

}

