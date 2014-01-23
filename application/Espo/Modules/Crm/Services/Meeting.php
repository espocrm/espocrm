<?php

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Meeting extends \Espo\Services\Record
{
	protected function storeEntity(Entity $entity)
	{
		$assignedUserId = $entity->get('assignedUserId');
		if ($assignedUserId && $entity->has('usersIds')) {
			$usersIds = $entity->get('usersIds');
			if (!is_array($usersIds)) {
				$usersIds = array();
			}
			if (!in_array($assignedUserId, $usersIds)) {
				$usersIds[] = $assignedUserId;
				$entity->set('usersIds', $usersIds);
				$hash = $entity->get('usersNames');
				if ($hash instanceof \stdClass) {
					$hash->assignedUserId = $entity->get('assignedUserName');
					$entity->set('usersNames', $hash);
				}
			}
		}		
		return parent::storeEntity($entity);
	}
}

