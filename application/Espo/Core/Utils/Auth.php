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

	protected $authentication;

	protected $config;

	protected $entityManager;

	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;

		$this->entityManager = $this->container->get('entityManager');
		$this->config = $this->container->get('config');

		$authenticationMethod = $this->config->get('authenticationMethod', 'Espo');
		$authenticationClassName = "\\Espo\\Core\\Utils\\Authentication\\" . $authenticationMethod;
		$this->authentication = new $authenticationClassName($this->config, $this->entityManager, $this);
	}

	public function useNoAuth($isAdmin = false)
	{
		$entityManager = $this->container->get('entityManager');

		$user = $entityManager->getRepository('User')->get('system');
		if (!$user) {
			throw new Error('System user is not found');
		}

		$user->set('isAdmin', $isAdmin);

		$entityManager->setUser($user);
		$this->container->setUser($user);
	}

	public function login($username, $password)
	{
		$GLOBALS['log']->debug('AUTH: Try to authenticate');

		$entityManager = $this->entityManager;

		$authToken = $entityManager->getRepository('AuthToken')->where(array('token' => $password))->findOne();

		$user = $this->authentication->login($username, $password, $authToken);

		if ($user) {
			$entityManager->setUser($user);
			$this->container->setUser($user);
			$GLOBALS['log']->debug('AUTH: Result of authenticate is [true]');

			if (!$authToken) {
				$authToken = $entityManager->getEntity('AuthToken');
				$token = $this->createToken($user);
				$authToken->set('token', $token);
				$authToken->set('hash', $user->get('password'));
				$authToken->set('ipAddress', $_SERVER['REMOTE_ADDR']);
				$authToken->set('userId', $user->id);
			}

			$authToken->set('lastAccess', date('Y-m-d H:i:s'));

			$entityManager->saveEntity($authToken);
			$user->set('token', $authToken->get('token'));

			return true;
		}
	}

	protected function createToken($user)
	{
		return md5(uniqid($user->get('id')));
	}

	public function destroyAuthToken($token)
	{
		$entityManager = $this->container->get('entityManager');

		$authToken = $entityManager->getRepository('AuthToken')->where(array('token' => $token))->findOne();
		if ($authToken) {
			$entityManager->removeEntity($authToken);
			return true;
		}
	}
}

