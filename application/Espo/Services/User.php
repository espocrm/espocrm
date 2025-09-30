<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Services;

use Espo\Core\Di\LogAware;
use Espo\Core\Di\LogSetter;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\User as UserEntity;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\PasswordHash;
use Espo\ORM\Entity;
use Espo\Tools\UserSecurity\Password\Checker as PasswordChecker;
use Espo\Tools\UserSecurity\Password\Generator as PasswordGenerator;
use Espo\Tools\UserSecurity\Password\Sender as PasswordSender;
use Espo\Tools\UserSecurity\Password\Service as PasswordService;
use SensitiveParameter;
use stdClass;
use Exception;

/**
 * @extends Record<UserEntity>
 */
class User extends Record implements LogAware
{
    use LogSetter;

    private function hashPassword(#[SensitiveParameter] string $password): string
    {
        $passwordHash = $this->injectableFactory->create(PasswordHash::class);

        return $passwordHash->hash($password);
    }

    /**
     * @throws BadRequest
     */
    private function fetchPassword(#[SensitiveParameter] stdClass $data): ?string
    {
        $password = $data->password ?? null;

        if ($password === '') {
            $password = null;
        }

        if ($password !== null && !is_string($password)) {
            throw new BadRequest("Bad password value.");
        }

        return $password;
    }

    public function create(stdClass $data, CreateParams $params): Entity
    {
        $newPassword = $this->fetchPassword($data);

        $passwordSpecified = $newPassword !== null;

        if (
            $newPassword !== null &&
            !$this->createPasswordChecker()->checkStrength($newPassword)
        ) {
            throw new Forbidden("Password is weak.");
        }

        if (!$newPassword) {
            // Generate a password as authentication implementations may require user records
            // to have passwords for auth token mechanism functioning.
            $newPassword = $this->createPasswordGenerator()->generate();
        }

        $data->password = $this->hashPassword($newPassword);

        /** @var UserEntity $user */
        $user = parent::create($data, $params);

        $sendAccessInfo = !empty($data->sendAccessInfo);

        if (!$sendAccessInfo || !$user->isActive() || $user->isApi()) {
            return $user;
        }

        try {
            if ($passwordSpecified) {
                $this->sendPassword($user, $newPassword);

                return $user;
            }

            $this->getPasswordService()->sendAccessInfoForNewUser($user);
        } catch (Exception $e) {
            $this->log->error("Could not send user access info. " . $e->getMessage());
        }

        return $user;
    }

    public function update(string $id, stdClass $data, UpdateParams $params): Entity
    {
        $newPassword = null;

        if (property_exists($data, 'password')) {
            $newPassword = $data->password;

            if (!$this->createPasswordChecker()->checkStrength($newPassword)) {
                throw new Forbidden("Password is weak.");
            }

            $data->password = $this->hashPassword($data->password);
        }

        if ($id === $this->user->getId()) {
            unset($data->isActive);
            unset($data->isPortalUser);
            unset($data->type);
        }

        /** @var UserEntity $user */
        $user = parent::update($id, $data, $params);

        if (!is_null($newPassword)) {
            try {
                if ($user->isActive() && !empty($data->sendAccessInfo)) {
                    $this->sendPassword($user, $newPassword);
                }
            } catch (Exception) {}
        }

        return $user;
    }

    private function getPasswordService(): PasswordService
    {
        return $this->injectableFactory->create(PasswordService::class);
    }

    /**
     * @throws SendingError
     */
    private function sendPassword(UserEntity $user, string $password): void
    {
        $this->injectableFactory
            ->create(PasswordSender::class)
            ->sendPassword($user, $password);
    }

    private function createPasswordChecker(): PasswordChecker
    {
        return $this->injectableFactory->create(PasswordChecker::class);
    }

    private function createPasswordGenerator(): PasswordGenerator
    {
        return $this->injectableFactory->create(PasswordGenerator::class);
    }
}
