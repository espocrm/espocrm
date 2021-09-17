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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Authentication\LDAP\Utils as LDAPUtils;
use Espo\Core\Authentication\LDAP\Client as LDAPClient;
use Espo\Core\Api\Request;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;

use Espo\Services\Settings as Service;

use Espo\Entities\User;

use stdClass;

class Settings
{
    private $metadata;

    private $service;

    private $user;

    private $config;

    public function __construct(
        Metadata $metadata,
        Service $service,
        User $user,
        Config $config
    ) {
        $this->metadata = $metadata;
        $this->service = $service;
        $this->user = $user;
        $this->config = $config;
    }

    public function getActionRead(): stdClass
    {
        return $this->getConfigData();
    }

    public function putActionUpdate(Request $request): stdClass
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $data = $request->getParsedBody();

        $this->service->setConfigData($data);

        return $this->getConfigData();
    }

    public function postActionTestLdapConnection(Request $request): bool
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $data = $request->getParsedBody();

        if (!isset($data->password)) {
            $data->password = $this->config->get('ldapPassword');
        }

        $ldapUtils = new LDAPUtils();

        $options = $ldapUtils->normalizeOptions(
            get_object_vars($data)
        );

        $ldapClient = new LDAPClient($options);

        // An exception thrown if no connection.
        $ldapClient->bind();

        return true;
    }

    private function getConfigData(): stdClass
    {
        $data = $this->service->getConfigData();

        $data->jsLibs = $this->metadata->get(['app', 'jsLibs']);

        unset($data->loginView);

        $loginView = $this->metadata->get(['clientDefs', 'App', 'loginView']);

        if ($loginView) {
            $data->loginView = $loginView;
        }

        return $data;
    }
}
