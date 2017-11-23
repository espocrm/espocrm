<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\BadRequest;

class App extends \Espo\Core\Controllers\Base
{
    public function actionUser()
    {
        $preferences = $this->getPreferences()->getValues();
        unset($preferences['smtpPassword']);

        $user = $this->getUser();
        if (!$user->has('teamsIds')) {
            $user->loadLinkMultipleField('teams');
        }
        if ($user->get('isPortalUser')) {
            $user->loadAccountField();
            $user->loadLinkMultipleField('accounts');
        }

        $userData = $user->getValues();


        $userData['emailAddressList'] = $this->getEmailAddressList();

        $settings = (object)[];
        foreach ($this->getConfig()->get('userItems') as $item) {
            $settings->$item = $this->getConfig()->get($item);
        }

        unset($userData['authTokenId']);
        unset($userData['password']);

        $language = \Espo\Core\Utils\Language::detectLanguage($this->getConfig(), $this->getPreferences());

        return array(
            'user' => $userData,
            'acl' => $this->getAcl()->getMap(),
            'preferences' => $preferences,
            'token' => $this->getUser()->get('token'),
            'settings' => $settings,
            'language' => $language
        );
    }

    public function postActionDestroyAuthToken($params, $data)
    {
        $token = $data['token'];
        if (empty($token)) {
            throw new BadRequest();
        }

        $auth = new \Espo\Core\Utils\Auth($this->getContainer());
        return $auth->destroyAuthToken($token);
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

        $entityManager = $this->getContainer()->get('entityManager');

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
}
