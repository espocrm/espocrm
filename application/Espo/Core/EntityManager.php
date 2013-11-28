<?php

namespace Espo\Core;

use Doctrine\Common\EventManager;

class EntityManager extends \Doctrine\ORM\Decorator\EntityManagerDecorator
{
	private $metadata;

	public function setMetadata($metadata)
	{
		$this->metadata = $metadata;
	}
	
    public function getRepository($entityName)
    {
    	$className = $this->metadata->getEntityPath($entityName);
        return $this->wrapped->getRepository($className);
    }
    
    public function find($entityName, $id)
    {
    	$className = $this->metadata->getEntityPath($entityName);
        return $this->wrapped->find($className, $id);
    }
    
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getWrapped()
    {
    	return $this->wrapped;
    }
}
