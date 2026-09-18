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

namespace Espo\Core\Utils\Database\Dbal\Factories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Espo\Core\Utils\Database\Dbal\ConnectionFactory;
use Espo\Core\Utils\Database\Dbal\Drivers\PostgreSQLCustomDriver;
use Espo\Core\Utils\Database\Helper;
use Espo\ORM\DatabaseParams;
use Espo\ORM\PDO\Options as PdoOptions;

use PDO;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class PostgresqlConnectionFactory implements ConnectionFactory
{
    private const string DEFAULT_CHARSET = 'utf8';

    public function __construct(
        private PDO $pdo,
        private Helper $helper,
    ) {}

    /**
     * @throws DBALException
     */
    public function create(DatabaseParams $databaseParams): Connection
    {
        $textSearchConfig = null;

        if ($databaseParams->getName()) {
            $textSearchConfig = $this->helper->getParam('default_text_search_config');
        }

        $driver = new PostgreSQLCustomDriver(
            textSearchConfig: $textSearchConfig,
        );

        if (!$databaseParams->getHost()) {
            throw new RuntimeException("No database host in config.");
        }

        $params = [
            'pdo' => $this->pdo,
            'host' => $databaseParams->getHost(),
            'driverOptions' => PdoOptions::getOptionsFromDatabaseParams($databaseParams),
        ];

        if ($databaseParams->getName() !== null) {
            $params['dbname'] = $databaseParams->getName();
        }

        if ($databaseParams->getPort() !== null) {
            $params['port'] = $databaseParams->getPort();
        }

        if ($databaseParams->getUsername() !== null) {
            $params['user'] = $databaseParams->getUsername();
        }

        if ($databaseParams->getPassword() !== null) {
            $params['password'] = $databaseParams->getPassword();
        }

        $params['charset'] = $databaseParams->getCharset() ?? self::DEFAULT_CHARSET;

        return new Connection($params, $driver);
    }
}
