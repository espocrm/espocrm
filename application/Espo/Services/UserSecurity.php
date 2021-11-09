<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

use Espo\ORM\EntityManager;

use Espo\Entities\User;
use Espo\Entities\UserData;

use Espo\Repositories\UserData as UserDataRepository;

use Espo\Core\{
    Utils\Config,
    Authentication\LoginFactory,
    Authentication\TwoFactor\UserSetupFactory as TwoFactorUserSetupFactory,
    Authentication\Login\Data as LoginData,
    Api\RequestNull,
};

use stdClass;

class UserSecurity
{
    private $entityManager;

    private $user;

    private $config;

    private $authLoginFactory;

    private $twoFactorUserSetupFactory;

    public function __construct(
        EntityManager $entityManager,
        User $user,
        Config $config,
        LoginFactory $authLoginFactory,
        TwoFactorUserSetupFactory $twoFactorUserSetupFactory
    ) {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->config = $config;
        $this->authLoginFactory = $authLoginFactory;
        $this->twoFactorUserSetupFactory = $twoFactorUserSetupFactory;
    }

    public function read(string $id): stdClass
    {
        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntity('User', $id);

        if (!$user) {
            throw new NotFound();
        }

        if (!$user->isAdmin() && !$user->isRegular()) {
            throw new Forbidden();
        }

        $userData = $this->getUserDataRepository()->getByUserId($id);

        return (object) [
            'auth2FA' => $userData->get('auth2FA'),
            'auth2FAMethod' => $userData->get('auth2FAMethod'),
        ];
    }

    public function getTwoFactorUserSetupData(string $id, stdClass $data): stdClass
    {
        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        $isReset = $data->reset ?? false;

        /** @var ?User $user */
        $user = $this->entityManager->getEntity('User', $id);

        if (!$user) {
            throw new NotFound();
        }

        if (!$user->isAdmin() && !$user->isRegular()) {
            throw new Forbidden();
        }

        $password = $data->password ?? null;

        if (!$password) {
            throw new Forbidden('Passport required.');
        }

        if (!$this->user->isAdmin() || $this->user->getId() === $id) {
            $this->checkPassword($id, $password);
        }

        $auth2FAMethod = $data->auth2FAMethod ?? null;

        if (!$auth2FAMethod) {
            throw new BadRequest();
        }

        $clientData = $this->twoFactorUserSetupFactory
            ->create($auth2FAMethod)
            ->getData($user);

        if ($isReset) {
            $userData = $this->getUserDataRepository()->getByUserId($id);

            $userData->set('auth2FA', false);
            $userData->set('auth2FAMethod', null);

            $this->entityManager->saveEntity($userData);
        }

        return $clientData;
    }

    public function update(string $id, stdClass $data): stdClass
    {
        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntity('User', $id);

        if (!$user) {
            throw new NotFound();
        }

        if (!$user->isAdmin() && !$user->isRegular()) {
            throw new Forbidden();
        }

        $userData = $this->getUserDataRepository()->getByUserId($id);

        $password = $data->password ?? null;

        if (!$password) {
            throw new Forbidden('Password required.');
        }

        if (!$this->user->isAdmin() || $this->user->getId() === $id) {
            $this->checkPassword($id, $password);
        }

        if (property_exists($data, 'auth2FA')) {
            $userData->set('auth2FA', $data->auth2FA);
        }

        if (property_exists($data, 'auth2FAMethod')) {
            $userData->set('auth2FAMethod', $data->auth2FAMethod);
        }

        if (!$userData->get('auth2FA')) {
            $userData->set('auth2FAMethod', null);
        }

        if ($userData->get('auth2FA') && $userData->isAttributeChanged('auth2FA')) {
            if (!$this->config->get('auth2FA')) {
                throw new Forbidden('2FA is not enabled.');
            }
        }

        if (
            $userData->get('auth2FA') &&
            $userData->get('auth2FAMethod') &&
            ($userData->isAttributeChanged('auth2FA') || $userData->isAttributeChanged('auth2FAMethod'))
        ) {
            $auth2FAMethod = $userData->get('auth2FAMethod');

            if (!in_array($auth2FAMethod, $this->config->get('auth2FAMethodList', []))) {
                throw new Forbidden('Not allowed 2FA auth method.');
            }

            $verifyResult = $this->twoFactorUserSetupFactory
                ->create($auth2FAMethod)
                ->verifyData($user, $data);

            if (!$verifyResult) {
                throw new Forbidden('Not verified.');
            }
        }

        $this->entityManager->saveEntity($userData);

        $returnData = (object) [
            'auth2FA' => $userData->get('auth2FA'),
            'auth2FAMethod' => $userData->get('auth2FAMethod'),
        ];

        return $returnData;
    }

    private function checkPassword(string $id, string $password): void
    {
        $user = $this->entityManager
            ->getRDBRepository('User')
            ->where([
                'id' => $id,
            ])
            ->findOne();

        if (!$user) {
            throw new Forbidden('User is not found.');
        }

        $loginData = LoginData::createBuilder()
            ->setUsername($user->get('userName'))
            ->setPassword($password)
            ->build();

        $login = $this->authLoginFactory->createDefault();

        $result = $login->login($loginData, new RequestNull());

        if ($result->isFail()) {
            throw new Forbidden('Password is incorrect.');
        }
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }
}
