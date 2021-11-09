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

namespace Espo\Services;

use Espo\Services\Settings as SettingsService;

use Espo\Repositories\PhoneNumber as PhoneNumberRepository;
use Espo\Repositories\ArrayValue as ArrayValueRepository;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\{
    Acl,
    AclManager,
    Select\SelectBuilderFactory,
    DataManager,
    InjectableFactory,
    Utils\Metadata,
    Utils\Config,
    Utils\Language,
    Utils\FieldUtil,
    Utils\Log,
};

use Espo\Entities\User;
use Espo\Entities\Preferences;
use Espo\Entities\PhoneNumber;
use Espo\Entities\ArrayValue;

use Espo\ORM\{
    EntityManager,
    Repository\RDBRepository,
    Entity,
};

use stdClass;
use Throwable;

class App
{
    private $config;

    private $entityManager;

    private $metadata;

    private $acl;

    private $aclManager;

    private $dataManager;

    private $selectBuilderFactory;

    private $injectableFactory;

    private $settingsService;

    private $user;

    private $preferences;

    private $fieldUtil;

    private $log;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        Metadata $metadata,
        Acl $acl,
        AclManager $aclManager,
        DataManager $dataManager,
        SelectBuilderFactory $selectBuilderFactory,
        InjectableFactory $injectableFactory,
        SettingsService $settingsService,
        User $user,
        Preferences $preferences,
        FieldUtil $fieldUtil,
        Log $log
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->dataManager = $dataManager;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->injectableFactory = $injectableFactory;
        $this->settingsService = $settingsService;
        $this->user = $user;
        $this->preferences = $preferences;
        $this->fieldUtil = $fieldUtil;
        $this->log = $log;
    }

    public function getUserData(): array
    {
        $preferencesData = $this->preferences->getValueMap();

        $this->filterPreferencesData($preferencesData);

        $user = $this->user;

        if (!$user->has('teamsIds')) {
            $user->loadLinkMultipleField('teams');
        }

        if ($user->isPortal()) {
            $user->loadAccountField();
            $user->loadLinkMultipleField('accounts');
        }

        $settings = $this->settingsService->getConfigData();

        if ($user->get('dashboardTemplateId')) {
            $dashboardTemplate = $this->entityManager
                ->getEntity('DashboardTemplate', $user->get('dashboardTemplateId'));

            if ($dashboardTemplate) {
                $settings->forcedDashletsOptions = $dashboardTemplate->get('dashletsOptions') ?? (object) [];
                $settings->forcedDashboardLayout = $dashboardTemplate->get('layout') ?? [];
            }
        }

        $language = Language::detectLanguage($this->config, $this->preferences);

        $auth2FARequired = false;

        if (
            $user->isRegular() &&
            $this->config->get('auth2FA') &&
            $this->config->get('auth2FAForced') &&
            !$user->get('auth2FA')
        ) {
            $auth2FARequired = true;
        }

        $appParams = [
            'maxUploadSize' => $this->getMaxUploadSize() / 1024.0 / 1024.0,
            'isRestrictedMode' => $this->config->get('restrictedMode'),
            'passwordChangeForNonAdminDisabled' => $this->config->get('authenticationMethod', 'Espo') !== 'Espo',
            'timeZoneList' => $this->metadata->get(['entityDefs', 'Settings', 'fields', 'timeZone', 'options'], []),
            'auth2FARequired' => $auth2FARequired,
        ];

        foreach (($this->metadata->get(['app', 'appParams']) ?? []) as $paramKey => $item) {
            $className = $item['className'] ?? null;

            if (!$className) {
                continue;
            }

            try {
                $itemParams = $this->injectableFactory->create($className)->get();
            }
            catch (Throwable $e) {
                $this->log->error("appParam {$paramKey}: " . $e->getMessage());

                continue;
            }

            $appParams[$paramKey] = $itemParams;
        }

        return [
            'user' => $this->getUserDataForFrontend(),
            'acl' => $this->getAclDataForFrontend(),
            'preferences' => $preferencesData,
            'token' => $this->user->get('token'),
            'settings' => $settings,
            'language' => $language,
            'appParams' => $appParams,
        ];
    }

    private function getUserDataForFrontend()
    {
        $user = $this->user;

        $emailAddressData = $this->getEmailAddressData();

        $data = $user->getValueMap();

        $data->emailAddressList = $emailAddressData->emailAddressList;
        $data->userEmailAddressList = $emailAddressData->userEmailAddressList;

        unset($data->authTokenId);
        unset($data->password);

        $forbiddenAttributeList = $this->acl->getScopeForbiddenAttributeList('User');

        $isPortal = $user->isPortal();

        foreach ($forbiddenAttributeList as $attribute) {
            if ($attribute === 'type') {
                continue;
            }

            if ($isPortal) {
                if (in_array($attribute, ['contactId', 'contactName', 'accountId', 'accountsIds'])) {
                    continue;
                }
            }
            else {
                if (in_array($attribute, ['teamsIds', 'defaultTeamId', 'defaultTeamName'])) {
                    continue;
                }
            }

            unset($data->$attribute);
        }

        return $data;
    }

    private function getAclDataForFrontend()
    {
        $data = $this->acl->getMapData();

        if (!$this->user->isAdmin()) {
            $data = unserialize(serialize($data));

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

    private function getEmailAddressData()
    {
        $user = $this->user;

        $emailAddressList = [];
        $userEmailAddressList = [];

        $emailAddressCollection = $this->entityManager
            ->getRDBRepository('User')
            ->getRelation($user, 'emailAddresses')
            ->find();

        foreach ($emailAddressCollection as $emailAddress) {
            if ($emailAddress->get('invalid')) {
                continue;
            }

            $userEmailAddressList[] = $emailAddress->get('name');

            if ($user->get('emailAddress') === $emailAddress->get('name')) {
                continue;
            }

            $emailAddressList[] = $emailAddress->get('name');
        }

        if ($user->get('emailAddress')) {
            array_unshift($emailAddressList, $user->get('emailAddress'));
        }

        $entityManager = $this->entityManager;

        $teamIdList = $user->getLinkMultipleIdList('teams');

        $groupEmailAccountPermission = $this->acl->get('groupEmailAccountPermission');

        if ($groupEmailAccountPermission && $groupEmailAccountPermission !== 'no') {
            if ($groupEmailAccountPermission === 'team') {
                if (count($teamIdList)) {
                    $inboundEmailList = $entityManager
                        ->getRDBRepository('InboundEmail')
                        ->where([
                            'status' => 'Active',
                            'useSmtp' => true,
                            'smtpIsShared' => true,
                            'teamsMiddle.teamId' => $teamIdList,
                        ])
                        ->join('teams')
                        ->distinct()
                        ->find();

                    foreach ($inboundEmailList as $inboundEmail) {
                        if (!$inboundEmail->get('emailAddress')) {
                            continue;
                        }

                        $emailAddressList[] = $inboundEmail->get('emailAddress');
                    }
                }
            }
            else if ($groupEmailAccountPermission === 'all') {
                $inboundEmailList = $entityManager
                    ->getRDBRepository('InboundEmail')
                    ->where([
                        'status' => 'Active',
                        'useSmtp' => true,
                        'smtpIsShared' => true,
                    ])
                    ->find();

                foreach ($inboundEmailList as $inboundEmail) {
                    if (!$inboundEmail->get('emailAddress')) {
                        continue;
                    }

                    $emailAddressList[] = $inboundEmail->get('emailAddress');
                }
            }
        }

        return (object) [
            'emailAddressList' => $emailAddressList,
            'userEmailAddressList' => $userEmailAddressList,
        ];
    }

    private function getMaxUploadSize()
    {
        $maxSize = 0;

        $postMaxSize = $this->convertPHPSizeToBytes(ini_get('post_max_size'));

        if ($postMaxSize > 0) {
            $maxSize = $postMaxSize;
        }

        $attachmentUploadMaxSize = $this->config->get('attachmentUploadMaxSize');

        if ($attachmentUploadMaxSize && (!$maxSize || $attachmentUploadMaxSize < $maxSize)) {
            $maxSize = $attachmentUploadMaxSize;
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

        $suffix = substr($size, -1);
        $value = (int) substr($size, 0, -1);

        switch (strtoupper($suffix)) {
            case 'P':
                $value *= 1024;
            case 'T':
                $value *= 1024;
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;

                break;
        }

        return $value;
    }

    public function jobClearCache()
    {
        $this->dataManager->clearCache();
    }

    public function jobRebuild()
    {
        $this->dataManager->rebuild();
    }

    /**
     * @todo Remove in 6.0.
     */
    public function jobPopulatePhoneNumberNumeric()
    {
        $numberList = $this->entityManager
            ->getRDBRepository('PhoneNumber')
            ->find();

        foreach ($numberList as $number) {
            $this->entityManager->saveEntity($number);
        }
    }

    /**
     * @todo Remove in 6.0. Move to another place. CLI command.
     */
    public function jobPopulateArrayValues()
    {
        $scopeList = array_keys($this->metadata->get(['scopes']));

        $query = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('ArrayValue')
            ->build();

        $this->entityManager
            ->getQueryExecutor()
            ->execute($query);

        foreach ($scopeList as $scope) {
            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            if ($this->metadata->get(['scopes', $scope, 'disabled'])) {
                continue;
            }

            $attributeList = [];

            $entityDefs = $this->entityManager
                ->getDefs()
                ->getEntity($scope);

            foreach ($entityDefs->getAttributeList() as $attributeDefs) {
                $attribute = $attributeDefs->getName();
                $type = $attributeDefs->getType();

                if ($type !== Entity::JSON_ARRAY) {
                    continue;
                }

                if (!$attributeDefs->getParam('storeArrayValues')) {
                    continue;
                }

                if (!$attributeDefs->getParam('notStorable')) {
                    continue;
                }

                $attributeList[] = $attribute;
            }

            $select = ['id'];

            $orGroup = [];

            foreach ($attributeList as $attribute) {
                $select[] = $attribute;

                $orGroup[$attribute . '!='] = null;
            }

            $repository = $this->entityManager->getRepository($scope);

            if (!$repository instanceof RDBRepository) {
                continue;
            }

            if (!count($attributeList)) {
                continue;
            }

            $query = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->from($scope)
                ->select($select)
                ->where([
                    'OR' => $orGroup,
                ])
                ->build();

            $sth = $this->entityManager
                ->getQueryExecutor()
                ->execute($query);

            while ($dataRow = $sth->fetch()) {
                $entity = $this->entityManager->getEntityFactory()->create($scope);

                if (!$entity instanceof CoreEntity) {
                    continue;
                }

                $entity->set($dataRow);
                $entity->setAsFetched();

                foreach ($attributeList as $attribute) {
                    $this->getArrayValueRepository()->storeEntityAttribute($entity, $attribute, true);
                }
            }
        }
    }

    /**
     * @todo Remove in 6.0. Move to another place. CLI command.
     */
    public function jobPopulateOptedOutPhoneNumbers()
    {
        $entityTypeList = ['Contact', 'Lead'];

        foreach ($entityTypeList as $entityType) {
            $entityList = $this->entityManager
                ->getRDBRepository($entityType)
                ->where([
                    'doNotCall' => true,
                    'phoneNumber!=' => null,
                ])
                ->select(['id', 'phoneNumber'])
                ->find();

            foreach ($entityList as $entity) {
                $phoneNumber = $entity->get('phoneNumber');

                if (!$phoneNumber) {
                    continue;
                }

                $phoneNumberEntity = $this->getPhoneNumberRepository()->getByNumber($phoneNumber);

                if (!$phoneNumberEntity) {
                    continue;
                }

                $phoneNumberEntity->set('optOut', true);

                $this->entityManager->saveEntity($phoneNumberEntity);
            }
        }
    }

    private function filterPreferencesData(stdClass $data)
    {
        $passwordFieldList = $this->fieldUtil->getFieldByTypeList('Preferences', 'password');

        foreach ($passwordFieldList as $field) {
            unset($data->$field);
        }
    }

    private function getPhoneNumberRepository(): PhoneNumberRepository
    {
        /** @var PhoneNumberRepository */
        return $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);
    }

    private function getArrayValueRepository(): ArrayValueRepository
    {
        /** @var ArrayValueRepository */
        return $this->entityManager->getRepository(ArrayValue::ENTITY_TYPE);
    }
}
