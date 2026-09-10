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

use Espo\Core\Utils\Config;
use DateInterval;
use DateMalformedIntervalStringException;
use RuntimeException;

class ConfigDataProvider
{
    private const string AUTHORIZATION_CODE_TTL = 'PT10M';
    private const string ACCESS_TOKEN_TTL = 'PT1H';
    private const string REFRESH_TOKEN_TTL = 'P1M';

    public function __construct(
        private Config $config,
    ) {}

    public function getAuthorizationCodeTtl(): DateInterval
    {
        $interval = $this->config->get('oAuthServer.ttl.authorizationCode') ?? self::AUTHORIZATION_CODE_TTL;

        return self::convertIntervalToDateInterval($interval);
    }

    public function getAccessTokenTtl(): DateInterval
    {
        $interval = $this->config->get('oAuthServer.ttl.accessToken') ?? self::ACCESS_TOKEN_TTL;

        return self::convertIntervalToDateInterval($interval);
    }

    public function getRefreshTokenTtl(): DateInterval
    {
        $interval = $this->config->get('oAuthServer.ttl.refreshToken') ?? self::REFRESH_TOKEN_TTL;

        return self::convertIntervalToDateInterval($interval);
    }

    private static function convertIntervalToDateInterval(mixed $interval): DateInterval
    {
        try {
            $value = new DateInterval($interval);
        } catch (DateMalformedIntervalStringException $e) {
            throw new RuntimeException("Bad interval '$interval'.", previous: $e);
        }

        return $value;
    }
}
