<?php

namespace Espo\Core\ORM;

class RepositoryFactory extends \Espo\ORM\RepositoryFactory
{	
	protected $defaultRepositoryClassName = '\\Espo\\Core\\ORM\\Repositories\\RDB';

	public function create($name)
	{
		$repository = parent::create($name);
		
    	$dependencies = $repository->getDependencyList();
    	foreach ($dependencies as $name) {
    		$repository->inject($name, $this->entityManager->getContainer()->get($name));
    	}
		return $repository;
	}
}

