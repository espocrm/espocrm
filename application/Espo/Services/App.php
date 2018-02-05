<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;

use \Espo\Core\Utils\Util;

class App extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependency('preferences');
        $this->addDependency('acl');
        $this->addDependency('container');
        $this->addDependency('entityManager');
        $this->addDependency('metadata');
        $this->addDependency('selectManagerFactory');
    }

    protected function getPreferences()
    {
        return $this->getInjection('preferences');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    public function getUserData()
    {
        $preferencesData = $this->getPreferences()->getValueMap();
        unset($preferencesData->smtpPassword);

        $user = $this->getUser();
        if (!$user->has('teamsIds')) {
            $user->loadLinkMultipleField('teams');
        }
        if ($user->get('isPortalUser')) {
            $user->loadAccountField();
            $user->loadLinkMultipleField('accounts');
        }

        $userData = $user->getValueMap();

        $userData->emailAddressList = $this->getEmailAddressList();

        $settings = (object)[];
        foreach ($this->getConfig()->get('userItems') as $item) {
            $settings->$item = $this->getConfig()->get($item);
        }

        if ($this->getUser()->isAdmin()) {
            foreach ($this->getConfig()->get('adminItems') as $item) {
                if ($this->getConfig()->has($item)) {
                    $settings->$item = $this->getConfig()->get($item);
                }
            }
        }

        $settingsFieldDefs = $this->getInjection('metadata')->get('entityDefs.Settings.fields', []);
        foreach ($settingsFieldDefs as $field => $d) {
            if ($d['type'] === 'password') {
                unset($settings->$field);
            }
        }

        unset($userData->authTokenId);
        unset($userData->password);

        $language = \Espo\Core\Utils\Language::detectLanguage($this->getConfig(), $this->getPreferences());

        return [
            'user' => $userData,
            'acl' => $this->getAcl()->getMap(),
            'preferences' => $preferencesData,
            'token' => $this->getUser()->get('token'),
            'settings' => $settings,
            'language' => $language,
            'appParams' => [
                'maxUploadSize' => $this->getMaxUploadSize() / 1024.0 / 1024.0,
                'templateEntityTypeList' => $this->getTemplateEntityTypeList()
            ]
        ];
    }

    protected function getEmailAddressList() {
        $user = $this->getUser();

        $emailAddressList = [];
        foreach ($user->get('emailAddresses') as $emailAddress) {
            if ($emailAddress->get('invalid')) continue;
            if ($user->get('emailAddrses') === $emailAddress->get('name')) continue;
            $emailAddressList[] = $emailAddress->get('name');
        }
        if ($user->get('emailAddrses')) {
            array_unshift($emailAddressList, $user->get('emailAddrses'));
        }

        $entityManager = $this->getEntityManager();

        $teamIdList = $user->getLinkMultipleIdList('teams');
        $groupEmailAccountPermission = $this->getAcl()->get('groupEmailAccountPermission');
        if ($groupEmailAccountPermission && $groupEmailAccountPermission !== 'no') {
            if ($groupEmailAccountPermission === 'team') {
                if (count($teamIdList)) {
                    $selectParams = [
                        'whereClause' => [
                            'status' => 'Active',
                            'useSmtp' => true,
                            'smtpIsShared' => true,
                            'teamsMiddle.teamId' => $teamIdList
                        ],
                        'joins' => ['teams'],
                        'distinct' => true
                    ];
                    $inboundEmailList = $entityManager->getRepository('InboundEmail')->find($selectParams);
                    foreach ($inboundEmailList as $inboundEmail) {
                        if (!$inboundEmail->get('emailAddress')) continue;
                        $emailAddressList[] = $inboundEmail->get('emailAddress');
                    }
                }
            } else if ($groupEmailAccountPermission === 'all') {
                $selectParams = [
                    'whereClause' => [
                        'status' => 'Active',
                        'useSmtp' => true,
                        'smtpIsShared' => true
                    ]
                ];
                $inboundEmailList = $entityManager->getRepository('InboundEmail')->find($selectParams);
                foreach ($inboundEmailList as $inboundEmail) {
                    if (!$inboundEmail->get('emailAddress')) continue;
                    $emailAddressList[] = $inboundEmail->get('emailAddress');
                }
            }
        }

        return $emailAddressList;
    }

    private function getMaxUploadSize()
    {
        $maxSize = 0;

        $postMaxSize = $this->convertPHPSizeToBytes(ini_get('post_max_size'));
        if ($postMaxSize > 0) {
            $maxSize = $postMaxSize;
        }
        $attachmentUploadMaxSize = $this->getConfig()->get('attachmentUploadMaxSize');
        if ($attachmentUploadMaxSize && (!$maxSize || $attachmentUploadMaxSize < $maxSize)) {
            $maxSize = $attachmentUploadMaxSize;
        }

        return $maxSize;
    }

    private function convertPHPSizeToBytes($size)
    {
        if (is_numeric($size)) return $size;

        $suffix = substr($size, -1);
        $value = substr($size, 0, -1);
        switch(strtoupper($suffix)) {
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

    protected function getTemplateEntityTypeList()
    {
        if (!$this->getAcl()->checkScope('Template')) {
            return [];
        }

        $list = [];

        $selectManager = $this->getInjection('selectManagerFactory')->create('Template');

        $selectParams = $selectManager->getEmptySelectParams();
        $selectManager->applyAccess($selectParams);

        $templateList = $this->getEntityManager()->getRepository('Template')
            ->select(['entityType'])
            ->groupBy(['entityType'])
            ->find($selectParams);

        foreach ($templateList as $template) {
            $list[] = $template->get('entityType');
        }

        return $list;
    }

    public function jobClearCache()
    {
        $this->getInjection('container')->get('dataManager')->clearCache();
    }

    public function jobRebuild()
    {
        $this->getInjection('container')->get('dataManager')->rebuild();
    }

}
