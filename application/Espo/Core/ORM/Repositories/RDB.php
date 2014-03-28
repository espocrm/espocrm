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

	protected function handleEmailAddressSave(Entity $entity)
	{
		if ($entity->hasRelation('emailAddresses') && $entity->hasField('emailAddress')) {
			$email = $entity->get('emailAddress');
			$pdo = $this->getPDO();

			$emailAddressRepository = $this->getEntityManager()->getRepository('EmailAddress');

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

