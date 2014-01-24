<?php

namespace Espo\Core\ORM;

class RepositoryFactory extends \Espo\ORM\RepositoryFactory
{	
	protected $defaultRepositoryClassName = '\\Espo\\Core\\ORM\\Repositories\\RDB';
	
	protected $espoMetadata = false;
	
	public function setEspoMetadata($espoMetadata)
	{
		$this->espoMetadata = $espoMetadata;
	}
	
	public function create($name)
	{
		$repository = parent::create($name);
		
    	$dependencies = $repository->getDependencyList();
    	foreach ($dependencies as $name) {
    		$repository->inject($name, $this->entityManager->getContainer()->get($name));
    	}
		
		$repository->setMetadata($this->espoMetadata);
		return $repository;
	}
}

