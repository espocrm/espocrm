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
				$smtpParams = array();				
				$smtpParams['server'] = $this->getPreferences()->get('smtpServer');
				if (!empty($smtpParams['server'])) {	
					$smtpParams['fromName'] = $this->getUser()->get('name');
					$smtpParams['port'] = $this->getPreferences()->get('smtpPort');
					$smtpParams['server'] = $this->getPreferences()->get('smtpServer');	
					$smtpParams['auth'] = $this->getPreferences()->get('smtpAuth');
					$smtpParams['security'] = $this->getPreferences()->get('smtpSecurity');
					$smtpParams['username'] = $this->getPreferences()->get('smtpUsername');
					$smtpParams['password'] = $this->getPreferences()->get('smtpPassword');
									
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
}

