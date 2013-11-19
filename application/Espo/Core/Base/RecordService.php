<?php

namespace Espo\Core\Base;

abstract class RecordService
{
	public static $dependencies = array(
		'config',
		'entityManager',
		'datetime',
	);
	
	private $config;
	
	private $entityManager;

	private $datetime;
	
	public function setConfig($config)
	{
		$this->config = $config;
	}
	
	protected function getConfig()
	{
		return $this->config;
	}
	
	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	public function setDatetime($datetime)
	{
		$this->datetime = $datetime;
	}	
	
	protected function getDatetime()
	{
		return $this->datetime;
	}	
	
	protected function fetch($id)
	{
		return $this->getEntityManager()->findById($id);
	}
	
	protected function save($entity)
	{
		$this->getEntityManager()->save($entity);
	}
	
	protected function findAssociated($entity, $link)
	{
		return array();
		
		
	}


}
