<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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

    /**
     * Create Espo user with data gets from external server
     *
     * @param  array $userData entity data
     *
     * @return \Espo\Entities\User
     */
    protected function createUser(array $data)
    {
        $GLOBALS['log']->info('Creating new user ...');

        $this->getAuth()->useNoAuth();

        $user = $this->getEntityManager()->getEntity('User');
        $user->set($data);

        $this->getEntityManager()->saveEntity($user);

        return $this->getEntityManager()->getEntity('User', $user->id);
    }

    /**
     * Login by authorization token
     *
     * @param  string $username
     * @param  \Espo\Entities\AuthToken $authToken
     *
     * @return \Espo\Entities\User | null
     */
    protected function loginByToken($username, \Espo\Entities\AuthToken $authToken = null)
    {
        if (!isset($authToken)) {
            return null;
        }

        $userId = $authToken->get('userId');
        $user = $this->getEntityManager()->getEntity('User', $userId);

        $tokenUsername = $user->get('userName');
        if (strtolower($username) != strtolower($tokenUsername)) {
            $GLOBALS['log']->alert('Unauthorized access attempt for user ['.$username.'] from IP ['.$_SERVER['REMOTE_ADDR'].']');
            return null;
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
            )
        ));

        return $user;
    }

    public function authDetails() {
        return array (
            'method' => 'form',
            'loginUrl' => null,
            'logoutUrl' => null,
        );
    }
}

