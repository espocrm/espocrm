<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Authentication\Ldap;

use Espo\Core\Api\Util;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\FieldProcessing\EmailAddress\Saver as EmailAddressSaver;
use Espo\Core\FieldProcessing\PhoneNumber\Saver as PhoneNumberSaver;
use Espo\Core\FieldProcessing\Saver\Params as SaverParams;
use Espo\Core\Api\Request;
use Espo\Core\Authentication\AuthToken\AuthToken;
use Espo\Core\Authentication\Ldap\Client as Client;
use Espo\Core\Authentication\Ldap\ClientFactory as ClientFactory;
use Espo\Core\Authentication\Ldap\Utils as LDAPUtils;
use Espo\Core\Authentication\Login;
use Espo\Core\Authentication\Login\Data;
use Espo\Core\Authentication\Logins\Espo;
use Espo\Core\Authentication\Result;
use Espo\Core\Authentication\Result\FailReason;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\PasswordHash;
use Espo\Entities\User;
use Exception;

class LdapLogin implements Login
{
    private LDAPUtils $utils;
    private ?Client $client = null;

    private Language $language;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private PasswordHash $passwordHash,
        Language $defaultLanguage,
        private Log $log,
        private Espo $baseLogin,
        private ClientFactory $clientFactory,
        private LinkMultipleSaver $linkMultipleSaver,
        private EmailAddressSaver $emailAddressSaver,
        private PhoneNumberSaver $phoneNumberSaver,
        private Util $util,
        private bool $isPortal = false
    ) {
        $this->language = $defaultLanguage;

        $this->utils = new LDAPUtils($config);
    }

    /**
     * @var array<string, string>
     */
    private $ldapFieldMap = [
        'userName' => 'userNameAttribute',
        'firstName' => 'userFirstNameAttribute',
        'lastName' => 'userLastNameAttribute',
        'title' => 'userTitleAttribute',
        'emailAddress' => 'userEmailAddressAttribute',
        'phoneNumber' => 'userPhoneNumberAttribute',
    ];

    /**
     * @var array<string, string>
     */
    private $userFieldMap = [
        'teamsIds' => 'userTeamsIds',
        'defaultTeamId' => 'userDefaultTeamId',
    ];

    /**
     * @var array<string, string>
     */
    private $portalUserFieldMap = [
        'portalsIds' => 'portalUserPortalsIds',
        'portalRolesIds' => 'portalUserRolesIds',
    ];

    public function login(Data $data, Request $request): Result
    {
        $username = $data->getUsername();
        $password = $data->getPassword();
        $authToken = $data->getAuthToken();

        $isPortal = $this->isPortal;

        if ($authToken) {
            $user = $this->loginByToken($username, $authToken, $request);

            if ($user) {
                return Result::success($user);
            }
            else {
                return Result::fail(FailReason::WRONG_CREDENTIALS);
            }
        }

        if (!$password || $username == '**logout') {
            return Result::fail(FailReason::NO_PASSWORD);
        }

        if ($isPortal) {
            $useLdapAuthForPortalUser = $this->utils->getOption('portalUserLdapAuth');

            if (!$useLdapAuthForPortalUser) {
                return $this->baseLogin->login($data, $request);
            }
        }

        $ldapClient = $this->getLdapClient();

        /* Login LDAP system user (ldapUsername, ldapPassword) */
        try {
            $ldapClient->bind();
        }
        catch (Exception $e) {
            $options = $this->utils->getLdapClientOptions();

            $this->log->error(
                'LDAP: Could not connect to LDAP server [' . $options['host'] . '], details: ' . $e->getMessage()
            );

            /** @var string $username */

            $adminUser = $this->adminLogin($username, $password);

            if (!isset($adminUser)) {
                return Result::fail();
            }

            $this->log->info('LDAP: Administrator [' . $username . '] was logged in by Espo method.');
        }

        $userDn = null;

        if (!isset($adminUser)) {
            /** @var string $username */

            try {
                $userDn = $this->findLdapUserDnByUsername($username);
            }
            catch (Exception $e) {
                $this->log->error(
                    'Error while finding DN for [' . $username . '], details: ' . $e->getMessage() . '.'
                );
            }

            if (!isset($userDn)) {
                $this->log->error(
                    'LDAP: Authentication failed for user [' . $username . '], details: user is not found.'
                );

                $adminUser = $this->adminLogin($username, $password);

                if (!isset($adminUser)) {
                    return Result::fail();
                }

                $this->log->info('LDAP: Administrator [' . $username . '] was logged in by Espo method.');
            }

            $this->log->debug('User [' . $username . '] is found with this DN ['.$userDn.'].');

            try {
                $ldapClient->bind($userDn, $password);
            }
            catch (Exception $e) {
                $this->log->error(
                    'LDAP: Authentication failed for user [' . $username . '], details: ' . $e->getMessage()
                );

                return Result::fail();
            }
        }

        $user = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'userName' => $username,
                'type!=' => [
                    User::TYPE_API,
                    User::TYPE_SYSTEM,
                    User::TYPE_SUPER_ADMIN,
                ],
            ])
            ->findOne();

        if (!isset($user)) {
            if (!$this->utils->getOption('createEspoUser')) {
                $this->log->warning(
                    "LDAP: Authentication success for user {$username}, but user is not created in EspoCRM."
                );

                return Result::fail(FailReason::USER_NOT_FOUND);
            }

            /** @var string $userDn */

            $userData = $ldapClient->getEntry($userDn);

            $user = $this->createUser($userData, $isPortal);
        }

        if (!$user) {
            return Result::fail();
        }

        return Result::success($user);
    }

    private function getLdapClient(): Client
    {
        if (!isset($this->client)) {
            $options = $this->utils->getLdapClientOptions();

            try {
                $this->client = $this->clientFactory->create($options);
            }
            catch (Exception $e) {
                $this->log->error('LDAP error: ' . $e->getMessage());
            }
        }

        /** @var Client */
        return $this->client;
    }

    /**
     * Login by authorization token.
     */
    private function loginByToken(?string $username, AuthToken $authToken, Request $request): ?User
    {
        if ($username === null) {
            return null;
        }

        $userId = $authToken->getUserId();

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            return null;
        }

        $tokenUsername = $user->getUserName() ?? '';

        if (strtolower($username) !== strtolower($tokenUsername)) {
            $ip = $this->util->obtainIpFromRequest($request);

            $this->log->alert('Unauthorized access attempt for user [' . $username . '] from IP [' . $ip . ']');

            return null;
        }

        /** @var ?User */
        return $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'userName' => $username,
            ])
            ->findOne();
    }

    private function adminLogin(string $username, string $password): ?User
    {
        $hash = $this->passwordHash->hash($password);

        return $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'userName' => $username,
                'password' => $hash,
                'type' => [User::TYPE_ADMIN, User::TYPE_SUPER_ADMIN],
            ])
            ->findOne();
    }

    /**
     * Create Espo user with data gets from LDAP server.
     *
     * @param array<string, mixed> $userData
     */
    private function createUser(array $userData, bool $isPortal = false): ?User
    {
        $this->log->info('Creating new user...');

        $data = [];

        $this->log->debug('LDAP: user data: ' . print_r($userData, true));

        $ldapFields = $this->loadFields('ldap');

        foreach ($ldapFields as $espo => $ldap) {
            $ldap = strtolower($ldap);

            if (isset($userData[$ldap][0])) {
                $this->log->debug('LDAP: Create a user with [' . $espo . '] = [' . $userData[$ldap][0] . '].');

                $data[$espo] = $userData[$ldap][0];
            }
        }

        if ($isPortal) {
            $userFields = $this->loadFields('portalUser');

            $userFields['type'] = 'portal';
        }
        else {
            $userFields = $this->loadFields('user');
        }

        foreach ($userFields as $fieldName => $fieldValue) {
            $data[$fieldName] = $fieldValue;
        }

        $user = $this->entityManager->getNewEntity('User');

        $user->set($data);

        $this->entityManager->saveEntity($user, [
            // Prevent `user` service being loaded by hooks.
            SaveOption::SKIP_HOOKS => true,
            SaveOption::KEEP_NEW => true,
        ]);

        $saverParams = SaverParams::create()
            ->withRawOptions([
                'skipLinkMultipleHooks' => true,
            ]);

        $this->linkMultipleSaver->process($user, 'teams', $saverParams);
        $this->linkMultipleSaver->process($user, 'portals', $saverParams);
        $this->linkMultipleSaver->process($user, 'portalRoles', $saverParams);
        $this->emailAddressSaver->process($user, $saverParams);
        $this->phoneNumberSaver->process($user, $saverParams);

        $user->setAsNotNew();
        $user->updateFetchedValues();

        return $this->entityManager->getEntityById(User::ENTITY_TYPE, $user->getId());
    }

    /**
     * Find LDAP user DN by his username.
     *
     * @throws \Laminas\Ldap\Exception\LdapException
     */
    private function findLdapUserDnByUsername(string $username): ?string
    {
        $ldapClient = $this->getLdapClient();

        $options = $this->utils->getOptions();

        $loginFilterString = '';

        if (!empty($options['userLoginFilter'])) {
            $loginFilterString = $this->convertToFilterFormat($options['userLoginFilter']);
        }

        $searchString =
            '(&(objectClass=' . $options['userObjectClass'] . ')' .
            '(' . $options['userNameAttribute'] . '=' . $username . ')' .
            $loginFilterString . ')';

        /** @var array<int, array{dn: string}> $result */
        $result = $ldapClient->search($searchString, null, Client::SEARCH_SCOPE_SUB);

        $this->log->debug('LDAP: user search string: "' . $searchString . '"');

        foreach ($result as $item) {
            return $item["dn"];
        }

        return null;
    }

    /**
     * Check and convert filter item into LDAP format.
     */
    private function convertToFilterFormat(string $filter): string
    {
        $filter = trim($filter);

        if (substr($filter, 0, 1) != '(') {
            $filter = '(' . $filter;
        }

        if (substr($filter, -1) != ')') {
            $filter = $filter . ')';
        }

        return $filter;
    }

    /**
     * Load fields for a user.
     *
     * @return array<string, mixed>
     */
    private function loadFields(string $type): array
    {
        $options = $this->utils->getOptions();

        $typeMap = $type . 'FieldMap';

        $fields = [];

        foreach ($this->$typeMap as $fieldName => $fieldValue) {
            /** @var string $fieldName */
            if (isset($options[$fieldValue])) {
                $fields[$fieldName] = $options[$fieldValue];
            }
        }

        return $fields;
    }
}
