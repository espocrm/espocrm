<?php

namespace Espo\Core\ORM;

use \Espo\ORM\Entity;

class Repository extends \Espo\ORM\Repository
{
	
	public function save(Entity $entity)
	{
		if ($entity->isNew()) {
			if ($entity->hasField('createdAt')) {
				$entity->set('createdAt', date());
			}
			if ($entity->hasField('createdById')) {
				$entity->set('createdById', $this->entityManager->user->id);
			}
			$entity->clear('modifiedById');
			$entity->clear('modifiedAt');
		} else {
			if ($entity->hasField('modifiedAt')) {
				$entity->set('modifiedAt', date());
			}
			if ($entity->hasField('modifiedById')) {
				$entity->set('modifiedById', $this->entityManager->user->id);
			}
			$entity->clear('createdById');
			$entity->clear('createdAt');
		}		
		parent::save($entity);
	}
}


