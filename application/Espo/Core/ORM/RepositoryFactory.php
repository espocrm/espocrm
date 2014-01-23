<?php

namespace Espo\Core\ORM;

class RepositoryFactory extends \Espo\ORM\RepositoryFactory
{	
	protected $defaultRepositoryClassName = '\\Espo\\Core\\ORM\\Repository';
	
	protected $espoMetadata = false;
	
	public function setEspoMetadata($espoMetadata)
	{
		$this->espoMetadata = $espoMetadata;
	}
	
	public function create($name)
	{
		$repository = parent::create($name);
		$repository->setMetadata($this->espoMetadata);
		return $repository;
	}

}

