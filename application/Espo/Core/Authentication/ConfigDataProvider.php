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

namespace Espo\Core\Authentication;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
};

class ConfigDataProvider
{
    private const FAILED_ATTEMPTS_PERIOD =  '60 seconds';

    private const MAX_FAILED_ATTEMPT_NUMBER = 10;

    private $config;

    private $metadata;

    public function __construct(Config $config, Metadata $metadata)
    {
        $this->config = $config;
        $this->metadata = $metadata;
    }

    /**
     * A period for max failed attempts checking.
     */
    public function getFailedAttemptsPeriod(): string
    {
        return $this->config->get('authFailedAttemptsPeriod', self::FAILED_ATTEMPTS_PERIOD);
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
        return $this->config->get('authenticationMethod', 'Espo');
    }

    /**
     * Whether an authentication method can be defined by request itself (in a header).
     */
    public function authenticationMethodIsApi(string $authenticationMethod): bool
    {
        return (bool) $this->metadata->get(['authenticationMethods', $authenticationMethod, 'api']);
    }
}
