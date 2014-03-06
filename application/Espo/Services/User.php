<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;

class User extends Record
{	
	public function getEntity($id)
	{		
		$entity = parent::getEntity($id);
	    $entity->clear('password');	 
	    return $entity;	    
	}
	
	public function findEntities($params)
	{		
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
			$data['isAdmin'] = true;
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

