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

namespace Espo\Repositories;

use Espo\ORM\Entity;

class EmailAddress extends \Espo\Core\ORM\Repositories\RDB
{
	public function getIds($arr = array())
	{		
		$ids = array();		
		if (!empty($arr)) {
			$a = array_map(function ($item) {
					return strtolower($item);
				}, $arr);
			$eas = $this->where(array(
				'lower' => array_map(function ($item) {
					return strtolower($item);
				}, $arr)
			))->find();
			$ids = array();
			$exist = array();
			foreach ($eas as $ea) {
				$ids[] = $ea->id;
				$exist[] = $ea->get('lower');
			}
			foreach ($arr as $address) {
				if (empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
					continue;
				}
				if (!in_array(strtolower($address), $exist)) {
					$ea = $this->get();
					$ea->set('name', $address);
					$this->save($ea);
					$ids[] = $ea->id;
				}
			}
		}
		return $ids;
	}
	
	public function getEmailAddressData(Entity $entity)
	{
		$data = array();
		
		$pdo = $this->getEntityManager()->getPDO();		
		$sql = "
			SELECT email_address.name, email_address.invalid, email_address.opt_out AS optOut, entity_email_address.primary 
			FROM entity_email_address
			JOIN email_address ON email_address.id = entity_email_address.email_address_id AND email_address.deleted = 0
			WHERE 
			entity_email_address.entity_id = ".$pdo->quote($entity->id)." AND 
			entity_email_address.entity_type = ".$pdo->quote($entity->getEntityName())." AND 
			entity_email_address.deleted = 0
			ORDER BY entity_email_address.primary DESC
		";
		$sth = $pdo->prepare($sql);
		$sth->execute();
		if ($rows = $sth->fetchAll()) {
			foreach ($rows as $row) {
				$obj = new \StdClass();
				$obj->emailAddress = $row['name'];
				$obj->primary = ($row['primary'] == '1') ? true : false;
				$obj->optOut = ($row['optOut'] == '1') ? true : false;
				$obj->invalid = ($row['invalid'] == '1') ? true : false;				
				$data[] = $obj;
			}
		}
		
		return $data;
	}
	
	public function getByAddress($address)
	{
		return $this->where(array('lower' => strtolower($address)))->findOne();
	}
	
	public function storeEntityEmailAddress(Entity $entity)
	{
			$email = trim($entity->get('emailAddress'));
			$emailAddressData = null;
			
			if ($entity->has('emailAddressData')) {
				$emailAddressData = $entity->get('emailAddressData');
			}
			
			$pdo = $this->getEntityManager()->getPDO();			
			
			if ($emailAddressData !== null && is_array($emailAddressData)) {
				$previousEmailAddressData = array();
				if (!$entity->isNew()) {
					$previousEmailAddressData = $this->getEmailAddressData($entity);
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
				
				foreach ($toRemove as $address) {
					$emailAddress = $this->getByAddress($address);
					if ($emailAddress) {
						$query = "
							UPDATE entity_email_address
							SET `deleted` = 1, `primary` = 0
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($entity->getEntityName())." AND
								email_address_id = ".$pdo->quote($emailAddress->id)."
						";
						$sth = $pdo->prepare($query);
						$sth->execute();	
					}
				}
				
				foreach ($toUpdate as $address) {
					$emailAddress = $this->getByAddress($address);
					if ($emailAddress) {
						$emailAddress->set(array(
							'optOut' => $hash[$address]['optOut'],
							'invalid' => $hash[$address]['invalid'],
						));
						$this->save($emailAddress);
					}
				}
				
				foreach ($toCreate as $address) {
					$emailAddress = $this->getByAddress($address);
					if (!$emailAddress) {
						$emailAddress = $this->get();
						
						$emailAddress->set(array(
							'name' => $address,
							'optOut' => $hash[$address]['optOut'],
							'invalid' => $hash[$address]['invalid'],
						));						
						$this->save($emailAddress);
					} else {
						if ($emailAddress->get('optOut') != $hash[$address]['optOut'] || $emailAddress->get('invalid') != $hash[$address]['invalid']) {
							$emailAddress->set(array(
								'optOut' => $hash[$address]['optOut'],
								'invalid' => $hash[$address]['invalid'],
							));
							$this->save($emailAddress);
						}
					}
					
					$query = "
						INSERT entity_email_address 
							(entity_id, entity_type, email_address_id, `primary`)
							VALUES
							(
								".$pdo->quote($entity->id).",
								".$pdo->quote($entity->getEntityName()).",
								".$pdo->quote($emailAddress->id).",
								".$pdo->quote($address === $primary)."
							)
					";
					$sth = $pdo->prepare($query);
					$sth->execute();
				}
				
				if ($primary) {
					$emailAddress = $this->getByAddress($primary);
					if ($emailAddress) {
						$query = "
							UPDATE entity_email_address
							SET `primary` = 0
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($entity->getEntityName())." AND
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
								entity_type = ".$pdo->quote($entity->getEntityName())." AND
								email_address_id = ".$pdo->quote($emailAddress->id)." AND 
								deleted = 0
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
					}
				}			
								
			
			} else {
				$entityRepository = $this->getEntityManager()->getRepository($entity->getEntityName());
				if (!empty($email)) {
					if ($email != $entity->getFetched('emailAddress')) {

						$emailAddressNew = $this->where(array('lower' => strtolower($email)))->findOne();
						$isNewEmailAddress = false;
						if (!$emailAddressNew) {
							$emailAddressNew = $this->get();
							$emailAddressNew->set('name', $email);
							$this->save($emailAddressNew);
							$isNewEmailAddress = true;
						}

						$emailOld = $entity->getFetched('emailAddress');
						if (!empty($emailOld)) {
							$emailAddressOld = $this->getByAddress($emailOld);
							$entityRepository->unrelate($entity, 'emailAddresses', $emailAddressOld);
						}
						$entityRepository->relate($entity, 'emailAddresses', $emailAddressNew);

						$query = "
							UPDATE entity_email_address
							SET `primary` = 1
							WHERE
								entity_id = ".$pdo->quote($entity->id)." AND
								entity_type = ".$pdo->quote($entity->getEntityName())." AND
								email_address_id = ".$pdo->quote($emailAddressNew->id)."
						";
						$sth = $pdo->prepare($query);
						$sth->execute();
					}
				} else {
					$emailOld = $entity->getFetched('emailAddress');
					if (!empty($emailOld)) {
						$emailAddressOld = $this->getByAddress($emailOld);
						$entityRepository->unrelate($entity, 'emailAddresses', $emailAddressOld);
					}
				}
			}
	}
}

