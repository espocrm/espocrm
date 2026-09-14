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

namespace Espo\Tools\OAuthServer\Metadata;

use Espo\Core\Utils\Cache\DataCacheAccess;
use Espo\Core\Utils\Config\ApplicationConfig;
use stdClass;

class ProtectedResourceMetadataProvider
{
    private const string CACHE_KEY = 'oAuthServer/protectedResourceMetadata';

    /**
     * @param DataCacheAccess<stdClass> $dataCacheAccess
     */
    public function __construct(
        private DataCacheAccess $dataCacheAccess,
        private ApplicationConfig $applicationConfig,
    ) {
        $this->dataCacheAccess->init(
            key: self::CACHE_KEY,
            loader: fn () => $this->buildData(),
        );
    }

    public function get(): stdClass
    {
        return $this->dataCacheAccess->get();
    }

    private function buildData(): stdClass
    {
        return (object) [
            'resource' => $this->applicationConfig->getSiteUrl(),
            'authorization_servers' => [
                self::serverIdentifierFromUrl($this->applicationConfig->getSiteUrl()),
            ],
            'bearer_methods_supported' => [
                'header',
            ],
        ];
    }

    private static function serverIdentifierFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        return $scheme . $host . $port;
    }
}
