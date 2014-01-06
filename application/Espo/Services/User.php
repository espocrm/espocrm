<?php

namespace Espo\Services;

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
		
	public function createEntity($data)
	{
		if (array_key_exists('password', $data)) {
			$data['password'] = md5($data['password']);
		}
		return parent::createEntity($data);		
	}
	
	public function updateEntity($id, $data)
	{
		if (array_key_exists('password', $data)) {
			$data['password'] = md5($data['password']);
		}
		return parent::updateEntity($id, $data);		
	}
}

