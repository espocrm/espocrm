<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Acl\Cache\Clearer as AclCacheClearer;
use Espo\Core\Authentication\Jwt\Token\Payload;
use Espo\Core\FieldProcessing\EmailAddress\Saver as EmailAddressSaver;
use Espo\Core\FieldProcessing\PhoneNumber\Saver as PhoneNumberSaver;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\FieldProcessing\Saver\Params as SaverParams;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\PasswordHash;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use RuntimeException;
use stdClass;

class Sync
{
    private EntityManager $entityManager;
    private Config $config;
    private LinkMultipleSaver $linkMultipleSaver;
    private EmailAddressSaver $emailAddressSaver;
    private PhoneNumberSaver $phoneNumberSaver;
    private PasswordHash $passwordHash;
    private AclCacheClearer $aclCacheClearer;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        LinkMultipleSaver $linkMultipleSaver,
        EmailAddressSaver $emailAddressSaver,
        PhoneNumberSaver $phoneNumberSaver,
        PasswordHash $passwordHash,
        AclCacheClearer $aclCacheClearer
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->linkMultipleSaver = $linkMultipleSaver;
        $this->emailAddressSaver = $emailAddressSaver;
        $this->phoneNumberSaver = $phoneNumberSaver;
        $this->passwordHash = $passwordHash;
        $this->aclCacheClearer = $aclCacheClearer;
    }

    public function createUser(Payload $payload): User
    {
        $username = $this->getUsernameFromToken($payload);

        $this->validateUsername($username);

        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getNew();

        $user->set([
            'type' => User::TYPE_REGULAR,
            'userName' => $username,
            'password' => $this->passwordHash->hash(Util::generatePassword(10, 4, 2, true)),
        ]);

        $user->set($this->getUserDataFromToken($payload));
        $user->set($this->getUserTeamsDataFromToken($payload));

        $this->saveUser($user);

        return $user;
    }

    public function syncUser(User $user, Payload $payload): void
    {
        $username = $this->getUsernameFromToken($payload);

        $this->validateUsername($username);

        if ($user->getUserName() !== $username) {
            throw new RuntimeException("Could not sync user. Username mismatch.");
        }

        if ($this->config->get('oidcSync')) {
            $user->set($this->getUserDataFromToken($payload));
        }

        $clearAclCache = false;

        if ($this->config->get('oidcSyncTeams')) {
            $user->loadLinkMultipleField('teams');

            $user->set($this->getUserTeamsDataFromToken($payload));

            $clearAclCache = $user->isAttributeChanged('teamsIds');
        }

        $this->saveUser($user);

        if ($clearAclCache) {
            $this->aclCacheClearer->clearForUser($user);
        }
    }

    private function saveUser(User $user): void
    {
        $this->entityManager->saveEntity($user, [
            // Prevent `user` service being loaded by hooks.
            SaveOption::SKIP_HOOKS => true,
            SaveOption::KEEP_NEW => true,
            SaveOption::KEEP_DIRTY => true,
        ]);

        $saverParams = SaverParams::create()->withRawOptions(['skipLinkMultipleHooks' => true]);

        $this->linkMultipleSaver->process($user, 'teams', $saverParams);
        $this->linkMultipleSaver->process($user, 'portals', $saverParams);
        $this->linkMultipleSaver->process($user, 'portalRoles', $saverParams);
        $this->emailAddressSaver->process($user, $saverParams);
        $this->phoneNumberSaver->process($user, $saverParams);

        $user->setAsNotNew();
        $user->updateFetchedValues();

        $this->entityManager->refreshEntity($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserDataFromToken(Payload $payload): array
    {
        return [
            'emailAddress' => $payload->get('email'),
            'phoneNumber' => $payload->get('phone_number'),
            'emailAddressData' => null,
            'phoneNumberData' => null,
            'firstName' => $payload->get('given_name'),
            'lastName' => $payload->get('family_name'),
            'middle_name' => $payload->get('middle_name'),
            'gender' =>
                in_array($payload->get('gender'), ['male', 'female']) ?
                    ucfirst($payload->get('gender') ?? '') :
                    null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserTeamsDataFromToken(Payload $payload): array
    {
        return [
            'teamsIds' => $this->getTeamIdList($payload),
        ];
    }

    private function getUsernameFromToken(Payload $payload): string
    {
        $usernameClaim = $this->config->get('oidcUsernameClaim');

        if (!$usernameClaim) {
            throw new RuntimeException("No OIDC username claim in config.");
        }

        $username = $payload->get($usernameClaim);

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
    private function getTeamIdList(Payload $payload): array
    {
        /** @var string[] $idList */
        $idList = $this->config->get('oidcTeamsIds') ?? [];
        /** @var stdClass $columns */
        $columns = $this->config->get('oidcTeamsColumns') ?? (object) [];

        if ($idList === []) {
            return [];
        }

        $groupList = $this->getGroups($payload);

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
    private function getGroups(Payload $payload): array
    {
        /** @var ?string $groupClaim */
        $groupClaim = $this->config->get('oidcGroupClaim');

        if (!$groupClaim) {
            return [];
        }

        $value = $payload->get($groupClaim);

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

    private function validateUsername(string $username): void
    {
        $maxLength = $this->entityManager
            ->getDefs()
            ->getEntity(User::ENTITY_TYPE)
            ->getAttribute('userName')
            ->getLength();

        if ($maxLength && $maxLength < strlen($username)) {
            throw new RuntimeException("Value in username claim exceeds max length of `{$maxLength}`. " .
                "Increase maxLength parameter for User.userName field (up to 255).");
        }
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
        $result = preg_replace("/{$regExp}/", '_', $username);

        /** @var string */
        return str_replace(' ', '_', $result);
    }
}
