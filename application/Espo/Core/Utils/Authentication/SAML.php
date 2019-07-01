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

namespace Espo\Core\Utils\Authentication;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Auth;

class SAML extends Base
{
    private $simplesaml_config_dir = "vendor/simplesaml/simplesaml/config";

    private $samlClient;

    private function getSamlClient()
    {
        if (!isset($this->samlClient)) {
            try {
                $this->samlClient = new \SimpleSAML\Auth\Simple('default-sp');
            } catch (\Exception $e) {
                $GLOBALS['log']->error('SimpleSAML error: ' . $e->getMessage());
            }
        }

        return $this->samlClient;
    }

    /**
     * SAML login
     *
     * @param  string $username
     * @param  string $password
     * @param  \Espo\Entities\AuthToken $authToken
     *
     * @return \Espo\Entities\User | null
     */
    public function login($username, $password, \Espo\Entities\AuthToken $authToken = null, $params = [], $request)
    {
        if ($authToken) {
            return $this->loginByToken($username, $authToken);
        }

        $as = $this->getSamlClient();
        if (!$as->isAuthenticated()) {
            $GLOBALS['log']->error('SAML: User not authenticated');
            return;
        }
        $attrs = $as->getAttributes();
        if (!isset($attrs['uid'][0])) {
            return;
        }
        $username = $attrs['uid'][0];

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
                'type!=' => ['api', 'system']
            ),
        ));

        $isCreateUser = $this->getConfig()->get('samlCreateEspoUser');
        $isCreateUser = true;

        if (!isset($user) && $isCreateUser) {
            $data = array(
                'userName' => $username
                # TODO: map attributes?
            );
            $user = $this->createUser($data, !empty($params['isPortal']));
        }

        return $user;
    }

    public function authDetails() {
        $as = $this->getSamlClient();

        return array (
            'method' => 'redirect',
            'loginUrl' =>  $as->getLoginURL('/'),
            'logoutUrl' =>  $as->getLogoutURL('/'),
        );
    }
}
