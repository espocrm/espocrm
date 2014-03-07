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

