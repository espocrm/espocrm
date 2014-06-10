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

class User extends Record
{	
	protected function init()
	{
		$this->dependencies[] = 'mailSender';
		$this->dependencies[] = 'language';
	}
	
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
	    $entity->clear('password');	 
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
	    foreach ($result['collection'] as $entity) {
	    	$entity->clear('password');
	    }
	    return $result;	    
	}
	
	protected function createDefaultPreferences(\Espo\Entities\User $user)
	{
		$preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);		
		$config = $this->getConfig();
		$defaults = array(
			'timeZone' => $config->get('timeZone'),
			'language' => $config->get('language'),
			'dateFormat' => $config->get('dateFormat'),
			'timeFormat' => $config->get('timeFormat'),
			'weekStart' => $config->get('weekStart'),
			'thousandSeparator' => $config->get('thousandSeparator'),
			'decimalMark' => $config->get('decimalMark'),
		);
		$preferences->set($defaults);		
		$this->getEntityManager()->saveEntity($preferences);
	}
		
	public function createEntity($data)
	{
		$newPassword = null;		
		if (array_key_exists('password', $data)) {
			$newPassword = $data['password'];
			$data['password'] = md5($data['password']);
		}
		$user = parent::createEntity($data);		
		$this->createDefaultPreferences($user);
		
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
			$data['password'] = md5($data['password']);
		}
		$user = parent::updateEntity($id, $data);
		
		if (!is_null($newPassword)) {
			$this->sendPassword($user, $newPassword);
		}
		
		return $user;
	}
	
	protected function sendPassword(Entity $user, $password)
	{
		// TODO use cron job
		
		$emailAddress = $user->get('emailAddress');
		
		if (empty($emailAddress)) {
			return;
		}
		
		$email = $this->getEntityManager()->getEntity('Email');
		
		
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

