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
		if (array_key_exists('password', $data)) {
			$data['password'] = md5($data['password']);
		}
		$user = parent::createEntity($data);		
		$this->createDefaultPreferences($user);		
		return $user;			
	}
	
	public function updateEntity($id, $data)
	{
		if ($id == 'system') {
			throw new Forbidden();
		}
		if (array_key_exists('password', $data)) {
			$data['password'] = md5($data['password']);
		}
		return parent::updateEntity($id, $data);		
	}
	
	public function deleteEntity($id)
	{
		if ($id == 'system') {
			throw new Forbidden();
		}
		return parent::deleteEntity($id);	
	}	
}

