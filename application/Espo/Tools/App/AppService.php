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

namespace Espo\Tools\App;

use Espo\Core\Authentication\Util\MethodProvider as AuthenticationMethodProvider;
use Espo\Core\Mail\ConfigDataProvider as EmailConfigDataProvider;
use Espo\Core\Name\Field;
use Espo\Core\Name\Link;
use Espo\Core\Utils\SystemUser;
use Espo\Entities\DashboardTemplate;
use Espo\Entities\Email;
use Espo\Entities\EmailAccount;
use Espo\Entities\EmailAddress;
use Espo\Entities\InboundEmail;
use Espo\Entities\Settings;
use Espo\ORM\Name\Attribute;
use Espo\Core\Acl;
use Espo\Core\Authentication\Logins\Espo;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Entities\Preferences;
use Espo\ORM\EntityManager;
use stdClass;
use Throwable;

class AppService
{
    /** @var string[] */
    private array $forbiddenUserAttributeList = [
        'apiKey',
        'authTokenId',
        'password',
        'rolesIds',
        'rolesNames',
    ];

    /** @var string[] */
    private array $allowedUserAttributeList = [
        'type',
    ];

    /** @var string[] */
    private array $allowedInternalUserAttributeList = [
        'teamsIds',
        'defaultTeamId',
        'defaultTeamName',
    ];

