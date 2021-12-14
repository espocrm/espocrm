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

namespace Espo\Core\Utils\Database;

use Espo\Core\{
    Exceptions\Error,
    Utils\Config,
};

use Doctrine\DBAL\{
    Connection as DbalConnection,
    Platforms\AbstractPlatform as DbalPlatform,
};

use Espo\ORM\{
    DatabaseParams,
    PDO\DefaultPDOProvider,
    PDO\Options as PdoOptions,
};

use PDO;
use ReflectionClass;
use RuntimeException;

class Helper
{
    private $config;

    private $dbalConnection;

    private $pdoConnection;

    private $driverPlatformMap = [
        'pdo_mysql' => 'Mysql',
        'mysqli' => 'Mysql',
    ];

    protected $dbalDrivers = [
        'mysqli' => 'Doctrine\\DBAL\\Driver\\Mysqli\\Driver',
        'pdo_mysql' => 'Espo\\Core\\Utils\\Database\\DBAL\\Driver\\PDO\\MySQL\\Driver',
    ];

    protected $dbalPlatforms = [
        'MariaDb1027Platform' => 'Espo\\Core\\Utils\\Database\\DBAL\\Platforms\\MariaDb1027Platform',
        'MySQL57Platform' => 'Espo\\Core\\Utils\\Database\\DBAL\\Platforms\\MySQL57Platform',
        'MySQL80Platform' => 'Espo\\Core\\Utils\\Database\\DBAL\\Platforms\\MySQL80Platform',
        'MySQLPlatform' => 'Espo\\Core\\Utils\\Database\\DBAL\\Platforms\\MySQLPlatform',
    ];

    public function __construct(Config $config = null)
    {
        $this->config = $config;
    }

    public function getDbalConnection()
    {
        if (!isset($this->dbalConnection)) {
            $this->dbalConnection = $this->createDbalConnection();
        }

        return $this->dbalConnection;
    }

    public function getPdoConnection()
    {
        if (!isset($this->pdoConnection)) {
            $this->pdoConnection = $this->createPdoConnection();
        }

        return $this->pdoConnection;
    }

    public function setDbalConnection(DbalConnection $dbalConnection)
    {
        $this->dbalConnection = $dbalConnection;
    }

