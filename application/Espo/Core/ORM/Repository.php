<?php

namespace Espo\Core\ORM;

class Repository extends \Espo\ORM\Repository
{	
	protected function getEntityById($id)
	{		
		$entity = $this->getEntityFactory()->create($this->entityName);
		$params = array();
		$this->handleEmailAddressParams($params);
		if ($this->mapper->selectById($entity, $id, $params)) {
			$entity->setFresh();
			return $entity;
		}		
		return null;
	}
	
	protected function handleSelectParams(&$params)
	{
		$this->handleEmailAddressParams($params);
	}
	
	protected function handleEmailAddressParams(&$params)
	{
		$defs = $this->getEntityManager()->getMetadata()->get($this->entityName);
		if (!empty($defs['relations']) && array_key_exists('emailAddresses', $defs['relations'])) {				
			if (empty($params['leftJoins'])) {
				$params['leftJoins'] = array();
			}
			if (empty($params['whereClause'])) {
				$params['whereClause'] = array();
			}
			if (empty($params['joinConditions'])) {
				$params['joinConditions'] = array();
			}
			$params['distinct'] = true;
			$params['leftJoins'] = array('emailAddresses');
			$params['joinConditions'] = array(
				'emailAddresses' => array(
					'primary' => 1
				)
			);
		}
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
		
		$this->handleEmailAddressSave($entity);	
		$this->handleSpecifiedRelations($entity);
		
		return $result;
	}
	
	protected function handleEmailAddressSave(Entity $entity)
	{
		if ($entity->hasRelation('emailAddresses') && $entity->hasField('emailAddress')) {
			$email = $entity->get('emailAddress');			
			$pdo = $this->getPDO();
			
			if (!empty($email)) {
				if ($email != $entity->getFetchedValue('emailAddress')) {
					$emailAddressRepository = $this->getEntityManager()->getRepository('EmailAddress');
					$emailAddress = $emailAddressRepository->where(array('lower' => strtolower($email)))->findOne();
					$isNewEmailAddress = false;
					if (!$emailAddress) {
						$emailAddress = $emailAddressRepository->get();
						$emailAddress->set('name', $email);
						$emailAddressRepository->save($emailAddress);						
						$isNewEmailAddress = true;
					}
					
					$query = "
						UPDATE entity_email_address 
						SET `primary` = 0
						WHERE
							entity_id = ".$pdo->quote($entity->id)." AND
							entity_type = ".$pdo->quote($this->entityName)."						
					";
					$sth = $pdo->prepare($query);
					$sth->execute();
					
					$sth = null;
					if (!$isNewEmailAddress) {
						$query = "
							SELECT * FROM entity_email_address 
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								email_address_id = ".$pdo->quote($emailAddress->id)."							
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
					}
					if (!$isNewEmailAddress && $sth->fetch()) {						
						$query = "
							UPDATE entity_email_address 
							SET `primary` = 1
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								email_address_id = ".$pdo->quote($emailAddress->id)."							
						";
						$sth = $pdo->prepare($query);
						$sth->execute();						
					} else {
						$query = "
							INSERT INTO entity_email_address  
							(entity_id, entity_type, email_address_id, `primary`)
							VALUES
							(".$pdo->quote($entity->id).", ".$pdo->quote($this->entityName).", ".$pdo->quote($emailAddress->id).", 1)					
						";

						$sth = $pdo->prepare($query);
						$sth->execute();
					}					
				}
			} else {
				$fetched = $entity->getFetchedValue('emailAddress');
				if (!empty($fetched)) {
						$query = "
							DELETE FROM entity_email_address  
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								primary = 1				
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
				}
			}					
		}
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

