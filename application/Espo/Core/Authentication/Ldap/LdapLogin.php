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
use Espo\Core\Name\Field;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\PasswordHash;
use Espo\Entities\User;
use Exception;
use Laminas\Ldap\Exception\LdapException;
use Laminas\Ldap\Ldap;
use SensitiveParameter;

/**
 * @noinspection PhpUnused
 */
class LdapLogin implements Login
{
    public const NAME = 'LDAP';

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
     * @noinspection PhpUnusedPrivateFieldInspection
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
     * @noinspection PhpUnusedPrivateFieldInspection
     */
    private $userFieldMap = [
        'teamsIds' => 'userTeamsIds',
        'defaultTeamId' => 'userDefaultTeamId',
    ];

    /**
     * @var array<string, string>
     * @noinspection PhpUnusedPrivateFieldInspection
     */
    private $portalUserFieldMap = [
        'portalsIds' => 'portalUserPortalsIds',
        'portalRolesIds' => 'portalUserRolesIds',
    ];

    /**
     * @throws LdapException
     */
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

            return Result::fail(FailReason::WRONG_CREDENTIALS);
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
        } catch (Exception $e) {
            $options = $this->utils->getLdapClientOptions();

            $this->log->error("LDAP: Could not connect to LDAP server host. {message}", [
                'host' => $options['host'],
                'message' => $e->getMessage()
            ]);

            /** @var string $username */

            $adminUser = $this->adminLogin($username, $password);

            if (!isset($adminUser)) {
                return Result::fail();
            }

            $this->log->info("LDAP: Administrator '{username}' was logged in by Espo method.", [
                'username' => $username,
            ]);
        }

        $userDn = null;

        if (!isset($adminUser)) {
            /** @var string $username */

            try {
                $userDn = $this->findLdapUserDnByUsername($username);
            } catch (Exception $e) {
                $this->log->error("Error while finding DN for '{username}'. {message}", [
                    'username' => $username,
                    'message' => $e->getMessage(),
                ]);
            }

            if (!isset($userDn)) {
                $this->log->error("LDAP: Authentication failed for '{username}'; user is not found.", [
                    'username' => $username,
                ]);

                $adminUser = $this->adminLogin($username, $password);

                if (!isset($adminUser)) {
                    return Result::fail();
                }

                $this->log->info("LDAP: Administrator '{username}' was logged in by Espo method.", [
                    'username' => $username,
                ]);
            }

            $this->log->debug("User '{username}' with DN '{dn}' is found .", [
                'username' => $username,
                'dn' => $userDn,
            ]);

            try {
                $ldapClient->bind($userDn, $password);
            } catch (Exception $e) {
                $this->log->error("LDAP: Authentication failed for '{username}'. {message}", [
                    'username' => $username,
                    'message' => $e->getMessage(),
                ]);

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
                $this->log->warning("LDAP: '{username}' authenticated, but user is not created in Espo.", [
                    'username' => $username,
                ]);

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
            } catch (Exception $e) {
                $this->log->error("LDAP error. {message}", ['message' => $e->getMessage()]);
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

            $this->log->alert("Unauthorized access attempt for user '{username}' from IP '{ip}'.", [
                'username' => $username,
                'ip' => $ip,
            ]);

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

    private function adminLogin(string $username, #[SensitiveParameter] string $password): ?User
    {
        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'userName' => $username,
                'type' => [User::TYPE_ADMIN, User::TYPE_SUPER_ADMIN],
            ])
            ->findOne();

        if (!$user) {
            return null;
        }

        if (!$this->passwordHash->verify($password, $user->getPassword())) {
            return null;
        }

        return $user;
    }

    /**
     * Create Espo user with data gets from LDAP server.
     *
     * @param array<string, mixed> $userData
     */
    private function createUser(array $userData, bool $isPortal = false): ?User
    {
        $this->log->info("LDAP: Creating new user.");
        $this->log->debug("LDAP: user data: {userData}", ['userData' => print_r($userData, true)]);

        $data = [];

        $ldapFields = $this->loadFields('ldap');

        foreach ($ldapFields as $espo => $ldap) {
            $ldap = strtolower($ldap);

            if (isset($userData[$ldap][0])) {
                $this->log->debug("LDAP: Create a user with [{user1}] = [{user2}].", [
                    'user1' => $espo,
                    'user2' => $userData[$ldap][0],
                ]);

                $data[$espo] = $userData[$ldap][0];
            }
        }

        if ($isPortal) {
            $userAttributes = $this->loadFields('portalUser');

            $userAttributes['type'] = User::TYPE_PORTAL;
        } else {
            $userAttributes = $this->loadFields('user');
        }

        foreach ($userAttributes as $key => $value) {
            $data[$key] = $value;
        }

        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getNew();

        $user->setMultiple($data);

        $this->entityManager->saveEntity($user, [
            // Prevent `user` service being loaded by hooks.
            SaveOption::SKIP_HOOKS => true,
            SaveOption::KEEP_NEW => true,
        ]);

        $saverParams = SaverParams::create()->withRawOptions(['skipLinkMultipleHooks' => true]);

        $this->linkMultipleSaver->process($user, Field::TEAMS, $saverParams);
        $this->linkMultipleSaver->process($user, 'portals', $saverParams);
        $this->linkMultipleSaver->process($user, 'portalRoles', $saverParams);
        $this->emailAddressSaver->process($user, $saverParams);
        $this->phoneNumberSaver->process($user, $saverParams);

        $user->setAsNotNew();
        $user->updateFetchedValues();

        return $this->entityManager->getRDBRepositoryByClass(User::class)->getById($user->getId());
    }

    /**
     * Find LDAP user DN by their username.
     *
     * @throws LdapException
     */
    private function findLdapUserDnByUsername(string $username): ?string
    {
        $ldapClient = $this->getLdapClient();
        $options = $this->utils->getOptions();

        $filterString = !empty($options['userLoginFilter']) ?
            $this->convertToFilterFormat($options['userLoginFilter']) : '';

        $objectClass = $options['userObjectClass'];
        $attribute = $options['userNameAttribute'];
        $usernameEscaped = $this->escapeUsernameFilter($username);

        $searchString = "(&(objectClass=$objectClass)($attribute=$usernameEscaped)$filterString)";

        /** @var array<int, array{dn: string}> $result */
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $result = $ldapClient->search($searchString, null, Ldap::SEARCH_SCOPE_SUB);

        $this->log->debug("LDAP: user search string: {string}.", ['string' => $searchString]);

        foreach ($result as $item) {
            return $item['dn'];
        }

        return null;
    }

    private function escapeUsernameFilter(string $username): string
    {
        $map = [
            '\\' => '\\5c',
            '*' => '\\2a',
            '(' => '\\28',
            ')' => '\\29',
            "\x00" => '\\00',
        ];

        return strtr($username, $map);
    }

    /**
     * Check and convert filter item into LDAP format.
     */
    private function convertToFilterFormat(string $filter): string
    {
        $filter = trim($filter);

        if (!str_starts_with($filter, '(')) {
            $filter = '(' . $filter;
        }

        if (!str_ends_with($filter, ')')) {
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
