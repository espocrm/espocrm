<?php

namespace Espo\Core\ORM;
use \Espo\Core\Utils\Util;

class EntityManager extends \Espo\ORM\EntityManager
{
	protected $espoMetadata;
	
	private $hookManager;
	
	protected $user;
	
	protected $container;
	
	public function setContainer(\Espo\Core\Container $container)
	{
		$this->container = $container;	
	}
	
	public function getContainer()
	{
		return $this->container;	
	}
	
	public function setUser($user)
	{
		$this->user = $user;
	}
	
	public function getUser()
	{
		return $this->user;
	}	

	public function setEspoMetadata($espoMetadata)
	{
		$this->espoMetadata = $espoMetadata;
		$this->repositoryFactory->setEspoMetadata($espoMetadata);
	}
	
	public function setHookManager(\Espo\Core\HookManager $hookManager)
	{
		$this->hookManager = $hookManager;
	}
	
	public function getHookManager()
	{
		return $this->hookManager;
	}

	public function normalizeRepositoryName($name)
	{			
		return $this->espoMetadata->getRepositoryPath($name);
	}

	public function normalizeEntityName($name)
	{
		return $this->espoMetadata->getEntityPath($name);
	}
}

