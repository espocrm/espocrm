<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\OAuthServer\League;

use Espo\Core\Exceptions\Error;
use Espo\Core\Session\Session;
use DateInterval;
use Espo\Tools\OAuthServer\League\Repositories\AccessTokenRepository;
use Espo\Tools\OAuthServer\League\Repositories\AuthCodeRepository;
use Espo\Tools\OAuthServer\League\Repositories\ClientRepository;
use Espo\Tools\OAuthServer\League\Repositories\RefreshTokenRepository;
use Espo\Tools\OAuthServer\League\Repositories\ScopeRepository;
use Exception;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;

class AuthorizationServerFactory
{
    public function __construct(
        private ClientRepository $clientRepository,
        private AccessTokenRepository $accessTokenRepository,
        private ScopeRepository $scopeRepository,
        private AuthCodeRepository $authCodeRepository,
        private RefreshTokenRepository $refreshTokenRepository,
    ) {}

    /**
     * @throws Error
     */
    public function create(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            clientRepository: $this->clientRepository,
            accessTokenRepository: $this->accessTokenRepository,
            scopeRepository: $this->scopeRepository,
            privateKey: '',
            encryptionKey: '', // @todo
        );

        try {
            $grant = new AuthCodeGrant(
                authCodeRepository: $this->authCodeRepository,
                refreshTokenRepository: $this->refreshTokenRepository,
                authCodeTTL: new DateInterval('PT10M'),
            );
        } catch (Exception $e) {
            throw new Error("Error occurred.", previous: $e);
        }

        $server->enableGrantType(
            grantType: $grant,
            accessTokenTTL: new DateInterval('PT1H'),
        );

        return $server;
    }
}
