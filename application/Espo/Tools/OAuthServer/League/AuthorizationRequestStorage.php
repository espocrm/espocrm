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

use Espo\Core\Session\Session;
use Espo\Tools\OAuthServer\League\Entities\AuthCodeEntity;
use Espo\Tools\OAuthServer\League\Entities\ClientEntity;
use Espo\Tools\OAuthServer\League\Entities\ScopeEntity;
use InvalidArgumentException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use RuntimeException;

class AuthorizationRequestStorage
{
    public function __construct(
        private Session $session,
    ) {}

    public function store(string $clientId, AuthorizationRequestInterface $request): void
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$request instanceof AuthorizationRequest) {
            throw new InvalidArgumentException("Unsupported authorization request instance.");
        }

        $this->session->set(self::composeSessionKey($clientId), serialize($request));
    }

    public function get(string $clientId, bool $clear = false): ?AuthorizationRequest
    {
        $key = self::composeSessionKey($clientId);

        $raw = $this->session->get($key);

        if (!$raw) {
            return null;
        }

        if ($clear) {
            $this->session->clear($key);
        }

        $request = unserialize($raw, [
            'allowed_classes' => [
                AuthorizationRequest::class,
                ClientEntity::class,
                ScopeEntity::class,
                AuthCodeEntity::class,
            ]
        ]);

        if (!$request instanceof AuthorizationRequest) {
            throw new RuntimeException("Unserialization error.");
        }

        return $request;
    }

    public static function composeSessionKey(string $clientId): string
    {
        return "oAuthServerAuthorizeRequest_" . $clientId;
    }
}
