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

namespace Espo\Core\Authentication\Login;

use Espo\Core\Exceptions\Error;

use Espo\Core\{
    ORM\EntityManager,
    Api\Request,
    Utils\Config,
    Utils\PasswordHash,
    Utils\Language,
    ApplicationUser,
    Authentication\Login,
    Authentication\LoginData,
    Authentication\Result,
    Authentication\LDAP\Utils as LDAPUtils,
    Authentication\LDAP\Client as LDAPClient,
    Authentication\AuthToken\AuthToken,
};

use Exception;

class LDAP implements Login
{
    private $utils;

    private $ldapClient;

    private $baseLogin;

    private $isPortal;

    private $config;

    private $entityManager;

    private $passwordHash;

    private $language;

    private $applicationUser;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        PasswordHash $passwordHash,
        Language $defaultLanguage,
        ApplicationUser $applicationUser,
        Espo $baseLogin,
        bool $isPortal = false
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->passwordHash = $passwordHash;
        $this->language = $defaultLanguage;
        $this->applicationUser = $applicationUser;
        $this->baseLogin = $baseLogin;

        $this->isPortal = $isPortal;

        $this->utils = new LDAPUtils($config);
    }

    private $ldapFieldMap = [
        'userName' => 'userNameAttribute',
        'firstName' => 'userFirstNameAttribute',
        'lastName' => 'userLastNameAttribute',
        'title' => 'userTitleAttribute',
        'emailAddress' => 'userEmailAddressAttribute',
        'phoneNumber' => 'userPhoneNumberAttribute',
    ];

    private $userFieldMap = [
        'teamsIds' => 'userTeamsIds',
        'defaultTeamId' => 'userDefaultTeamId',
    ];

    private $portalUserFieldMap = [
        'portalsIds' => 'portalUserPortalsIds',
        'portalRolesIds' => 'portalUserRolesIds',
    ];

    public function login(LoginData $loginData, Request $request) : Result
    {
        $username = $loginData->getUsername();
        $password = $loginData->getPassword();
        $authToken = $loginData->getAuthToken();

        $isPortal = $this->isPortal;

        if ($authToken) {
            $user = $this->loginByToken($username, $authToken);

            if ($user) {
                return Result::success($user);
            }
            else {
                return Result::fail();
            }
        }

        if (!$password || $username == '**logout') {
            return Result::fail();
        }

        if ($isPortal) {
            $useLdapAuthForPortalUser = $this->utils->getOption('portalUserLdapAuth');

            if (!$useLdapAuthForPortalUser) {
                return $this->baseLogin->login($loginData, $request);
            }
        }

        $ldapClient = $this->getLdapClient();

        /* Login LDAP system user (ldapUsername, ldapPassword) */
        try {
            $ldapClient->bind();
        }
        catch (Exception $e) {
            $options = $this->utils->getLdapClientOptions();

            $GLOBALS['log']->error(
                'LDAP: Could not connect to LDAP server [' . $options['host'] . '], details: ' . $e->getMessage()
            );

            $adminUser = $this->adminLogin($username, $password);

            if (!isset($adminUser)) {
                return Result::fail();
            }

            $GLOBALS['log']->info('LDAP: Administrator [' . $username . '] was logged in by Espo method.');
        }

        if (!isset($adminUser)) {
            try {
                $userDn = $this->findLdapUserDnByUsername($username);
            }
            catch (Exception $e) {
                $GLOBALS['log']->error(
                    'Error while finding DN for [' . $username . '], details: ' . $e->getMessage() . '.'
                );
            }

            if (!isset($userDn)) {
                $GLOBALS['log']->error(
                    'LDAP: Authentication failed for user [' . $username . '], details: user is not found.'
                );

                $adminUser = $this->adminLogin($username, $password);

                if (!isset($adminUser)) {
                    return Result::fail();
                }

                $GLOBALS['log']->info('LDAP: Administrator [' . $username . '] was logged in by Espo method.');
            }

            $GLOBALS['log']->debug('User [' . $username . '] is found with this DN ['.$userDn.'].');

            try {
                $ldapClient->bind($userDn, $password);
            }
            catch (Exception $e) {
                $GLOBALS['log']->error(
                    'LDAP: Authentication failed for user [' . $username . '], details: ' . $e->getMessage()
                );

                return Result::fail();
            }
        }

        $user = $this->entityManager->getRepository('User')->findOne([
            'whereClause' => [
                'userName' => $username,
                'type!=' => ['api', 'system']
            ]
        ]);

        if (!isset($user)) {
            if (!$this->utils->getOption('createEspoUser')) {
                $this->useSystemUser();

                throw new Error($this->language->translate('ldapUserInEspoNotFound', 'messages', 'User'));
            }

            $userData = $ldapClient->getEntry($userDn);

            $user = $this->createUser($userData, $isPortal);
        }

        if (!$user) {
            return Result::fail();
        }

        return Result::success($user);
    }

    private function useSystemUser()
    {
        $this->applicationUser->setupSystemUser();
    }

    private function getLdapClient()
    {
        if (!isset($this->ldapClient)) {
            $options = $this->utils->getLdapClientOptions();

            try {
                $this->ldapClient = new LDAPClient($options);
            }
            catch (Exception $e) {
                $GLOBALS['log']->error('LDAP error: ' . $e->getMessage());
            }
        }

        return $this->ldapClient;
    }

    /**
     * Login by authorization token.
     */
    private function loginByToken($username, AuthToken $authToken = null)
    {
        if (!isset($authToken)) {
            return null;
        }

        $userId = $authToken->getUserId();

        $user = $this->entityManager->getEntity('User', $userId);

        $tokenUsername = $user->get('userName');

        if (strtolower($username) != strtolower($tokenUsername)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            $GLOBALS['log']->alert(
                'Unauthorized access attempt for user [' . $username . '] from IP ['.$ip.']'
            );

            return null;
        }

        $user = $this->entityManager->getRepository('User')->findOne([
            'whereClause' => [
                'userName' => $username,
            ]
        ]);

        return $user;
    }

    /**
     * Login user with administrator rights.
     */
    private function adminLogin($username, $password)
    {
        $hash = $this->passwordHash->hash($password);

        $user = $this->entityManager->getRepository('User')->findOne([
            'whereClause' => [
                'userName' => $username,
                'password' => $hash,
                'type' => ['admin', 'super-admin'],
            ]
        ]);

        return $user;
    }

    /**
     * Create Espo user with data gets from LDAP server.
     */
    private function createUser(array $userData, $isPortal = false)
    {
        $GLOBALS['log']->info('Creating new user...');

        $data = [];

        $GLOBALS['log']->debug('LDAP: user data: ' . print_r($userData, true));

        $ldapFields = $this->loadFields('ldap');

        foreach ($ldapFields as $espo => $ldap) {
            $ldap = strtolower($ldap);

            if (isset($userData[$ldap][0])) {
                $GLOBALS['log']->debug('LDAP: Create a user with [' . $espo . '] = [' . $userData[$ldap][0] . '].');

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

        $user = $this->entityManager->getEntity('User');

        $user->set($data);

        $this->applicationUser->setUser($user);

        $this->entityManager->saveEntity($user);

        return $this->entityManager->getEntity('User', $user->id);
    }

    /**
     * Find LDAP user DN by his username.
     */
    private function findLdapUserDnByUsername($username)
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

        $result = $ldapClient->search($searchString, null, LDAPClient::SEARCH_SCOPE_SUB);

        $GLOBALS['log']->debug('LDAP: user search string: "' . $searchString . '"');

        foreach ($result as $item) {
            return $item["dn"];
        }
    }

    /**
     * Check and convert filter item into LDAP format.
     */
    private function convertToFilterFormat($filter)
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
     */
    private function loadFields(string $type) : array
    {
        $options = $this->utils->getOptions();

        $typeMap = $type . 'FieldMap';

        $fields = [];

        foreach ($this->$typeMap as $fieldName => $fieldValue) {
            if (isset($options[$fieldValue])) {
                $fields[$fieldName] = $options[$fieldValue];
            }
        }

        return $fields;
    }
}
