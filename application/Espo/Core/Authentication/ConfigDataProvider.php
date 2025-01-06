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

namespace Espo\Core\Authentication;

use Espo\Core\Authentication\Login\MetadataParams;
use Espo\Core\Authentication\Logins\Espo;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;

class ConfigDataProvider
{
    private const FAILED_ATTEMPTS_PERIOD =  '60 seconds';
    private const FAILED_CODE_ATTEMPTS_PERIOD =  '5 minutes';
    private const MAX_FAILED_ATTEMPT_NUMBER = 10;

    public function __construct(private Config $config, private Metadata $metadata)
    {}

    /**
     * A period for max failed attempts checking.
     */
    public function getFailedAttemptsPeriod(): string
    {
        return $this->config->get('authFailedAttemptsPeriod', self::FAILED_ATTEMPTS_PERIOD);
    }

    /**
     * A period for max failed 2FA code attempts checking.
     */
    public function getFailedCodeAttemptsPeriod(): string
    {
        return $this->config->get('authFailedCodeAttemptsPeriod', self::FAILED_CODE_ATTEMPTS_PERIOD);
    }

    /**
     * Max failed log in attempts.
     */
    public function getMaxFailedAttemptNumber(): int
    {
        return $this->config->get('authMaxFailedAttemptNumber', self::MAX_FAILED_ATTEMPT_NUMBER);
    }

    /**
     * Auth token secret won't be created. Can be reasonable for a custom AuthTokenManager implementation.
     */
    public function isAuthTokenSecretDisabled(): bool
    {
        return (bool) $this->config->get('authTokenSecretDisabled');
    }

    /**
     * A maintenance mode. Only admin can log in.
     */
    public function isMaintenanceMode(): bool
    {
        return (bool) $this->config->get('maintenanceMode');
    }

    /**
     * Whether 2FA is enabled.
     */
    public function isTwoFactorEnabled(): bool
    {
        return (bool) $this->config->get('auth2FA');
    }

    /**
     * Allowed methods of 2FA.
     *
     * @return array<int, string>
     */
    public function getTwoFactorMethodList(): array
    {
        return $this->config->get('auth2FAMethodList') ?? [];
    }

    /**
     * A user won't be able to have multiple active auth tokens simultaneously.
     */
    public function preventConcurrentAuthToken(): bool
    {
        return (bool) $this->config->get('authTokenPreventConcurrent');
    }

    /**
     * A default authentication method.
     */
    public function getDefaultAuthenticationMethod(): string
    {
        return $this->config->get('authenticationMethod', Espo::NAME);
    }

    /**
     * Whether an authentication method can be defined by request itself (in a header).
     */
    public function authenticationMethodIsApi(string $authenticationMethod): bool
    {
        return (bool) $this->metadata->get(['authenticationMethods', $authenticationMethod, 'api']);
    }

    public function isAnotherUserDisabled(): bool
    {
        return (bool) $this->config->get('authAnotherUserDisabled');
    }

    public function isAuthLogDisabled(): bool
    {
        return (bool) $this->config->get('authLogDisabled');
    }

    public function isApiUserAuthLogDisabled(): bool
    {
        return (bool) $this->config->get('authApiUserLogDisabled');
    }

    /**
     * @return MetadataParams[]
     */
    public function getLoginMetadataParamsList(): array
    {
        $list = [];

        /** @var array<string, array<string, mixed>> $data */
        $data = $this->metadata->get(['authenticationMethods']) ?? [];

        foreach ($data as $method => $item) {
            $list[] = MetadataParams::fromRaw($method, $item);
        }

        return $list;
    }

    public function ipAddressCheck(): bool
    {
        return (bool) $this->config->get('authIpAddressCheck');
    }

    /**
     * @return string[]
     */
    public function getIpAddressWhitelist(): array
    {
        return $this->config->get('authIpAddressWhitelist') ?? [];
    }

    /**
     * @return string[]
     */
    public function getIpAddressCheckExcludedUserIdList(): array
    {
        return $this->config->get('authIpAddressCheckExcludedUsersIds') ?? [];
    }
}
