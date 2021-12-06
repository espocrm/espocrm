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

use Espo\Core\{
    Acl,
    Container,
    Utils\Metadata,
};

use Espo\Entities\User;

class Language
{
    protected $metadata;

    protected $acl;

    protected $user;

    protected $container;

    public function __construct(
        Metadata $metadata,
        Acl $acl,
        User $user,
        Container $container
    ) {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->user = $user;
        $this->container = $container;
    }

    // TODO use proxy
    protected function getDefaultLanguage()
    {
        return $this->container->get('defaultLanguage');
    }

    protected function getLanguage()
    {
        return $this->container->get('language');
    }

    public function getDataForFrontend(bool $default = false)
    {
        if ($default) {
            $languageObj = $this->getDefaultLanguage();
        }
        else {
            $languageObj = $this->getLanguage();
        }

        $data = $languageObj->getAll();

        if ($this->user->isSystem()) {
            unset($data['Global']['scopeNames']);
            unset($data['Global']['scopeNamesPlural']);
            unset($data['Global']['dashlets']);
            unset($data['Global']['links']);
            unset($data['Global']['fields']);
            unset($data['Global']['options']);

            foreach ($data as $k => $item) {
                if (
                    in_array($k, ['Global', 'User', 'Campaign']) ||
                    $this->metadata->get(['scopes', $k, 'languageIsGlobal'])
                ) {
                    continue;
                }

                unset($data[$k]);
            }

            unset($data['User']['fields']);
            unset($data['User']['links']);
            unset($data['User']['options']);
            unset($data['User']['filters']);
            unset($data['User']['presetFilters']);
            unset($data['User']['boolFilters']);
            unset($data['User']['tooltips']);

            unset($data['Campaign']['fields']);
            unset($data['Campaign']['links']);
            unset($data['Campaign']['options']);
            unset($data['Campaign']['tooltips']);
            unset($data['Campaign']['presetFilters']);
        }
        else {
            $scopeList = array_keys($this->metadata->get(['scopes'], []));

            foreach ($scopeList as $scope) {
                if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                    continue;
                }

                if ($this->metadata->get(['entityAcl', $scope, 'languageAclDisabled'])) {
                    continue;
                }

                if (!$this->acl->tryCheck($scope)) {
                    unset($data[$scope]);
                    unset($data['Global']['scopeNames'][$scope]);
                    unset($data['Global']['scopeNamesPlural'][$scope]);
                }
                else {
                    if (in_array($scope, ['EmailAccount', 'InboundEmail'])) {
                        continue;
                    }

                    foreach ($this->acl->getScopeForbiddenFieldList($scope) as $field) {
                        if (isset($data[$scope]['fields'])) {
                            unset($data[$scope]['fields'][$field]);
                        }

                        if (isset($data[$scope]['options'])) {
                            unset($data[$scope]['options'][$field]);
                        }

                        if (isset($data[$scope]['links'])) {
                            unset($data[$scope]['links'][$field]);
                        }
                    }
                }
            }

            if (!$this->user->isAdmin()) {
                unset($data['Admin']);
                unset($data['LayoutManager']);
                unset($data['EntityManager']);
                unset($data['FieldManager']);
                unset($data['Settings']);
                unset($data['ApiUser']);
                unset($data['DynamicLogic']);

                $data['Settings'] = [
                    'options' => [
                        'weekStart' => $languageObj->get(['Settings', 'options', 'weekStart']),
                    ],
                ];
                $data['Admin'] = [
                    'messages' => [
                        'userHasNoEmailAddress' => $languageObj
                            ->translate('userHasNoEmailAddress', 'messages', 'Admin'),
                    ],
                ];

                foreach (($this->metadata->get(['app', 'language', 'aclDependencies']) ?? []) as $target => $item) {
                    $targetArr = explode('.', $target);

                    $aclScope = $item['scope'] ?? null;;
                    $aclField = $item['field'] ?? null;

                    if (!$aclScope) {
                        continue;
                    }
                    if (!$this->acl->tryCheck($aclScope)) {
                        continue;
                    }

                    if ($aclField && in_array($aclField, $this->acl->getScopeForbiddenFieldList($aclScope))) {
                        continue;
                    }

                    $pointer =& $data;

                    foreach ($targetArr as $i => $k) {
                        if ($i === count($targetArr) - 1) {
                            $pointer[$k] = $languageObj->get($targetArr);
                            break;
                        }

                        if (!isset($pointer[$k])) {
                            $pointer[$k] = [];
                        }

                        $pointer =& $pointer[$k];
                    }
                }
            }
        }

        $data['User']['fields'] = $data['User']['fields'] ?? [];

        $data['User']['fields']['password'] = $languageObj->translate('password', 'fields', 'User');
        $data['User']['fields']['passwordConfirm'] = $languageObj->translate('passwordConfirm', 'fields', 'User');
        $data['User']['fields']['newPassword'] = $languageObj->translate('newPassword', 'fields', 'User');
        $data['User']['fields']['newPasswordConfirm'] = $languageObj->translate('newPasswordConfirm', 'fields', 'User');

        return $data;
    }
}
