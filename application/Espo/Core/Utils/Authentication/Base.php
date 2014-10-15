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

use \Espo\Core\Utils\Config;
use \Espo\Core\ORM\EntityManager;
use \Espo\Core\Utils\Auth;

abstract class Base
{
	private $config;

	private $entityManager;

	private $auth;

	private $passwordHash;

	public function __construct(Config $config, EntityManager $entityManager, Auth $auth)
	{
		$this->config = $config;
		$this->entityManager = $entityManager;
		$this->auth = $auth;
	}

	protected function getConfig()
	{
		return $this->config;
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getAuth()
	{
		return $this->auth;
	}

	protected function getPasswordHash()
	{
		if (!isset($this->passwordHash)) {
			$this->passwordHash = new \Espo\Core\Utils\PasswordHash($this->config);
		}

		return $this->passwordHash;
	}

}