    public function setPdoConnection(PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    public function createDbalConnection(array $params = [])
    {
        if (empty($params) && isset($this->config)) {
            $params = $this->config->get('database');
        }

        if (empty($params)) {
            throw new RuntimeException('Params cannot be empty for Dbal connection.');
        }

        $databaseParams = $this->createDatabaseParams($params);

        $driver = $this->createDbalDriver($params);

        $version = $this->getFullDatabaseVersion();

        $platform = $driver->createDatabasePlatformForVersion($version);

        return new DbalConnection(
            [
                'platform' => $this->createDbalPlatform($platform),
                'host' => $databaseParams->getHost(),
                'port' => $databaseParams->getPort(),
                'dbname' => $databaseParams->getName(),
                'charset' => $databaseParams->getCharset(),
                'user' => $databaseParams->getUsername(),
                'password' => $databaseParams->getPassword(),
                'driverOptions' => PdoOptions::getOptionsFromDatabaseParams($databaseParams),
            ],
            $driver
        );
    }

    private function createDbalDriver(array $params)
    {
        $driverName = $params['driver'] ?? 'pdo_mysql';

        if (!isset($this->dbalDrivers[$driverName])) {
            throw new RuntimeException('Unknown database driver.');
        }

        $driverClass = $this->dbalDrivers[$driverName];

        if (!class_exists($driverClass)) {
            throw new RuntimeException('Unknown database class.');
        }

        return new $driverClass();
    }

    private function createDbalPlatform(DbalPlatform $platform)
    {
        $reflect = new ReflectionClass($platform);

        $platformClass = $reflect->getShortName();

        if (isset($this->dbalPlatforms[$platformClass])) {
            $class = $this->dbalPlatforms[$platformClass];

            return new $class();
        }

        return $platform;
    }

    /**
     * Create PDO connection.
     *
     * @param array $params
     * @return PDO|null
     */
    public function createPdoConnection(
        array $params = [],
        bool $skipDatabaseName = false
    ) {
        $defaultParams = [
            'driver' => 'pdo_mysql',
        ];

        if (isset($this->config) && $this->config instanceof Config) {
            $defaultParams = array_merge(
                $defaultParams,
                $this->config->get('database')
            );
        }

        $params = array_merge(
            $defaultParams,
            $params
        );

        if ($skipDatabaseName && isset($params['dbname'])) {
            unset($params['dbname']);
        }

        $pdoProvider = new DefaultPDOProvider(
            $this->createDatabaseParams($params)
        );

        return $pdoProvider->get();
    }

    private function createDatabaseParams(array $params): DatabaseParams
    {
        $databaseParams = DatabaseParams::create()
            ->withHost($params['host'] ?? null)
            ->withPort(isset($params['port']) ? (int) $params['port'] : null)
            ->withName($params['dbname'] ?? null)
            ->withUsername($params['user'] ?? null)
            ->withPassword($params['password'] ?? null)
            ->withCharset($params['charset'] ?? 'utf8')
            ->withPlatform($params['platform'] ?? null)
            ->withSslCa($params['sslCA'] ?? null)
            ->withSslCert($params['sslCert'] ?? null)
            ->withSslKey($params['sslKey'] ?? null)
            ->withSslCaPath($params['sslCAPath'] ?? null)
            ->withSslCipher($params['sslCipher'] ?? null)
            ->withSslVerifyDisabled($params['sslVerifyDisabled'] ?? false);

        if (!$databaseParams->getPlatform()) {
            $driver = $params['driver'] ?? null;

            if (!$driver) {
                throw new RuntimeException('No database driver specified.');
            }

            $platform = $this->driverPlatformMap[$driver] ?? null;

            if (!$platform) {
                throw new RuntimeException("Database driver '{$driver}' is not supported.");
            }

            $databaseParams = $databaseParams->withPlatform($platform);
        }

        return $databaseParams;
    }

    /**
     * Get maximum index length. If $tableName is empty get a value for all database tables.
     *
     * @param ?string $tableName
     * @return int
     */
    public function getMaxIndexLength($tableName = null, $default = 1000)
    {
        $tableEngine = $this->getTableEngine($tableName);

        if (!$tableEngine) {
            return $default;
        }

        switch ($tableEngine) {
            case 'InnoDB':
                $databaseType = $this->getDatabaseType();
                $version = $this->getDatabaseVersion();

                switch ($databaseType) {
                    case 'MariaDB':
                        if (version_compare($version, '10.2.2') >= 0) {
                            return 3072; //InnoDB, MariaDB 10.2.2+
                        }

                        break;

                    case 'MySQL':
                        if (version_compare($version, '5.7.0') >= 0) {
                            return 3072; //InnoDB, MySQL 5.7+
                        }

                        break;
                }

                return 767; //InnoDB
        }

        return 1000; //MyISAM
    }

    public function getTableMaxIndexLength($tableName, $default = 1000)
    {
        return $this->getMaxIndexLength($tableName, $default);
    }

    /**
     * Get database type (MySQL, MariaDB)
     * @return string
     */
    public function getDatabaseType($default = 'MySQL')
    {
        $version = $this->getFullDatabaseVersion();

        if (preg_match('/mariadb/i', $version)) {
            return 'MariaDB';
        }

        return $default;
    }

    protected function getFullDatabaseVersion()
    {
        $connection = $this->getPdoConnection();

        if (!$connection) {
            return null;
        }

        $sth = $connection->prepare("select version()");

        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Get database version.
     *
     * @return string|null
     */
    public function getDatabaseVersion()
    {
        $fullVersion = $this->getFullDatabaseVersion();

        if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $fullVersion, $match)) {
            return $match[0];
        }

        return null;
    }

    /**
     * Get table/database tables engine. If $tableName is empty get a value for all database tables.
     *
     * @param  string|null $tableName
     *
     * @return string
     */
    protected function getTableEngine($tableName = null, $default = null)
    {
        $connection = $this->getPdoConnection();

        if (!$connection) {
            return $default;
        }

        $query = "SHOW TABLE STATUS WHERE Engine = 'MyISAM'";
        if (!empty($tableName)) {
            $query = "SHOW TABLE STATUS WHERE Engine = 'MyISAM' AND Name = '" . $tableName . "'";
        }

        $sth = $connection->prepare($query);
        $sth->execute();

        $result = $sth->fetchColumn();

        if (!empty($result)) {
            return 'MyISAM';
        }

        return 'InnoDB';
    }

    /**
     * Check if full text is supported. If $tableName is empty get a value for all database tables.
     *
     * @param string $tableName
     *
     * @return boolean
     */
    public function doesSupportFulltext($tableName = null, $default = false)
    {
        $tableEngine = $this->getTableEngine($tableName);

        if (!$tableEngine) {
            return $default;
        }

        switch ($tableEngine) {
            case 'InnoDB':
                $version = $this->getFullDatabaseVersion();

                if (version_compare($version, '5.6.4') >= 0) {
                    return true; //InnoDB, MySQL 5.6.4+
                }

                return false; //InnoDB
        }

        return true; //MyISAM
    }

    public function doesTableSupportFulltext($tableName, $default = false)
    {
        return $this->doesSupportFulltext($tableName, $default);
    }

    public function getPdoDatabaseParam($name, PDO $pdoConnection)
    {
        if (!method_exists($pdoConnection, 'prepare')) {
            return null;
        }

        $sth = $pdoConnection->prepare("SHOW VARIABLES LIKE '" . $name . "'");

        $sth->execute();

        $res = $sth->fetch(PDO::FETCH_NUM);

        $version = empty($res[1]) ? null : $res[1];

        return $version;
    }

    public function getPdoDatabaseVersion(PDO $pdoConnection)
    {
        return $this->getPdoDatabaseParam('version', $pdoConnection);
    }
}
