<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\UserSecurity;

use Espo\Core\Authentication\TwoFactor\Exceptions\NotConfigured;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\UserData;
use Espo\Repositories\UserData as UserDataRepository;
use Espo\Core\Api\RequestNull;
use Espo\Core\Authentication\Login\Data as LoginData;
use Espo\Core\Authentication\LoginFactory;
use Espo\Core\Authentication\TwoFactor\UserSetupFactory as TwoFactorUserSetupFactory;
use Espo\Core\Utils\Config;

use stdClass;

class Service
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Config $config,
        private LoginFactory $authLoginFactory,
        private TwoFactorUserSetupFactory $twoFactorUserSetupFactory,
        private Log $log
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function read(string $id): stdClass
    {
        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $id);

        if (!$user) {
            throw new NotFound();
        }

        $allow =
            $user->isAdmin() ||
            $user->isRegular() ||
            $user->isPortal() && $this->config->get('auth2FAInPortal');

        if (!$allow) {
            throw new Forbidden();
        }

        $userData = $this->getUserDataRepository()->getByUserId($id);

        if (!$userData) {
            throw new NotFound();
        }

        return (object) [
            'auth2FA' => $userData->get('auth2FA'),
            'auth2FAMethod' => $userData->get('auth2FAMethod'),
        ];
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getTwoFactorUserSetupData(string $id, stdClass $data): stdClass
    {
        if (
            !$this->user->isAdmin() &&
            $id !== $this->user->getId()
        ) {
            throw new Forbidden();
        }

        $isReset = $data->reset ?? false;

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $id);

        if (!$user) {
            throw new NotFound();
        }

        $allow =
            $this->config->get('auth2FA') &&
            (
                $user->isAdmin() ||
                $user->isRegular() ||
                $user->isPortal() && $this->config->get('auth2FAInPortal')
            );

        if (!$allow) {
            throw new Forbidden();
        }

        $password = $data->password ?? null;

        if (!$password) {
            throw new Forbidden('Passport required.');
        }

        if (!$this->user->isAdmin()) {
            $this->checkPassword($id, $password);
        }

        if ($this->user->isAdmin()) {
            $this->checkPassword($this->user->getId(), $password);
        }

        $auth2FAMethod = $data->auth2FAMethod ?? null;

        if (!$auth2FAMethod) {
            throw new BadRequest();
        }

        try {
            $clientData = $this->twoFactorUserSetupFactory
                ->create($auth2FAMethod)
                ->getData($user);
        } catch (NotConfigured $e) {
            $this->log->error($e->getMessage());

            throw Forbidden::createWithBody(
                "2FA method '$auth2FAMethod' is not fully configured.",
                Body::create()->withMessageTranslation('2faMethodNotConfigured', 'User')
            );
        }

        if ($isReset) {
            $userData = $this->getUserDataRepository()->getByUserId($id);

            if (!$userData) {
                throw new NotFound();
            }

            $userData->set('auth2FA', false);
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $userData->set('auth2FAMethod', null);

            $this->entityManager->saveEntity($userData);
        }

        return $clientData;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws BadRequest
     */
    public function update(string $id, stdClass $data): stdClass
    {
        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $id);

        if (!$user) {
            throw new NotFound();
        }

        $allow =
            $user->isAdmin() ||
            $user->isRegular() ||
            $user->isPortal() && $this->config->get('auth2FAInPortal');

        if (!$allow) {
            throw new Forbidden();
        }

        $userData = $this->getUserDataRepository()->getByUserId($id);

        if (!$userData) {
            throw new NotFound();
        }

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
            /** @noinspection PhpRedundantOptionalArgumentInspection */
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
            ($userData->isAttributeChanged('auth2FA') || $userData->isAttributeChanged('auth2FAMethod')) &&
            (
                !$user->isPortal() ||
                $this->config->get('auth2FAInPortal')
            )
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

        return (object) [
            'auth2FA' => $userData->get('auth2FA'),
            'auth2FAMethod' => $userData->get('auth2FAMethod'),
        ];
    }

    /**
     * @throws Forbidden
     */
    private function checkPassword(string $id, string $password): void
    {
        $user = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
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
