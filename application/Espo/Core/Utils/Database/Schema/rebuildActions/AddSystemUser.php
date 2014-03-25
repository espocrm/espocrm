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

namespace Espo\Core\Utils\Database\Schema\rebuildActions;

class AddSystemUser extends \Espo\Core\Utils\Database\Schema\BaseRebuildActions
{
	
	public function afterRebuild()
	{	 
		$userId = $this->getConfig()->get('systemUser.id');

		$entity = $this->getEntityManager()->getEntity('User', $userId);

		if (!isset($entity)) {

			$systemUser = $this->getConfig()->get('systemUser');

			$entity = $this->getEntityManager()->getEntity('User');
			$entity->set($systemUser);			

			return $this->getEntityManager()->saveEntity($entity);			
		}				
	}	
	
}

