<?php

namespace Espo\Services;


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
	
	public function getEntityManager()
	{
		return $this->entityManager;
	}
	
	public function getUser()
	{
		return $this->user;
	}
	
	
	public function getEntity($id)
	{
	
	}	
}
