<?php

namespace Espo\Core\Utils\Database\Schema;

abstract class BaseRebuildActions
{
	private $metadata;

	private $config;

	private $entityManager;
	
	protected $currentSchema = null;

	protected $metadataSchema = null;


	public function __construct(\Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\Config $config, \Espo\Core\ORM\EntityManager $entityManager)
	{
		$this->metadata = $metadata;
		$this->config = $config;
		$this->entityManager = $entityManager;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	protected function getConfig()
	{
		return $this->config;
	}
	
	protected function getMetadata()
	{
		return $this->metadata;
	}

	public function setCurrentSchema(\Doctrine\DBAL\Schema\Schema $currentSchema)
	{
		$this->currentSchema = $currentSchema;
	}  

	public function setMetadataSchema(\Doctrine\DBAL\Schema\Schema $metadataSchema)
	{
		$this->metadataSchema = $metadataSchema;
	} 

	protected function getCurrentSchema()
	{
		return $this->currentSchema;
	}

	protected function getMetadataSchema()
	{
		return $this->metadataSchema;
	}

	/*
	public function beforeRebuild()
	{	 	
	}

	public function afterRebuild()
	{	 	
	}
	*/
	
	
}

