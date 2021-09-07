<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Authentication\Login;

use Espo\Core\Exceptions\Error;

use Espo\Core\{
    ORM\EntityManager,
    Api\Request,
    Utils\Config,
    Utils\Language,
    Utils\Log,
    Authentication\Login,
    Authentication\LoginData,
    Authentication\Result,
    Authentication\AuthToken\AuthToken,
    Authentication\FailReason,
};

use Exception;

class SAML implements Login
{
    use ExternalLoginTrait;

    private $simplesaml_config_dir = "vendor/simplesaml/simplesaml/config";

    private $samlClient;

    private $isPortal;

    private $config;

    private $entityManager;

    private $language;

    private $log;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        Language $defaultLanguage,
        Log $log,
        bool $isPortal = false
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->language = $defaultLanguage;
        $this->log = $log;

        $this->isPortal = $isPortal;
    }

    private function getSamlClient()
    {
        if (!isset($this->samlClient)) {
            try {
                $this->samlClient = new \SimpleSAML\Auth\Simple('default-sp');
            } catch (\Exception $e) {
                $GLOBALS['log']->error('SimpleSAML error: ' . $e->getMessage());
                throw $e;
            }
        }

        return $this->samlClient;
    }

    public function login(LoginData $loginData, Request $request): Result
    {
        $username = $loginData->getUsername();
        $authToken = $loginData->getAuthToken();

        $isPortal = $this->isPortal;

        if ($authToken) {
            $user = $this->loginByToken($username, $authToken);

            if ($user) {
                return Result::success($user);
            } else {
                return Result::fail(FailReason::WRONG_CREDENTIALS);
            }
        }

        $as = $this->getSamlClient();
        if (!$as->isAuthenticated()) {
            $this->log->debug('SAML: User not authenticated');
            return Result::fail();
        }
        $attrs = $as->getAttributes();
        if (!isset($attrs['uid'][0])) {
            $this->log->error('SAML: No uid: '.print_r($attrs, true));
            return Result::fail();
        }
        $username = $attrs['uid'][0];

        $user = $this->entityManager
            ->getRDBRepository('User')
            ->where([
                'userName' => $username,
                'type!=' => ['api', 'system'],
            ])
            ->findOne();

        if (!isset($user)) {
            if (false) { // !$this->config->get('samlCreateEspoUser');

                $this->log->warning(
                    "SAML: Authentication success for user {$username}, but user is not created in EspoCRM."
                );

                return Result::fail(FailReason::USER_NOT_FOUND);
            }

            $data = array(
                'userName' => $username
            );
            if (isset($attrs['givenName'])) {
                $data['firstName'] = $attrs['givenName'][0];
            }
            if (isset($attrs['surname'])) {
                $data['lastName'] = $attrs['surname'][0];
            }
            $user = $this->createUser($data, $isPortal);
        }

        return Result::success($user);
    }

    public function authDetails(): array {
        $as = $this->getSamlClient();

        return [
            'method' => 'redirect',
            'loginUrl' =>  $as->getLoginURL('/'),
            'logoutUrl' =>  $as->getLogoutURL('/'),
        ];
    }
}
