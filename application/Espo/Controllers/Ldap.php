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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Authentication\Ldap\Utils as LDAPUtils;
use Espo\Core\Authentication\Ldap\Client as LDAPClient;
use Espo\Core\Api\Request;
use Espo\Core\Utils\Config;
use Espo\Entities\User;

use Laminas\Ldap\Exception\LdapException;


class Ldap
{
    private User $user;
    private Config $config;

    public function __construct(
        User $user,
        Config $config
    ) {
        $this->user = $user;
        $this->config = $config;
    }

    /**
     * @throws Forbidden
     * @throws LdapException
     */
    public function postActionTestConnection(Request $request): bool
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
}
