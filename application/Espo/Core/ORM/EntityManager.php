<?php

namespace Espo\Core\ORM;

class EntityManager extends \Espo\ORM\EntityManager
{
	protected $espoMetadata;

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

