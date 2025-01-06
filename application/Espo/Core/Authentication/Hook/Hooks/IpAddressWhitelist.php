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

namespace Espo\Core\Authentication\Hook\Hooks;

use Espo\Core\Api\Request;
use Espo\Core\Api\Util;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\Core\Authentication\Hook\OnLogin;
use Espo\Core\Authentication\Result;
use Espo\Core\Authentication\Util\IpAddressUtil;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;

class IpAddressWhitelist implements OnLogin
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private Util $util,
        private Config $config,
        private IpAddressUtil $ipAddressUtil
    ) {}

    public function process(Result $result, AuthenticationData $data, Request $request): void
    {
        if (!$this->configDataProvider->ipAddressCheck()) {
            return;
        }

        $ipAddress = $this->util->obtainIpFromRequest($request);

        if (
            $ipAddress &&
            $this->ipAddressUtil->isInWhitelist($ipAddress, $this->configDataProvider->getIpAddressWhitelist())
        ) {
            return;
        }

        $user = $result->getUser();

        if ($user && $user->isPortal()) {
            return;
        }

        if ($user && $user->isSuperAdmin() && $this->config->get('restrictedMode')) {
            return;
        }

        if (
            $user &&
            in_array($user->getId(), $this->configDataProvider->getIpAddressCheckExcludedUserIdList())
        ) {
            return;
        }

        $username = $user ? $user->getUserName() : '?';

        throw new Forbidden("Not allowed IP address $ipAddress, user: $username.");
    }
}
