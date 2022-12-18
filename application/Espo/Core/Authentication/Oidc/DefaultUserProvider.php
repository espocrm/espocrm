<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Authentication\Oidc;

use Espo\Core\Authentication\Jwt\Token\Payload;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use RuntimeException;

class DefaultUserProvider implements UserProvider
{
    public function __construct(
        private Config $config,
        private Sync $sync,
        private EntityManager $entityManager
    ) {}

    public function get(Payload $payload): ?User
    {
        $user = $this->findUser($payload);

        if ($user) {
            $this->syncUser($user, $payload);

            return $user;
        }

        return $this->tryToCreateUser($payload);
    }

    private function findUser(Payload $payload): ?User
    {
        $usernameClaim = $this->config->get('oidcUsernameClaim');

        if (!$usernameClaim) {
            throw new RuntimeException("No username claim in config.");
        }

        $username = $payload->get($usernameClaim);

        if (!$username) {
            throw new RuntimeException("No username claim `{$usernameClaim}` in token.");
        }

        $username = $this->sync->normalizeUsername($username);

        /** @var ?User $user */
        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where(['userName' => $username])
            ->findOne();

        if (!$user) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        if (!$user->isRegular() && !$user->isAdmin()) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        if ($user->isAdmin() && !$this->config->get('oidcAllowAdminUser')) {
            return null;
        }

        return $user;
    }

    private function tryToCreateUser(Payload $payload): ?User
    {
        if (!$this->config->get('oidcCreateUser')) {
            return null;
        }

        $usernameClaim = $this->config->get('oidcUsernameClaim');

        if (!$usernameClaim) {
            throw new RuntimeException("Could not create a user. No OIDC username claim in config.");
        }

        $username = $payload->get($usernameClaim);

        if (!$username) {
            throw new RuntimeException("Could not create a user. No username claim returned in token.");
        }

        return $this->sync->createUser($payload);
    }

    private function syncUser(User $user, Payload $payload): void
    {
        if (!$this->config->get('oidcSync') && !$this->config->get('oidcSyncTeams')) {
            return;
        }

        $this->sync->syncUser($user, $payload);
    }
}
