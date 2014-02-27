<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class Preferences extends \Espo\Core\Controllers\Base
{	
	protected function getPreferences()
	{
		return $this->getContainer()->get('preferences');
	}
	
	protected function getEntityManager()
	{
		return $this->getContainer()->get('entityManager');
	}
	
	protected function handleUserAccess($userId)
	{
		if (!$this->getUser()->isAdmin()) {
			if ($this->getUser()->id != $userId) {
				throw new Forbidden();
			}
		}
	}
	
	public function actionPatch($params, $data)
	{
		return $this->actionUpdate($params, $data);
	}	

	public function actionUpdate($params, $data)
	{
		$userId = $params['id'];
		$this->handleUserAccess($userId);		

		$entity = $this->getEntityManager()->getEntity('Preferences', $userId);
		if ($entity) {
			$entity->set($data);
			$this->getEntityManager()->saveEntity($entity);
			return $entity->toArray();		
		}
		throw new Error();
	}

    public function actionRead($params)
	{
		$userId = $params['id'];
		$this->handleUserAccess($userId);

		$entity = $this->getEntityManager()->getEntity('Preferences', $userId);		
		$user = $this->getEntityManager()->getEntity('User', $userId);
		
		$entity->set('name', $user->get('name'));
		if ($entity) {
			return $entity->toArray();		
		}
		throw new NotFound();
	}

}

