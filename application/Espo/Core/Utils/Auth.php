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

namespace Espo\Core\Utils;

use \Espo\Core\Exceptions\Error;

class Auth 
{
	protected $container;
	
	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}
	
	public function useNoAuth()
	{
		$entityManager = $this->container->get('entityManager');		
		
		$user = $entityManager->getRepository('User')->get('system');		
		if (!$user) {
			throw new Error('System user is not found');			
		}

		$entityManager->setUser($user);
		$this->container->setUser($user);
	}
	
	public function login($username, $password)
	{
		$GLOBALS['log']->debug('AUTH: Try to authenticate');
		
		$entityManager = $this->container->get('entityManager');
		
		$user = $entityManager->getRepository('User')->findOne(array(
			'whereClause' => array(
				'userName' => $username,
				'password' => md5($password)
			),
		));
		
		if ($user instanceof \Espo\Entities\User) {
			$entityManager->setUser($user);
			$this->container->setUser($user);
			$GLOBALS['log']->debug('AUTH: Result of authenticate is [true]');
			return true;
		}
	}
}

