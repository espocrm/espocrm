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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\ORM\Entity;

use Espo\Core\{
    ApplicationState,
    Acl,
    InjectableFactory,
    ORM\EntityManager,
    Utils\Metadata,
    Utils\FieldUtil,
    Utils\Config,
    Utils\Config\ConfigWriter,
    DataManager,
    FieldValidation\FieldValidationManager,
    Currency\DatabasePopulator as CurrencyDatabasePopulator,
};

class Settings
{
    protected $applicationState;
    protected $config;
    protected $configWriter;
    protected $fieldUtil;
    protected $metadata;
    protected $acl;
    protected $entityManager;
    protected $dataManager;
    protected $fieldValidationManager;
    protected $injectableFactory;

    public function __construct(
        ApplicationState $applicationState,
        Config $config,
        ConfigWriter $configWriter,
        Metadata $metadata,
        Acl $acl,
        FieldUtil $fieldUtil,
        EntityManager $entityManager,
        DataManager $dataManager,
        FieldValidationManager $fieldValidationManager,
        InjectableFactory $injectableFactory
    ) {
        $this->applicationState = $applicationState;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->fieldUtil = $fieldUtil;
        $this->entityManager = $entityManager;
        $this->dataManager = $dataManager;
        $this->fieldValidationManager = $fieldValidationManager;
        $this->injectableFactory = $injectableFactory;
    }

