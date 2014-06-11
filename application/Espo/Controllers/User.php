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
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;

class User extends \Espo\Core\Controllers\Record
{	
	public function actionAcl($params, $data, $request)
	{		
		$userId = $request->get('id');
		if (empty($userId)) {
			throw new Error();
		}
		
		if (!$this->getUser()->isAdmin() && $this->getUser()->id != $userId) {
			throw new Forbidden();
		}
		
		$user = $this->getEntityManager()->getEntity('User', $userId);
		if (empty($user)) {
			throw new NotFound();
		}
		
		$acl = new \Espo\Core\Acl($user);
		
		return $acl->toArray();					
	}
	
	public function actionChangeOwnPassword($params, $data)
	{
		return $this->getService('User')->changePassword($this->getUser()->id, $data['password']);
	}
}

