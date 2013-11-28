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
    	$fullName = $this->metadata->getEntityPath($entityName);
        return $this->wrapped->getRepository($entityName);
    }
    
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getWrapped()
    {
    	return $this->wrapped;
    }
}
