<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

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

        $this->request = $this->container->get('slim')->request();
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
        $entityManager = $this->entityManager;

        $authToken = $entityManager->getRepository('AuthToken')->where(array('token' => $password))->findOne();

        $user = $this->authentication->login($username, $password, $authToken);

        if ($user) {
            if (!$user->isActive()) {
                $GLOBALS['log']->debug("AUTH: Trying to login as user '".$user->get('userName')."' which is not active.");
                return false;
            }
            $entityManager->setUser($user);
            $this->container->setUser($user);

            if ($this->request->headers->get('HTTP_ESPO_AUTHORIZATION')) {
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
            }

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

