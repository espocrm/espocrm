<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

namespace Espo\Core\ORM\Repositories;

use \Espo\ORM\EntityManager;
use \Espo\ORM\EntityFactory;
use \Espo\ORM\Entity;
use \Espo\ORM\IEntity;

use \Espo\Core\Interfaces\Injectable;

class RDB extends \Espo\ORM\Repositories\RDB implements Injectable
{
	public static $mapperClassName = '\\Espo\\Core\\ORM\\DB\\MysqlMapper';

	protected $dependencies = array(
		'metadata'
	);

	protected $injections = array();

	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}

	protected function getInjection($name)
	{
		return $this->injections[$name];
	}

	public function getDependencyList()
	{
		return $this->dependencies;
	}

	protected function getMetadata()
	{
		return $this->getInjection('metadata');
	}

	protected function handleSelectParams(&$params, $entityName = false)
	{
		$this->handleEmailAddressParams($params, $entityName);
	}

	protected function handleEmailAddressParams(&$params, $entityName = false)
	{
		if (empty($entityName)) {
			$entityName = $this->entityName;
		}

		$defs = $this->getEntityManager()->getMetadata()->get($entityName);
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
			$params['leftJoins'] = array('emailAddresses');
			$params['joinConditions'] = array(
				'emailAddresses' => array(
					'primary' => 1
				)
			);
		}
	}

	protected function beforeRemove(Entity $entity)
	{
		parent::beforeRemove($entity);
		$this->getEntityManager()->getHookManager()->process($this->entityName, 'beforeRemove', $entity);
	}

	protected function afterRemove(Entity $entity)
	{
		parent::afterRemove($entity);
		$this->getEntityManager()->getHookManager()->process($this->entityName, 'afterRemove', $entity);
	}

	public function remove(Entity $entity)
	{
		$this->getEntityManager()->getHookManager()->process($this->entityName, 'beforeRemove', $entity);

		$result = parent::remove($entity);
		if ($result) {
			$this->getEntityManager()->getHookManager()->process($this->entityName, 'afterRemove', $entity);
		}
		return $result;
	}

	protected function beforeSave(Entity $entity)
	{
		parent::beforeSave($entity);
		$this->getEntityManager()->getHookManager()->process($this->entityName, 'beforeSave', $entity);
	}

	protected function afterSave(Entity $entity)
	{
		parent::afterSave($entity);
		$this->getEntityManager()->getHookManager()->process($this->entityName, 'afterSave', $entity);
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

	// TODO move this logic somewhere out
	protected function handleEmailAddressSave(Entity $entity)
	{
		if ($entity->hasRelation('emailAddresses') && $entity->hasField('emailAddress')) {
			$email = trim($entity->get('emailAddress'));
			$emailAddressData = null;
			
			if ($entity->has('emailAddressData')) {
				$emailAddressData = $entity->get('emailAddressData');
			}
			
			$pdo = $this->getPDO();

			$emailAddressRepository = $this->getEntityManager()->getRepository('EmailAddress');
			
			if ($emailAddressData !== null && is_array($emailAddressData)) {
				$previousEmailAddressData = array();
				if (!$entity->isNew()) {
					$previousEmailAddressData = $emailAddressRepository->getEmailAddressData($entity);
				}
				
				$hash = array();
				foreach ($emailAddressData as $row) {
					$key = $row->emailAddress;
					if (!empty($key)) {
						$hash[$key] = array(
							'primary' => $row->primary ? true : false,
							'optOut' => $row->optOut ? true : false,
							'invalid' => $row->invalid ? true : false,							
						);
					}
				}
								
				$hashPrev = array();
				foreach ($previousEmailAddressData as $row) {
					$key = $row->emailAddress;
					if (!empty($key)) {
						$hashPrev[$key] = array(
							'primary' => $row->primary ? true : false,
							'optOut' => $row->optOut ? true : false,
							'invalid' => $row->invalid ? true : false,							
						);
					}
				}				
				
				$primary = false;				
				$toCreate = array();
				$toUpdate = array();				
				$toRemove = array();

				
				foreach ($hash as $key => $data) {
					$new = true;
					$changed = false;
					
					if ($hash[$key]['primary']) {
						$primary = $key;
					}
					
					if (array_key_exists($key, $hashPrev)) {
						$new = false;
						$changed = $hash[$key]['optOut'] != $hashPrev[$key]['optOut'] || $hash[$key]['invalid'] != $hashPrev[$key]['invalid'];						
						if ($hash[$key]['primary']) {
							if ($hash[$key]['primary'] == $hashPrev[$key]['primary']) {
								$primary = false;
							}
						}						
					}
					
					if ($new) {
						$toCreate[] = $key;
					}					
					if ($changed) {
						$toUpdate[] = $key;
					}					 
				}
				
				foreach ($hashPrev as $key => $data) {				
					if (!array_key_exists($key, $hash)) {
						$toRemove[] = $key;
					}
				}
				
				
				/*echo $primary;
				print_r($toCreate);
				print_r($toUpdate);
				print_r($toRemove);
				
				die;*/
				
				foreach ($toRemove as $address) {
					$emailAddress = $emailAddressRepository->getByAddress($address);
					if ($emailAddress) {
						$query = "
							UPDATE entity_email_address
							SET `deleted` = 1, `primary` = 0
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								email_address_id = ".$pdo->quote($emailAddress->id)."
						";
						$sth = $pdo->prepare($query);
						$sth->execute();	
					}
				}
				
				foreach ($toUpdate as $address) {
					$emailAddress = $emailAddressRepository->getByAddress($address);
					if ($emailAddress) {
						$emailAddress->set(array(
							'optOut' => $hash[$address]['optOut'],
							'invalid' => $hash[$address]['invalid'],
						));
						$emailAddressRepository->save($emailAddress);
					}
				}
				
				foreach ($toCreate as $address) {
					$emailAddress = $emailAddressRepository->getByAddress($address);
					if (!$emailAddress) {
						$emailAddress = $emailAddressRepository->get();
						
						$emailAddress->set(array(
							'name' => $address,
							'optOut' => $hash[$address]['optOut'],
							'invalid' => $hash[$address]['invalid'],
						));						
						$emailAddressRepository->save($emailAddress);
					} else {
						if ($emailAddress->get('optOut') != $hash[$address]['optOut'] || $emailAddress->get('invalid') != $hash[$address]['invalid']) {
							$emailAddress->set(array(
								'optOut' => $hash[$address]['optOut'],
								'invalid' => $hash[$address]['invalid'],
							));
							$emailAddressRepository->save($emailAddress);
						}
					}
					
					$query = "
						INSERT entity_email_address 
							(entity_id, entity_type, email_address_id, `primary`)
							VALUES
							(
								".$pdo->quote($entity->id).",
								".$pdo->quote($this->entityName).",
								".$pdo->quote($emailAddress->id).",
								".$pdo->quote($address === $primary)."
							)
					";
					$sth = $pdo->prepare($query);
					$sth->execute();
				}
				
				if ($primary) {
					$emailAddress = $emailAddressRepository->getByAddress($primary);
					if ($emailAddress) {
						$query = "
							UPDATE entity_email_address
							SET `primary` = 0
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								`primary` = 1 AND 
								deleted = 0
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
						
						$query = "
							UPDATE entity_email_address
							SET `primary` = 1
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								email_address_id = ".$pdo->quote($emailAddress->id)." AND 
								deleted = 0
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
					}
				}			
								
			
			} else {
				if (!empty($email)) {
					if ($email != $entity->getFetched('emailAddress')) {

						$emailAddressNew = $emailAddressRepository->where(array('lower' => strtolower($email)))->findOne();
						$isNewEmailAddress = false;
						if (!$emailAddressNew) {
							$emailAddressNew = $emailAddressRepository->get();
							$emailAddressNew->set('name', $email);
							$emailAddressRepository->save($emailAddressNew);
							$isNewEmailAddress = true;
						}

						$emailOld = $entity->getFetched('emailAddress');
						if (!empty($emailOld)) {
							$emailAddressOld = $emailAddressRepository->where(array('lower' => strtolower($emailOld)))->findOne();
							$this->unrelate($entity, 'emailAddresses', $emailAddressOld);
						}
						$this->relate($entity, 'emailAddresses', $emailAddressNew);

						$query = "
							UPDATE entity_email_address
							SET `primary` = 1
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($this->entityName)." AND
								email_address_id = ".$pdo->quote($emailAddressNew->id)."
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
					}
				} else {
					$emailOld = $entity->getFetched('emailAddress');
					if (!empty($emailOld)) {
						$emailAddressOld = $emailAddressRepository->where(array('lower' => strtolower($emailOld)))->findOne();
						$this->unrelate($entity, 'emailAddresses', $emailAddressOld);
					}
				}
			}
		}
	}

	protected function handleSpecifiedRelations(Entity $entity)
	{
		$relationTypes = array($entity::HAS_MANY, $entity::MANY_MANY, $entity::HAS_CHILDREN);
		foreach ($entity->getRelations() as $name => $defs) {
			if (in_array($defs['type'], $relationTypes)) {
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

