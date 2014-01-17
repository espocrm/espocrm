<?php

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

class Stream extends \Espo\Core\Hooks\Base
{
	protected $streamService = null;
	
	protected $dependencies = array(
		'entityManager',
		'config',
		'metadata',
		'acl',
		'user',
	);
	
	protected function init()
	{
		$this->dependencies[] = 'serviceFactory';
	}
	
	protected function getServiceFactory()
	{
		return $this->getInjection('serviceFactory');
	}
	
	protected function checkHasStream(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		return $this->getMetadata()->get("scopes.{$entityName}.stream");
	}
	
	public function afterRemove(Entity $entity)
	{
		if ($this->checkHasStream($entity)) {
			$this->getStreamService()->unfollowAllUsersFromEntity($entity);
		}
	}
	
	public function afterSave(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		
		if ($this->checkHasStream($entity)) {			
			if (!$entity->getFetchedValue('id')) {
				$this->noteCreate($entity);
			}
		}	
	}
	
	protected function getStreamService()
	{
		if (empty($this->streamService)) {
			$this->streamService = $this->getServiceFactory()->createByClassName('\\Espo\\Services\\Stream');
		}
		return $this->streamService;		
	}
	
	protected function noteCreate(Entity $entity)
	{
		$this->getStreamService()->noteCreate($entity);
	}
}

