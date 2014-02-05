<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

use \Espo\Core\Utils\Util;

class SelectManagerFactory
{
	private $entityManager;
	
	private $user;
	
	private $acl;
	
	private $metadata;

    public function __construct($entityManager, \Espo\Entities\User $user, Acl $acl, $metadata)
    {
    	$this->entityManager = $entityManager;
    	$this->user = $user;
    	$this->acl = $acl;
    	$this->metadata = $metadata;
    }
    
	public function create($entityName)
	{
    	$className = '\\Espo\\Custom\\SelectManagers\\' . Util::normilizeClassName($entityName);
		if (!class_exists($className)) {
			$moduleName = $this->metadata->getScopeModuleName($entityName);
			if ($moduleName) {
				$className = '\\Espo\\Modules\\' . $moduleName . '\\SelectManagers\\' . Util::normilizeClassName($entityName);
			} else {
				$className = '\\Espo\\SelectManagers\\' . Util::normilizeClassName($entityName);
			}    	
			if (!class_exists($className)) {
				$className = '\\Espo\\Core\\SelectManagers\\Base';
			}
    	}
		
		$selectManager = new $className($this->entityManager, $this->user, $this->acl, $this->metadata);
		$selectManager->setEntityName($entityName);
				
		return $selectManager;
	}	
}

