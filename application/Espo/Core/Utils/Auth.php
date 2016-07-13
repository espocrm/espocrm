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

use \Espo\Entities\Portal;

class Auth
{
    protected $container;

    protected $authentication;

    protected $allowAnyAccess;

    const ACCESS_CRM_ONLY = 0;

    const ACCESS_PORTAL_ONLY = 1;

    const ACCESS_ANY = 3;

    private $portal;

    public function __construct(\Espo\Core\Container $container, $allowAnyAccess = false)
    {
        $this->container = $container;

        $this->allowAnyAccess = $allowAnyAccess;

        $authenticationMethod = $this->getConfig()->get('authenticationMethod', 'Espo');
        $authenticationClassName = "\\Espo\\Core\\Utils\\Authentication\\" . $authenticationMethod;
        $this->authentication = new $authenticationClassName($this->getConfig(), $this->getEntityManager(), $this);

        $this->request = $container->get('slim')->request();
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function setPortal(Portal $portal)
    {
        $this->portal = $portal;
    }

    protected function isPortal()
    {
        if ($this->portal) {
            return true;
        }
        return !!$this->getContainer()->get('portal');
    }

    protected function getPortal()
    {
        if ($this->portal) {
            return $this->portal;
        }
        return $this->getContainer()->get('portal');
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    public function useNoAuth($isAdmin = false)
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $user = $entityManager->getRepository('User')->get('system');
        if (!$user) {
            throw new Error("System user is not found");
        }

        $user->set('isAdmin', $isAdmin);

        $entityManager->setUser($user);
        $this->getContainer()->setUser($user);
    }

    public function login($username, $password)
    {
        $authToken = $this->getEntityManager()->getRepository('AuthToken')->where(array('token' => $password))->findOne();

        if ($authToken) {
            if (!$this->allowAnyAccess) {
                if ($this->isPortal() && $authToken->get('portalId') !== $this->getPortal()->id) {
                    $GLOBALS['log']->debug("AUTH: Trying to login to portal with a token not related to portal.");
                    return false;
                }
                if (!$this->isPortal() && $authToken->get('portalId')) {
                    $GLOBALS['log']->debug("AUTH: Trying to login to crm with a token related to portal.");
                    return false;
                }
            }
            if ($this->allowAnyAccess) {
                if ($authToken->get('portalId') && !$this->isPortal()) {
                    $portal = $this->getEntityManager()->getEntity('Portal', $authToken->get('portalId'));
                    if ($portal) {
                        $this->setPortal($portal);
                    }
                }
            }
        }

        $user = $this->authentication->login($username, $password, $authToken);

        if ($user) {
            if (!$user->isActive()) {
                $GLOBALS['log']->debug("AUTH: Trying to login as user '".$user->get('userName')."' which is not active.");
                return false;
            }

            if (!$user->isAdmin() && !$this->isPortal() && $user->get('isPortalUser')) {
                $GLOBALS['log']->debug("AUTH: Trying to login to crm as a portal user '".$user->get('userName')."'.");
                return false;
            }

            if (!$user->isAdmin() && $this->isPortal() && !$user->get('isPortalUser')) {
                $GLOBALS['log']->debug("AUTH: Trying to login to portal as user '".$user->get('userName')."' which is not portal user.");
                return false;
            }

            if ($this->isPortal()) {
                if (!$user->isAdmin() && !$this->getEntityManager()->getRepository('Portal')->isRelated($this->getPortal(), 'users', $user)) {
                    $GLOBALS['log']->debug("AUTH: Trying to login to portal as user '".$user->get('userName')."' which is portal user but does not belongs to portal.");
                    return false;
                }
                $user->set('portalId', $this->getPortal()->id);
            } else {
                $user->loadLinkMultipleField('teams');
            }

            $this->getEntityManager()->setUser($user);
            $this->getContainer()->setUser($user);

            if ($this->request->headers->get('HTTP_ESPO_AUTHORIZATION')) {
	            if (!$authToken) {
	                $authToken = $this->getEntityManager()->getEntity('AuthToken');
	                $token = $this->createToken($user);
	                $authToken->set('token', $token);
	                $authToken->set('hash', $user->get('password'));
	                $authToken->set('ipAddress', $_SERVER['REMOTE_ADDR']);
	                $authToken->set('userId', $user->id);
                    if ($this->isPortal()) {
                        $authToken->set('portalId', $this->getPortal()->id);
                    }
	            }
            	$authToken->set('lastAccess', date('Y-m-d H:i:s'));

            	$this->getEntityManager()->saveEntity($authToken);
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
        $authToken = $this->getEntityManager()->getRepository('AuthToken')->where(array('token' => $token))->findOne();
        if ($authToken) {
            $this->getEntityManager()->removeEntity($authToken);
            return true;
        }
    }
}