    /** @var string[] */
    private array $allowedPortalUserAttributeList = [
        'contactId',
        'contactName',
        'accountId',
        'accountsIds',
    ];

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Acl $acl,
        private InjectableFactory $injectableFactory,
        private SettingsService $settingsService,
        private User $user,
        private Preferences $preferences,
        private FieldUtil $fieldUtil,
        private Log $log,
        private AuthenticationMethodProvider $authenticationMethodProvider,
        private SystemUser $systemUser,
        private EmailConfigDataProvider $emailConfigDataProvider,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getUserData(): array
    {
        $preferencesData = $this->preferences->getValueMap();

        $this->filterPreferencesData($preferencesData);

        $user = $this->user;

        if (!$user->has('teamsIds')) {
            $user->loadLinkMultipleField(Field::TEAMS);
        }

        if ($user->isPortal()) {
            $user->loadAccountField();
            $user->loadLinkMultipleField('accounts');
        }

        $settings = $this->settingsService->getConfigData();

        $dashboardTemplateId = $user->get('dashboardTemplateId');

        if ($dashboardTemplateId) {
            $dashboardTemplate = $this->entityManager
                ->getEntityById(DashboardTemplate::ENTITY_TYPE, $dashboardTemplateId);

            if ($dashboardTemplate) {
                $settings->forcedDashletsOptions = $dashboardTemplate->get('dashletsOptions') ?? (object) [];
                $settings->forcedDashboardLayout = $dashboardTemplate->get('layout') ?? [];
            }
        }

        $language = Language::detectLanguage($this->config, $this->preferences);

        return [
            'user' => $this->getUserDataForFrontend(),
            'acl' => $this->getAclDataForFrontend(),
            'preferences' => $preferencesData,
            'token' => $this->user->get('token'),
            'settings' => $settings,
            'language' => $language,
            'appParams' => $this->getAppParams(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getAppParams(): array
    {
        $user = $this->user;

        $auth2FARequired =
            $user->isRegular() &&
            $this->config->get('auth2FA') &&
            $this->config->get('auth2FAForced') &&
            !$user->get('auth2FA');

        $authenticationMethod = $this->authenticationMethodProvider->get();

        $passwordChangeForNonAdminDisabled = $authenticationMethod !== Espo::NAME;
        $logoutWait = (bool) $this->metadata->get(['authenticationMethods', $authenticationMethod, 'logoutClassName']);

        $timeZoneList = $this->metadata
            ->get(['entityDefs', Settings::ENTITY_TYPE, 'fields', 'timeZone', 'options']) ?? [];

        $appParams = [
            'maxUploadSize' => $this->getMaxUploadSize() / 1024.0 / 1024.0,
            'isRestrictedMode' => $this->config->get('restrictedMode'),
            'passwordChangeForNonAdminDisabled' => $passwordChangeForNonAdminDisabled,
            'timeZoneList' => $timeZoneList,
            'auth2FARequired' => $auth2FARequired,
            'logoutWait' => $logoutWait,
            'systemUserId' => $this->systemUser->getId(),
        ];

        /** @var array<string, array<string, mixed>> $map */
        $map = $this->metadata->get(['app', 'appParams']) ?? [];

        foreach ($map as $paramKey => $item) {
            /** @var ?class-string<AppParam> $className */
            $className = $item['className'] ?? null;

            if (!$className) {
                continue;
            }

            try {
                /** @var AppParam $obj */
                $obj = $this->injectableFactory->create($className);

                $itemParams = $obj->get();
            } catch (Throwable $e) {
                $this->log->error("AppParam $paramKey: " . $e->getMessage(), ['exception' => $e]);

                continue;
            }

            $appParams[$paramKey] = $itemParams;
        }

        return $appParams;
    }

    private function getUserDataForFrontend(): stdClass
    {
        $user = $this->user;

        $data = $user->getValueMap();

        $emailAddressData = $this->getEmailAddressData();

        $data->emailAddressList = $emailAddressData['emailAddressList'];
        $data->userEmailAddressList = $emailAddressData['userEmailAddressList'];
        $data->excludeFromReplyEmailAddressList = $emailAddressData['excludeFromReplyEmailAddressList'];

        foreach ($this->forbiddenUserAttributeList as $attribute) {
            unset($data->$attribute);
        }

        $forbiddenAttributeList = $this->acl->getScopeForbiddenAttributeList(User::ENTITY_TYPE);

        $isPortal = $user->isPortal();

        foreach ($forbiddenAttributeList as $attribute) {
            if (in_array($attribute, $this->allowedUserAttributeList)) {
                continue;
            }

            if ($isPortal && in_array($attribute, $this->allowedPortalUserAttributeList)) {
                continue;
            }

            if (!$isPortal && in_array($attribute, $this->allowedInternalUserAttributeList)) {
                continue;
            }

            unset($data->$attribute);
        }

        return $data;
    }

    private function getAclDataForFrontend(): stdClass
    {
        $data = $this->acl->getMapData();

        if (!$this->user->isAdmin()) {
            $data = unserialize(serialize($data));

            /** @var string[] $scopeList */
            $scopeList = array_keys($this->metadata->get(['scopes'], []));

            foreach ($scopeList as $scope) {
                if (!$this->acl->check($scope)) {
                    unset($data->table->$scope);
                    unset($data->fieldTable->$scope);
                    unset($data->fieldTableQuickAccess->$scope);
                }
            }
        }

        return $data;
    }

    /**
     * @return array{
     *     emailAddressList: string[],
     *     userEmailAddressList: string[],
     *     excludeFromReplyEmailAddressList: string[],
     * }
     */
    private function getEmailAddressData(): array
    {
        $user = $this->user;

        $systemIsShared = $this->emailConfigDataProvider->isSystemOutboundAddressShared();
        $systemAddress = $this->emailConfigDataProvider->getSystemOutboundAddress();

        $addressList = [];
        $userAddressList = [];

        /** @var iterable<EmailAddress> $emailAddresses */
        $emailAddresses = $this->entityManager
            ->getRelation($user, Link::EMAIL_ADDRESSES)
            ->find();

        foreach ($emailAddresses as $emailAddress) {
            if ($emailAddress->isInvalid()) {
                continue;
            }

            $userAddressList[] = $emailAddress->getAddress();

            if ($user->getEmailAddress() === $emailAddress->getAddress()) {
                continue;
            }

            $addressList[] = $emailAddress->getAddress();
        }

        if ($user->getEmailAddress()) {
            array_unshift($addressList, $user->getEmailAddress());
        }

        if (!$systemIsShared) {
            $addressList = $this->filterUserEmailAddressList($user, $addressList);
        }

        $addressList = array_merge($addressList, $this->getUserGroupEmailAddressList($user));

        if ($systemIsShared && $systemAddress) {
            $addressList[] = $systemAddress;
        }

        $addressList = array_values(array_unique($addressList));

        return [
            'emailAddressList' => $addressList,
            'userEmailAddressList' => $userAddressList,
            'excludeFromReplyEmailAddressList' => $this->getExcludeFromReplyAddressList(),
        ];
    }

    /**
     * @param string[] $emailAddressList
     * @return string[]
     */
    private function filterUserEmailAddressList(User $user, array $emailAddressList): array
    {
        $emailAccountCollection = $this->entityManager
            ->getRDBRepositoryByClass(EmailAccount::class)
            ->select([
                Attribute::ID,
                Field::EMAIL_ADDRESS,
            ])
            ->where([
                'assignedUserId' => $user->getId(),
                'useSmtp' => true,
                'status' => EmailAccount::STATUS_ACTIVE,
            ])
            ->find();

        $inAccountList = array_map(
            fn (EmailAccount $e) => $e->getEmailAddress(),
            [...$emailAccountCollection]
        );

        return array_values(array_filter(
            $emailAddressList,
            fn (string $item) => in_array($item, $inAccountList)
        ));
    }

    /**
     * @return string[]
     */
    private function getUserGroupEmailAddressList(User $user): array
    {
        $groupEmailAccountPermission = $this->acl->getPermissionLevel(Acl\Permission::GROUP_EMAIL_ACCOUNT);

        if (!$groupEmailAccountPermission || $groupEmailAccountPermission === Acl\Table::LEVEL_NO) {
            return [];
        }

        if ($groupEmailAccountPermission === Acl\Table::LEVEL_TEAM) {
            $teamIdList = $user->getLinkMultipleIdList(Field::TEAMS);

            if (!count($teamIdList)) {
                return [];
            }

            $inboundEmailList = $this->entityManager
                ->getRDBRepositoryByClass(InboundEmail::class)
                ->where([
                    'status' => InboundEmail::STATUS_ACTIVE,
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                    'teamsMiddle.teamId' => $teamIdList,
                ])
                ->join(Field::TEAMS)
                ->distinct()
                ->find();

            $list = [];

            foreach ($inboundEmailList as $inboundEmail) {
                if (!$inboundEmail->getEmailAddress()) {
                    continue;
                }

                $list[] = $inboundEmail->getEmailAddress();
            }

            return $list;
        }

        if ($groupEmailAccountPermission === Acl\Table::LEVEL_ALL) {
            $inboundEmailList = $this->entityManager
                ->getRDBRepositoryByClass(InboundEmail::class)
                ->where([
                    'status' => InboundEmail::STATUS_ACTIVE,
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                ])
                ->find();

            $list = [];

            foreach ($inboundEmailList as $inboundEmail) {
                if (!$inboundEmail->getEmailAddress()) {
                    continue;
                }

                $list[] = $inboundEmail->getEmailAddress();
            }

            return $list;
        }

        return [];
    }

    /**
     * @return int
     */
    private function getMaxUploadSize()
    {
        $maxSize = 0;

        $postMaxSize = $this->convertPHPSizeToBytes(ini_get('post_max_size'));

        if ($postMaxSize > 0) {
            $maxSize = $postMaxSize;
        }

        return $maxSize;
    }

    /**
     * @param string|false $size
     * @return int
     */
    private function convertPHPSizeToBytes($size)
    {
        if (is_numeric($size)) {
            return (int) $size;
        }

        if ($size === false) {
            return 0;
        }

        $suffix = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        if ($suffix == 'P') {
            $value *= pow(1024, 5);
        } else if ($suffix == 'T') {
            $value *= pow(1024, 4);
        } else if ($suffix == 'G') {
            $value *= pow(1024, 3);
        } else if ($suffix == 'M') {
            $value *= pow(1024, 2);
        } elseif ($suffix == 'K') {
            $value *= 1024;
        }

        return $value;
    }

    private function filterPreferencesData(stdClass $data): void
    {
        $passwordFieldList = $this->fieldUtil->getFieldByTypeList(Preferences::ENTITY_TYPE, 'password');

        foreach ($passwordFieldList as $field) {
            unset($data->$field);
        }
    }

    /**
     * @return string[]
     */
    private function getExcludeFromReplyAddressList(): array
    {
        if (!$this->acl->checkScope(Email::ENTITY_TYPE, Acl\Table::ACTION_CREATE)) {
            return [];
        }

        /** @var iterable<InboundEmail> $accounts */
        $accounts = $this->entityManager
            ->getRDBRepositoryByClass(InboundEmail::class)
            ->select('emailAddress')
            ->where(['excludeFromReply' => true])
            ->find();

        $list = [];

        foreach ($accounts as $account) {
            if (!$account->getEmailAddress()) {
                continue;
            }

            $list[] = $account->getEmailAddress();
        }

        return $list;
    }
}
