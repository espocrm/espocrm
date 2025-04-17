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

use Espo\Core\Acl\Cache\Clearer as AclCacheClearer;
use Espo\Core\ApplicationState;
use Espo\Core\Authentication\Oidc\ConfigDataProvider;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\PasswordHash;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use RuntimeException;

class Sync
{
    public function __construct(
        private UsernameValidator $usernameValidator,
        private Config $config,
        private ConfigDataProvider $configDataProvider,
        private UserRepository $userRepository,
        private PasswordHash $passwordHash,
        private AclCacheClearer $aclCacheClearer,
        private ApplicationState $applicationState,
    ) {}

    public function createUser(UserInfo $userInfo): User
    {
        $username = $this->getUsernameFromToken($userInfo);

        $this->usernameValidator->validate($username);

        $user = $this->userRepository->getNew();

        $user->setType(User::TYPE_REGULAR);
        $user->setUserName($username);

        $user->setMultiple([
            'password' => $this->passwordHash->hash(Util::generatePassword(10, 4, 2, true)),
        ]);

        $user->set($this->getUserDataFromToken($userInfo));
        $user->set($this->getUserTeamsDataFromToken($userInfo));

        if ($this->applicationState->isPortal()) {
            $portalId = $this->applicationState->getPortalId();

            $user->setType(User::TYPE_PORTAL);
            $user->setPortals(LinkMultiple::create()->withAddedId($portalId));
        }

        $this->userRepository->save($user);

        return $user;
    }

    public function syncUser(User $user, UserInfo $payload): void
    {
        $username = $this->getUsernameFromToken($payload);

        $this->usernameValidator->validate($username);

        if ($user->getUserName() !== $username) {
            throw new RuntimeException("Could not sync user. Username mismatch.");
        }

        if ($this->configDataProvider->sync()) {
            $user->set($this->getUserDataFromToken($payload));
        }

        $clearAclCache = false;

        if ($this->configDataProvider->syncTeams()) {
            $user->loadLinkMultipleField(Field::TEAMS);

            $user->set($this->getUserTeamsDataFromToken($payload));

            $clearAclCache = $user->isAttributeChanged('teamsIds');
        }

        $this->userRepository->save($user);

        if ($clearAclCache) {
            $this->aclCacheClearer->clearForUser($user);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserDataFromToken(UserInfo $userInfo): array
    {
        return [
            'emailAddress' => $userInfo->get('email'),
            'phoneNumber' => $userInfo->get('phone_number'),
            'emailAddressData' => null,
            'phoneNumberData' => null,
            'firstName' => $userInfo->get('given_name'),
            'lastName' => $userInfo->get('family_name'),
            'middle_name' => $userInfo->get('middle_name'),
            'gender' =>
                in_array($userInfo->get('gender'), ['male', 'female']) ?
                    ucfirst($userInfo->get('gender') ?? '') :
                    null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserTeamsDataFromToken(UserInfo $userInfo): array
    {
        return [
            'teamsIds' => $this->getTeamIdList($userInfo),
        ];
    }

    private function getUsernameFromToken(UserInfo $userInfo): string
    {
        $usernameClaim = $this->configDataProvider->getUsernameClaim();

        if (!$usernameClaim) {
            throw new RuntimeException("No OIDC username claim in config.");
        }

        $username = $userInfo->get($usernameClaim);

        if (!$username) {
            throw new RuntimeException("No username claim returned in token.");
        }

        if (!is_string($username)) {
            throw new RuntimeException("Bad username claim returned in token.");
        }

        return $this->normalizeUsername($username);
    }

    /**
     * @return string[]
     */
    private function getTeamIdList(UserInfo $userInfo): array
    {
        $idList = $this->configDataProvider->getTeamIds() ?? [];
        $columns = $this->configDataProvider->getTeamColumns() ?? (object) [];

        if ($idList === []) {
            return [];
        }

        $groupList = $this->getGroups($userInfo);

        $resultIdList = [];

        foreach ($idList as $id) {
            $group = ($columns->$id ?? (object) [])->group ?? null;

            if (!$group || in_array($group, $groupList)) {
                $resultIdList[] = $id;
            }
        }

        return $resultIdList;
    }

    /**
     * @return string[]
     */
    private function getGroups(UserInfo $userInfo): array
    {
        $groupClaim = $this->configDataProvider->getGroupClaim();

        if (!$groupClaim) {
            return [];
        }

        $value = $userInfo->get($groupClaim);

        if (!$value) {
            return [];
        }

        if (is_string($value)) {
            return [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $list = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $list[] = $item;
            }
        }

        return $list;
    }

    public function normalizeUsername(string $username): string
    {
        /** @var ?string $regExp */
        $regExp = $this->config->get('userNameRegularExpression');

        if (!$regExp) {
            throw new RuntimeException("No `userNameRegularExpression` in config.");
        }

        $username = strtolower($username);

        /** @var string $result */
        $result = preg_replace("/$regExp/", '_', $username);

        /** @var string */
        return str_replace(' ', '_', $result);
    }
}
