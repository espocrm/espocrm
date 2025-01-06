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

namespace Espo\Tools\Oidc;

use Espo\Core\Authentication\Jwt\Exceptions\Invalid;
use Espo\Core\Authentication\Oidc\ConfigDataProvider;
use Espo\Core\Authentication\Oidc\Login as OidcLogin;
use Espo\Core\Authentication\Oidc\BackchannelLogout;
use Espo\Core\Authentication\Util\MethodProvider;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Json;

class Service
{
    public function __construct(
        private BackchannelLogout $backchannelLogout,
        private MethodProvider $methodProvider,
        private ConfigDataProvider $configDataProvider
    ) {}

    /**
     * @return array{
     *     clientId: non-empty-string,
     *     endpoint: non-empty-string,
     *     redirectUri: string,
     *     scopes: non-empty-array<string>,
     *     claims: ?string,
     *     prompt: 'none'|'login'|'consent'|'select_account',
     *     maxAge: ?int,
     * }
     * @throws Forbidden
     * @throws Error
     */
    public function getAuthorizationData(): array
    {
        if ($this->methodProvider->get() !== OidcLogin::NAME) {
            throw new Forbidden();
        }

        $clientId = $this->configDataProvider->getClientId();
        $endpoint = $this->configDataProvider->getAuthorizationEndpoint();
        $scopes = $this->configDataProvider->getScopes();
        $groupClaim = $this->configDataProvider->getGroupClaim();
        $redirectUri = $this->configDataProvider->getRedirectUri();

        if (!$clientId) {
            throw new Error("No client ID.");
        }

        if (!$endpoint) {
            throw new Error("No authorization endpoint.");
        }

        array_unshift($scopes, 'openid');

        $claims = null;

        if ($groupClaim) {
            $claims = Json::encode([
                'id_token' => [
                    $groupClaim => ['essential' => true],
                ],
            ]);
        }

        /** @var 'none'|'login'|'consent'|'select_account' $prompt
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $prompt = $this->configDataProvider->getAuthorizationPrompt();
        $maxAge = $this->configDataProvider->getAuthorizationMaxAge();

        return [
            'clientId' => $clientId,
            'endpoint' => $endpoint,
            'redirectUri' => $redirectUri,
            'scopes' => $scopes,
            'claims' => $claims,
            'prompt' => $prompt,
            'maxAge' => $maxAge,
        ];
    }

    /**
     * @throws Forbidden
     */
    public function backchannelLogout(string $rawToken): void
    {
        if ($this->methodProvider->get() !== OidcLogin::NAME) {
            throw new Forbidden();
        }

        try {
            $this->backchannelLogout->logout($rawToken);
        } catch (Invalid $e) {
            throw new Forbidden("OIDC logout: Invalid JWT. " . $e->getMessage());
        }
    }
}
