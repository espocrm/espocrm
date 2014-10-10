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

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;

use \Espo\ORM\Entity;

class User extends Record
{	
	protected function init()
	{
		$this->dependencies[] = 'mailSender';
		$this->dependencies[] = 'language';
	}
	
	protected $internalFields = array('password');
	
	protected function getMailSender()
	{
		return $this->injections['mailSender'];
	}
	
	protected function getLanguage()
	{
		return $this->injections['language'];
	}
	
	public function getEntity($id)
	{		
		if ($id == 'system') {
			throw new Forbidden();
		}
		
		$entity = parent::getEntity($id);
	    return $entity;	    
	}
	
	public function findEntities($params)
	{		
		if (empty($params['where'])) {
			$params['where'] = array();
		}
		$params['where'][] = array(
			'type' => 'notEquals',
			'field' => 'id',
			'value' => 'system'
		);
		
		$result = parent::findEntities($params);
	    return $result;	    
	}	
	
	public function changePassword($userId, $password)
	{
		$user = $this->getEntityManager()->getEntity('User', $userId);
		if (!$user) {
			throw new NotFound();
		}
		
		if (empty($password)) {
			throw new Error('Password can\'t be empty.');
		}
		
		$user->set('password', $this->hashPassword($password));
		
		$this->getEntityManager()->saveEntity($user);
		
		return true;
	}
	
	protected function hashPassword($password)
	{
		return md5($password);
	}
		
	public function createEntity($data)
	{
		$newPassword = null;		
		if (array_key_exists('password', $data)) {
			$newPassword = $data['password'];
			$data['password'] = $this->hashPassword($data['password']);
		}
		$user = parent::createEntity($data);		
		
		if (!is_null($newPassword)) {
			$this->sendPassword($user, $newPassword);
		}
			
		return $user;			
	}
	
	public function updateEntity($id, $data)
	{
		if ($id == 'system') {
			throw new Forbidden();
		}
		$newPassword = null;
		if (array_key_exists('password', $data)) {
			$newPassword = $data['password'];
			$data['password'] = $this->hashPassword($data['password']);
		}
		$user = parent::updateEntity($id, $data);
		
		if (!is_null($newPassword)) {
			try {
				$this->sendPassword($user, $newPassword);
			} catch (\Exception $e) {}
		}
		
		return $user;
	}
	
	protected function sendPassword(Entity $user, $password)
	{		
		$emailAddress = $user->get('emailAddress');
		
		if (empty($emailAddress)) {
			return;
		}
		
		$email = $this->getEntityManager()->getEntity('Email');
		
		if (!$this->getConfig()->get('smtpServer')) {
			return;
		}
		
		
		$subject = $this->getLanguage()->translate('accountInfoEmailSubject', 'messages', 'User');
		$body = $this->getLanguage()->translate('accountInfoEmailBody', 'messages', 'User');
		
		$body = str_replace('{userName}', $user->get('userName'), $body);
		$body = str_replace('{password}', $password, $body);
		$body = str_replace('{siteUrl}', $this->getConfig()->get('siteUrl'), $body);
		
		$email->set(array(
			'subject' => $subject,
			'body' => $body,
			'isHtml' => false,
			'to' => $emailAddress
		));
		
		$this->getMailSender()->send($email);
	}
	
	public function deleteEntity($id)
	{
		if ($id == 'system') {
			throw new Forbidden();
		}
		return parent::deleteEntity($id);	
	}	
}

