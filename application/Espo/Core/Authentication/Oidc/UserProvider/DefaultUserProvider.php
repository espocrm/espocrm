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

namespace Espo\Core\Authentication\Oidc\UserProvider;

use Espo\Core\ApplicationState;
use Espo\Core\Authentication\Oidc\ConfigDataProvider;
use Espo\Core\Authentication\Oidc\UserProvider;
use Espo\Core\Utils\Log;
use Espo\Entities\User;

use RuntimeException;

class DefaultUserProvider implements UserProvider
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private Sync $sync,
        private UserRepository $userRepository,
        private ApplicationState $applicationState,
        private Log $log,
    ) {}

    public function get(UserInfo $userInfo): ?User
    {
        $user = $this->findUser($userInfo);

        if ($user === false) {
            return null;
        }

        if ($user) {
            $this->syncUser($user, $userInfo);

            return $user;
        }

        return $this->tryToCreateUser($userInfo);
    }

    /**
     * @return User|false|null
     */
    private function findUser(UserInfo $userInfo): User|bool|null
    {
        $usernameClaim = $this->configDataProvider->getUsernameClaim();

        if (!$usernameClaim) {
            throw new RuntimeException("No username claim in config.");
        }

        $username = $userInfo->get($usernameClaim);

        if (!$username) {
            throw new RuntimeException("No username claim `$usernameClaim` in token and userinfo.");
        }

        $username = $this->sync->normalizeUsername($username);

        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            return null;
        }

        $userId = $user->getId();

        if (!$user->isActive()) {
            $this->log->info("Oidc: User $userId found but it's not active.");

            return false;
        }

        $isPortal = $this->applicationState->isPortal();

        if (!$isPortal && !$user->isRegular() && !$user->isAdmin()) {
            $this->log->info("Oidc: User $userId found but it's neither regular user nor admin.");

            return false;
        }

        if ($isPortal && !$user->isPortal()) {
            $this->log->info("Oidc: User $userId found but it's not portal user.");

            return false;
        }

        if ($isPortal) {
            $portalId = $this->applicationState->getPortalId();

            if (!$user->getPortals()->hasId($portalId)) {
                $this->log->info("Oidc: User $userId found but it's not related to current portal.");

                return false;
            }
        }

        if ($user->isSuperAdmin()) {
            $this->log->info("Oidc: User $userId found but it's super-admin, not allowed.");

            return false;
        }

        if ($user->isAdmin() && !$this->configDataProvider->allowAdminUser()) {
            $this->log->info("Oidc: User $userId found but it's admin, not allowed.");

            return false;
        }

        return $user;
    }

    private function tryToCreateUser(UserInfo $userInfo): ?User
    {
        if (!$this->configDataProvider->createUser()) {
            return null;
        }

        $usernameClaim = $this->configDataProvider->getUsernameClaim();

        if (!$usernameClaim) {
            throw new RuntimeException("Could not create a user. No OIDC username claim in config.");
        }

        $username = $userInfo->get($usernameClaim);

        if (!$username) {
            throw new RuntimeException("Could not create a user. No username claim in token and userinfo.");
        }

        return $this->sync->createUser($userInfo);
    }

    private function syncUser(User $user, UserInfo $userInfo): void
    {
        if (
            !$this->configDataProvider->sync() &&
            !$this->configDataProvider->syncTeams()
        ) {
            return;
        }

        $this->sync->syncUser($user, $userInfo);
    }
}
