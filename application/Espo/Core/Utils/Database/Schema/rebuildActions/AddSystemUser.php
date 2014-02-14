<?php

namespace Espo\Core\Utils\Database\Schema\rebuildActions;

class AddSystemUser extends \Espo\Core\Utils\Database\Schema\BaseRebuildActions
{
	
	public function afterRebuild()
	{	 
		$userId = $this->getConfig()->get('systemUser.id');

		$entity = $this->getEntityManager()->getEntity('User', $userId);

		if (!isset($entity)) {

			$systemUser = $this->getConfig()->get('systemUser');

			$entity = $this->getEntityManager()->getEntity('User');
			$entity->set($systemUser);			

			return $this->getEntityManager()->saveEntity($entity);			
		}				
	}	
	
}

