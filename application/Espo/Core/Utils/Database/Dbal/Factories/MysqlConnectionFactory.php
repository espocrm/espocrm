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

namespace Espo\Core\Utils\Database\Dbal\Factories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as PDOMySQLDriver;
use Doctrine\DBAL\Exception as DBALException;

use Espo\Core\Utils\Database\Dbal\ConnectionFactory;
use Espo\ORM\DatabaseParams;
use Espo\ORM\PDO\Options as PdoOptions;

use PDO;
use RuntimeException;

class MysqlConnectionFactory implements ConnectionFactory
{
    private const DEFAULT_CHARSET = 'utf8mb4';

    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * @throws DBALException
     */
    public function create(DatabaseParams $databaseParams): Connection
    {
        $driver = new PDOMySQLDriver();

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
