<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Error;

class Record extends \Espo\Core\Services\Base
{
	static public $dependencies = array(
		'entityManager',
		'user',
	);

	private $user;

	private $entityManager;

	protected $entityName;

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

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getUser()
	{
		return $this->user;
	}

	public function getEntity($id)
	{
		return $this->getEntityManager()->getRepository($this->name)->find($id);
	}

	public function findEntities($params)
	{
		$collection = $this->getEntityManager()->getRepository($this->name)->findBy();
    	$criteria = $this->getCriteriaManager()->createCriteria($params);
    	return $collection->matching($criteria);
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

	public function updateEntity($entity, $data)
	{
		// TODO validate $data
		$entity->fromArray($data);
		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();
		return $entity;
	}

	public function deleteEntity($entity)
	{
		$this->getEntityManager()->remove($entity);
		$this->getEntityManager()->flush();
		return true;
	}

    public function findLinkedEntities($entity, $link, $params)
    {
    	$criteria = $this->getCriteriaManager()->createCriteria($params);
    	$methodName = 'get' . ucfirst($link);
    	$collection = $entity->$methodName();
    	return $collection->matching($criteria);
    }
    
    public function linkEntity($entity, $link, $foreignId)
    {
    	$entityName = $this->getEntityManager()->getEntityName($entity);    	
    	$foreignEntityName = $this->getMetadata()->get('entityDefs.' . $entityName . '.links.' . $link . '.entity');
    	
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
    
    public function unlinkEntity($entity, $link, $foreignId)
    {
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

