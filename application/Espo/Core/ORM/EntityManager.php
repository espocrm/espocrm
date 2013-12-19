<?php

namespace Espo\Core\ORM;

class EntityManager extends \Espo\ORM\EntityManager
{
	protected $espoMetadata;
	
	protected $user;
	
	public function setUser()
	{
		$this->user = $user;
	}

	public function setEspoMetadata($espoMetadata)
	{
		$this->espoMetadata = $espoMetadata;
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

