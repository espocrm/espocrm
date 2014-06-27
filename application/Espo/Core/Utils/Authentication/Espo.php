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

namespace Espo\Core\Utils\Authentication;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Utils\Config;
use \Espo\Core\ORM\EntityManager;

class Espo 
{
	protected $config;
	
	protected $entityManager;
	
	public function __construct(Config $config, EntityManager $entityManager)
	{
		$this->config = $config;
		$this->entityManager = $entityManager;
	}
	
	
	public function login($username, $password, \Espo\Entities\AuthToken $authToken = null)
	{
		if ($authToken) {
			$hash = $authToken->get('hash');
		} else {
			$hash = md5($password);
		}
		
		$user = $this->entityManager->getRepository('User')->findOne(array(
			'whereClause' => array(
				'userName' => $username,
				'password' => $hash
			),
		));
		
		return $user;
	}
}

