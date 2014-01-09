<?php

namespace Espo\Core\ORM;

class Repository extends \Espo\ORM\Repository
{	
	protected function getEntityById($id)
	{		
		$entity = $this->getEntityFactory()->create($this->entityName);
		$params = array();
		if ($entity->hasRelation('emailAddresses')) {
			$params['distinct'] = true;
			$params['leftJoins'] = array('emailAddresses');
		}
		if ($this->mapper->selectById($entity, $id, $params)) {
			$entity->setFresh();
			return $entity;
		}		
		return null;
	}
	
	protected function handleEmailAddressParams(&$params)
	{
		$defs = $this->getEntityManager()->getMetadata()->get($this->entityName);
		if (!empty($defs['relations']) && array_key_exists('emailAddresses', $defs['relations'])) {
			$params['distinct'] = true;	
			if (empty($params['leftJoins'])) {
				$params['leftJoins'] = array();
			}
			$params['leftJoins'] = array('emailAddresses');
		}
	}
	
	public function find(array $params = array())
	{
		$this->handleEmailAddressParams($params);		
		return parent::find($params);
	}
	
	public function findRelated(Entity $entity, $relationName, array $params = array())
	{
		$this->handleEmailAddressParams($params);		
		return parent::findRelated($entity, $relationName, $params);
	}
	
	public function save(Entity $entity)
	{		
		$nowString = date('Y-m-d H:i:s', time());		
		$restoreData = array();							
		
		if ($entity->isNew()) {			
			if (!$entity->has('id')) {
				$entity->set('id', uniqid());
			}
		
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
		$result = parent::save($entity);
		
		$entity->set($restoreData);		
		$this->handleSpecifiedRelations($entity);
		
		return $result;
	}
	
	protected function handleSpecifiedRelations(Entity $entity)
	{
		foreach ($entity->getRelations() as $name => $defs) {
			if ($defs['type'] == $entity::HAS_MANY || $defs['type'] == $entity::MANY_MANY) {
				$fieldName = $name . 'Ids';
				if ($entity->has($fieldName)) {					
					$specifiedIds = $entity->get($fieldName);
					if (is_array($specifiedIds)) {
						$toRemoveIds = array();
						$existingIds = array();
						foreach ($entity->get($name) as $foreignEntity) {
							$existingIds[] = $foreignEntity->id;
						}				
						foreach ($existingIds as $id) {
							if (!in_array($id, $specifiedIds)) {
								$toRemoveIds[] = $id;
							}
						}										
						foreach ($specifiedIds as $id) {
							if (!in_array($id, $existingIds)) {
								$this->relate($entity, $name, $id);
							}							
						}
						foreach ($toRemoveIds as $id) {
							$this->unrelate($entity, $name, $id);
						}
					}
				}
			}
		}
	}
}

