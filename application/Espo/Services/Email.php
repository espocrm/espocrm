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

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;

class Email extends Record
{

	protected function init()
	{
		$this->dependencies[] = 'mailSender';
		$this->dependencies[] = 'preferences';
	}

	protected function getMailSender()
	{
		return $this->injections['mailSender'];
	}

	protected function getPreferences()
	{
		return $this->injections['preferences'];
	}

	public function createEntity($data)
	{
		$entity = parent::createEntity($data);

		if ($entity && $entity->get('status') == 'Sending') {
			$emailSender = $this->getMailSender();

			if (strtolower($this->getUser()->get('emailAddress')) == strtolower($entity->get('from'))) {
				$smtpParams = $this->getPreferences()->getSmtpParams();
				if ($smtpParams) {
					$smtpParams['fromName'] = $this->getUser()->get('name');
					$emailSender->useSmtp($smtpParams);
				}
			} else {
				if (!$this->getConfig()->get('outboundEmailIsShared')) {
					throw new Error('Can not use system smtp. outboundEmailIsShared is false.');
				}
			}

			$emailSender->send($entity);

			$this->getEntityManager()->saveEntity($entity);
		}

		return $entity;
	}

	public function getEntity($id = null)
	{
		$entity = parent::getEntity($id);
		if (!empty($id)) {

			if ($entity->get('fromEmailAddressName')) {
				$entity->set('from', $entity->get('fromEmailAddressName'));
			}

			$entity->loadLinkMultipleField('toEmailAddresses');
			$entity->loadLinkMultipleField('ccEmailAddresses');
			$entity->loadLinkMultipleField('bccEmailAddresses');

			$names = $entity->get('toEmailAddressesNames');
			if (!empty($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('to', implode('; ', $arr));
			}

			$names = $entity->get('ccEmailAddressesNames');
			if (!empty($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('cc', implode('; ', $arr));
			}

			$names = $entity->get('bccEmailAddressesNames');
			if (!empty($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('bcc', implode('; ', $arr));
			}

		}
		return $entity;
	}
	
	public function findEntities($params)
	{
		$searchByEmailAddress = false;
		if (!empty($params['where']) && is_array($params['where'])) {
			foreach ($params['where'] as $i => $p) {
				if (!empty($p['field']) && $p['field'] == 'emailAddress') {
					$searchByEmailAddress = true;				
					$emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where(array(
						'lower' => strtolower($p['value'])
					))->findOne();
					unset($params['where'][$i]);					
					$emailAddressId = null;
					if ($emailAddress) {
						$emailAddressId = $emailAddress->id;
					}
				}
		
			}
		}
		
		$selectParams = $this->getSelectManager($this->entityName)->getSelectParams($params, true);
		
		if ($searchByEmailAddress) {
			if ($emailAddressId) {
				$pdo = $this->getEntityManager()->getPDO();
		
				$selectParams['customJoin'] = "
					LEFT JOIN email_email_address 
						ON 
						email_email_address.email_id = email.id AND 
						email_email_address.deleted = 0
				";
				$selectParams['customWhere'] = " 
					AND
					(
						email.from_email_address_id = ".$pdo->quote($emailAddressId)." OR 
						email_email_address.email_address_id = ".$pdo->quote($emailAddressId)."
					)
				";
			} else {
				$selectParams['customWhere'] = ' AND 0';
			}
		
		}		
		
		$collection = $this->getRepository()->find($selectParams);	
		
		foreach ($collection as $e) {
			$this->loadParentNameFields($e);
		}
		
    	return array(
    		'total' => $this->getRepository()->count($selectParams),
    		'collection' => $collection,
    	);
	}
}

