<?php

namespace Espo\Core\ORM;

use \Espo\ORM\Entity;

class Repository extends \Espo\ORM\Repository
{
	
	public function save(Entity $entity)
	{		
		$nowString = date('Y-d-m H:i:s', time());
		
		if ($entity->isNew()) {
			if ($entity->hasField('createdAt')) {
				$entity->set('createdAt', $nowString);
			}
			if ($entity->hasField('createdById')) {
				$entity->set('createdById', $this->entityManager->getUser()->id);
			}
			$entity->clear('modifiedById');
			$entity->clear('modifiedAt');
		} else {
			if ($entity->hasField('modifiedAt')) {
				$entity->set('modifiedAt', $nowString);
			}
			if ($entity->hasField('modifiedById')) {
				$entity->set('modifiedById', $this->entityManager->getUser()->id);
			}
			$entity->clear('createdById');
			$entity->clear('createdAt');
		}		
		parent::save($entity);
	}
}

