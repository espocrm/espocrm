<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Services;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;

class UserSecurity extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('user');
        $this->addDependency('metadata');
        $this->addDependency('config');
        $this->addDependency('authenticationFactory');
        $this->addDependency('auth2FAUserFactory');
    }

    protected function getUser()
    {
        return $this->getInjection('user');
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    public function read(string $id) : object
    {
        if (!$this->getUser()->isAdmin() && $id !== $this->getUser()->id) throw new Forbidden();

        $user = $this->getEntityManager()->getEntity('User', $id);
        if (!$user) throw new NotFound();

        if (!$user->isAdmin() && !$user->isRegular()) throw new Forbidden();

        $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($id);

        return (object) [
            'auth2FA' => $userData->get('auth2FA'),
            'auth2FAMethod' => $userData->get('auth2FAMethod'),
        ];
    }

    public function generate2FAData(string $id, object $data) : object
    {
        if (!$this->getUser()->isAdmin() && $id !== $this->getUser()->id) throw new Forbidden();

        $user = $this->getEntityManager()->getEntity('User', $id);
        if (!$user) throw new NotFound();

        if (!$user->isAdmin() && !$user->isRegular()) throw new Forbidden();

        $password = $data->password ?? null;
        if (!$password) throw new Forbidden('Passport required.');

        if (!$this->getUser()->isAdmin() || $this->getUser()->id === $id) {
            $this->checkPassword($id, $password);
        }

        $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($id);

        $auth2FAMethod = $data->auth2FAMethod ?? null;
        if (!$auth2FAMethod) throw new BadRequest();

        $user = $this->getEntityManager()->getEntity('User', $userData->get('userId'));
        if (!$user) throw new Error("User not found.");

        $impl = $this->getInjection('auth2FAUserFactory')->create($auth2FAMethod);
        $generatedData = $impl->generateData($userData, $data, $user->get('userName'));

        $userData->set($generatedData);

        if (!empty($data->reset)) {
            $userData->set('auth2FA', false);
            $userData->set('auth2FAMethod', null);
        }

        $this->getEntityManager()->saveEntity($userData);

        return $generatedData;
    }

    public function update(string $id, object $data) : object
    {
        if (!$this->getUser()->isAdmin() && $id !== $this->getUser()->id) throw new Forbidden();

        $user = $this->getEntityManager()->getEntity('User', $id);
        if (!$user) throw new NotFound();

        if (!$user->isAdmin() && !$user->isRegular()) throw new Forbidden();

        $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($id);

        $originalData = clone $data;

        $password = $originalData->password ?? null;
        if (!$password) throw new Forbidden('Passport required.');

        if (!$this->getUser()->isAdmin() || $this->getUser()->id === $id) {
            $this->checkPassword($id, $password);
        }

        foreach (get_object_vars($data) as $attribute => $v) {
            if (!in_array($attribute, ['auth2FA', 'auth2FAMethod'])) {
                unset($data->$attribute);
            }
        }

        $userData->set($data);

        if (!$userData->get('auth2FA')) {
            $userData->set('auth2FAMethod', null);
        }

        if ($userData->get('auth2FA') && $userData->isAttributeChanged('auth2FA')) {
            if (!$this->getInjection('config')->get('auth2FA')) {
                throw new Forbidden('2FA is not enabled.');
            }
        }

        if (
            $userData->get('auth2FA') &&
            $userData->get('auth2FAMethod') &&
            ($userData->isAttributeChanged('auth2FA') || $userData->isAttributeChanged('auth2FAMethod'))
        ) {
            $auth2FAMethod = $userData->get('auth2FAMethod');

            if (!in_array($auth2FAMethod, $this->getInjection('config')->get('auth2FAMethodList', []))) {
                throw new Forbidden('Not allowed 2FA auth method.');
            }

            $code = $originalData->code ?? null;

            if (!$code) {
                throw new Forbidden('Not verified.');
            }

            $verifyResult = $this->getInjection('auth2FAUserFactory')->create($auth2FAMethod)->verify($userData, $code);

            if (!$verifyResult) {
                throw new Forbidden('Not verified.');
            }
        }

        $this->getEntityManager()->saveEntity($userData);

        $returnData = (object) [
            'auth2FA' => $userData->get('auth2FA'),
            'auth2FAMethod' => $userData->get('auth2FAMethod'),
        ];

        return $returnData;
    }

    protected function checkPassword(string $id, string $password)
    {
        $method = $this->getConfig()->get('authenticationMethod', 'Espo');

        $auth = $this->getInjection('authenticationFactory')->create($method);

        $user = $this->getEntityManager()->getRepository('User')->where([
            'id' => $id,
        ])->findOne();

        if (!$user) throw new Forbidden('User is not found.');

        if (!$auth->login($user->get('userName'), $password)) {
            throw new Forbidden('Password is incorrect.');
        }
        return true;
    }
}