    public function getConfigData()
    {
        $data = $this->config->getAllData();

        $user = $this->applicationState->getUser();

        $ignoreItemList = [];

        foreach ($this->getSystemOnlyItemList() as $item) {
            $ignoreItemList[] = $item;
        }

        if (!$user->isAdmin() || $user->isSystem()) {
            foreach ($this->getAdminOnlyItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        if ($user->isSystem()) {
            foreach ($this->getUserOnlyItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        if ($this->config->get('restrictedMode') && !$user->isSuperAdmin()) {
            foreach ($this->config->getSuperAdminOnlySystemItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        if ($this->applicationState->isPortal()) {
            $portal = $this->applicationState->getPortal();

            $this->entityManager->getRepository('Portal')->loadUrlField($portal);

            $data->siteUrl = $portal->get('url');
        }

        foreach ($ignoreItemList as $item) {
            unset($data->$item);
        }

        if ($user->isSystem()) {
            $globalItemList = $this->getGlobalItemList();

            foreach (get_object_vars($data) as $item => $value) {
                if (!in_array($item, $globalItemList)) {
                    unset($data->$item);
                }
            }
        }

        if (!$user->isAdmin() && !$user->isSystem()) {
            $entityTypeListParamList = [
                'tabList',
                'quickCreateList',
                'globalSearchEntityList',
                'assignmentEmailNotificationsEntityList',
                'assignmentNotificationsEntityList',
                'calendarEntityList',
                'streamEmailNotificationsEntityList',
                'activitiesEntityList',
                'historyEntityList',
                'streamEmailNotificationsTypeList',
                'emailKeepParentTeamsEntityList',
            ];

            $scopeList = array_keys($this->metadata->get(['entityDefs'], []));

            foreach ($scopeList as $scope) {
                if (!$this->metadata->get(['scopes', $scope, 'acl'])) {
                    continue;
                }

                if ($this->acl->check($scope)) {
                    continue;
                }

                foreach ($entityTypeListParamList as $param) {
                    $list = $data->$param ?? [];

                    foreach ($list as $i => $item) {
                        if ($item === $scope) {
                            unset($list[$i]);
                        }
                    }

                    $list = array_values($list);

                    $data->$param = $list;
                }
            }
        }

        if (
            ($this->config->get('outboundEmailFromAddress') || $this->config->get('internalSmtpServer'))
            &&
            !$this->config->get('passwordRecoveryDisabled')
        ) {
            $data->passwordRecoveryEnabled = true;
        }

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if ($fieldParams['type'] === 'password') {
                unset($data->$field);
            }
        }

        $this->filterData($data);

        return $data;
    }

    public function setConfigData($data)
    {
        $user = $this->applicationState->getUser();

        if (!$user->isAdmin()) {
            throw new Forbidden();
        }

        $ignoreItemList = [];

        foreach ($this->getSystemOnlyItemList() as $item) {
            $ignoreItemList[] = $item;
        }

        if ($this->config->get('restrictedMode') && !$user->isSuperAdmin()) {
            foreach ($this->config->getSuperAdminOnlyItemList() as $item) {
                $ignoreItemList[] = $item;
            }

            foreach ($this->config->getSuperAdminOnlySystemItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        foreach ($ignoreItemList as $item) {
            unset($data->$item);
        }

        $entity = $this->entityManager->getEntity('Settings');

        $entity->set($data);

        $this->processValidation($entity, $data);

        if (
            (isset($data->useCache) && $data->useCache !== $this->config->get('useCache'))
        ) {
            $this->dataManager->clearCache();
        }

        $this->configWriter->setMultiple(
            get_object_vars($data)
        );

        $this->configWriter->save();

        if (isset($data->personNameFormat)) {
            $this->dataManager->clearCache();
        }

        if (isset($data->defaultCurrency) || isset($data->baseCurrency) || isset($data->currencyRates)) {
            $this->populateDatabaseWithCurrencyRates();
        }
    }

    protected function populateDatabaseWithCurrencyRates()
    {
        $this->injectableFactory->create(CurrencyDatabasePopulator::class)->process();
    }

    protected function filterData($data)
    {
        $user = $this->applicationState->getUser();

        if (empty($data->useWebSocket)) {
            unset($data->webSocketUrl);
        }

        if ($user->isSystem()) {
            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        if (
            !$this->acl->checkScope('Email', 'create')
            ||
            !$this->config->get('outboundEmailIsShared')
        ) {
            unset($data->outboundEmailFromAddress);
            unset($data->outboundEmailFromName);
            unset($data->outboundEmailBccAddress);
        }
    }

    public function getAdminOnlyItemList()
    {
        $itemList = $this->config->getAdminOnlyItemList();

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);
        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['onlyAdmin'])) {
                foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    public function getUserOnlyItemList()
    {
        $itemList = $this->config->getUserOnlyItemList();

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['onlyUser'])) {
                foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    public function getSystemOnlyItemList()
    {
        $itemList = $this->config->getSystemOnlyItemList();

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['onlySystem'])) {
                foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    public function getGlobalItemList()
    {
        $itemList = $this->config->get('globalItems', []);

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['global'])) {
                foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    protected function processValidation(Entity $entity, $data)
    {
        $fieldList = $this->fieldUtil->getEntityTypeFieldList('Settings');

        foreach ($fieldList as $field) {
            if (!$this->isFieldSetInData($data, $field)) {
                continue;
            }

            $this->processValidationField($entity, $field, $data);
        }
    }

    protected function processValidationField(Entity $entity, string $field, $data)
    {
        $fieldType = $this->fieldUtil->getEntityTypeFieldParam('Settings', $field, 'type');
        $validationList = $this->metadata->get(['fields', $fieldType, 'validationList'], []);
        $mandatoryValidationList = $this->metadata->get(['fields', $fieldType, 'mandatoryValidationList'], []);

        foreach ($validationList as $type) {
            $value = $this->fieldUtil->getEntityTypeFieldParam('Settings', $field, $type);

            if (is_null($value) && !in_array($type, $mandatoryValidationList)) {
                continue;
            }

            if (!$this->fieldValidationManager->check($entity, $field, $type, $data)) {
                throw new BadRequest("Not valid data. Field: '{$field}', type: {$type}.");
            }
        }
    }

    protected function isFieldSetInData($data, $field)
    {
        $attributeList = $this->fieldUtil->getActualAttributeList('Settings', $field);

        $isSet = false;

        foreach ($attributeList as $attribute) {
            if (property_exists($data, $attribute)) {
                $isSet = true;

                break;
            }
        }

        return $isSet;
    }
}
