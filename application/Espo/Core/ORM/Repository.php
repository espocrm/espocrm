<?php

namespace Espo\Core\ORM;

use \Espo\ORM\Entity;

class Repository extends \Espo\ORM\Repository
{
	
	public function save(Entity $entity)
	{		
		$nowString = date('Y-m-d H:i:s', time());
		
		$restoreData = array();
		
		if ($entity->isNew()) {
			if ($entity->hasField('createdAt')) {
				$entity->set('createdAt', $nowString);
			}
			if ($entity->hasField('createdById')) {
				$entity->set('createdById', $this->entityManager->getUser()->id);
			}
			
			if ($entity->has('modifiedById')) {
				$restoreData['modifiedById'] = $entity->get('modifiedById');
			}
			if ($entity->has('modifiedAt')) {
				$restoreData['modifiedAt'] = $entity->get('modifiedAt');
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
			
			if ($entity->has('createdById')) {
				$restoreData['createdById'] = $entity->get('createdById');
			}
			if ($entity->has('createdAt')) {
				$restoreData['createdAt'] = $entity->get('createdAt');
			}
			$entity->clear('createdById');
			$entity->clear('createdAt');
		}		
		parent::save($entity);
		
		$entity->set($restoreData);
	}
}

