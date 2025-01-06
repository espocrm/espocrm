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

namespace Espo\Core\ORM;

use Espo\Core\Utils\Config;
use Espo\ORM\DatabaseParams;

use RuntimeException;

class DatabaseParamsFactory
{
    private const DEFAULT_PLATFORM = 'Mysql';

    public function __construct(private Config $config) {}

    public function create(): DatabaseParams
    {
        $config = $this->config;

        if (!$config->get('database')) {
            throw new RuntimeException('No database params in config.');
        }

        $databaseParams = DatabaseParams::create()
            ->withHost($config->get('database.host'))
            ->withPort($config->get('database.port') ? (int) $config->get('database.port') : null)
            ->withName($config->get('database.dbname'))
            ->withUsername($config->get('database.user'))
            ->withPassword($config->get('database.password'))
            ->withCharset($config->get('database.charset'))
            ->withPlatform($config->get('database.platform'))
            ->withSslCa($config->get('database.sslCA'))
            ->withSslCert($config->get('database.sslCert'))
            ->withSslKey($config->get('database.sslKey'))
            ->withSslCaPath($config->get('database.sslCAPath'))
            ->withSslCipher($config->get('database.sslCipher'))
            ->withSslVerifyDisabled($config->get('database.sslVerifyDisabled') ?? false);

        if (!$databaseParams->getPlatform()) {
            $databaseParams = $databaseParams->withPlatform(self::DEFAULT_PLATFORM);
        }

        return $databaseParams;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function createWithMergedAssoc(array $params): DatabaseParams
    {
        $configParams = $this->create();

        return DatabaseParams::create()
            ->withHost($params['host'] ?? $configParams->getHost())
            ->withPort(isset($params['port']) ? (int) $params['port'] : $configParams->getPort())
            ->withName($params['dbname'] ?? $configParams->getName())
            ->withUsername($params['user'] ?? $configParams->getUsername())
            ->withPassword($params['password'] ?? $configParams->getPassword())
            ->withCharset($params['charset'] ?? $configParams->getCharset())
            ->withPlatform($params['platform'] ?? $configParams->getPlatform())
            ->withSslCa($params['sslCA'] ?? $configParams->getSslCa())
            ->withSslCert($params['sslCert'] ?? $configParams->getSslCert())
            ->withSslKey($params['sslKey'] ?? $configParams->getSslKey())
            ->withSslCaPath($params['sslCAPath'] ?? $configParams->getSslCaPath())
            ->withSslCipher($params['sslCipher'] ?? $configParams->getSslCipher())
            ->withSslVerifyDisabled($params['sslVerifyDisabled'] ?? $configParams->isSslVerifyDisabled());
    }
}
