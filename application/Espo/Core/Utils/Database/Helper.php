<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Utils\Util;
use Espo\ORM\Entity;

class Helper
{
    private $config;

    private $connection;

    protected $drivers = array(
        'mysqli' => '\Espo\Core\Utils\Database\DBAL\Driver\Mysqli\Driver',
        'pdo_mysql' => '\Espo\Core\Utils\Database\DBAL\Driver\PDOMySql\Driver',
    );

    public function __construct(\Espo\Core\Utils\Config $config = null)
    {
        $this->config = $config;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    public function getDbalConnection()
    {
        if (!isset($this->connection)) {
            $this->connection = $this->createDbalConnection();
        }

        return $this->connection;
    }

    public function setDbalConnection($dbalConnection)
    {
        $this->connection = $dbalConnection;
    }

    public function createDbalConnection(array $params = null)
    {
        if (!isset($params)) {
            $config = $this->getConfig();
            if ($config) {
                $params = $config->get('database');
            }
        }

        $params['driver'] = isset($params['driver']) ? $params['driver'] : 'pdo_mysql';

        if (empty($params['dbname']) || empty($params['user'])) {
            return null;
        }

        $params['driverClass'] = $this->drivers[ $params['driver'] ];
        unset($params['driver']);

        $dbalConfig = new \Doctrine\DBAL\Configuration();

        return \Doctrine\DBAL\DriverManager::getConnection($params, $dbalConfig);
    }

    /**
     * Create PDO connection
     * @param  array $params
     * @return \Pdo| \PDOException
     */
    public function createPdoConnection(array $params = null)
    {
        if (!isset($params)) {
            $params = $this->getConfig()->get('database');
        }

        $platform = !empty($params['platform']) ? strtolower($params['platform']) : 'mysql';
        $port = empty($params['port']) ? '' : ';port=' . $params['port'];
        $dbname = empty($params['dbname']) ? '' : ';dbname=' . $params['dbname'];

        $options = array();

        if (isset($params['sslCA'])) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $params['sslCA'];
        }
        if (isset($params['sslCert'])) {
            $options[PDO::MYSQL_ATTR_SSL_CERT] = $params['sslCert'];
        }
        if (isset($params['sslKey'])) {
            $options[PDO::MYSQL_ATTR_SSL_KEY] = $params['sslKey'];
        }
        if (isset($params['sslCAPath'])) {
            $options[PDO::MYSQL_ATTR_SSL_CAPATH] = $params['sslCAPath'];
        }
        if (isset($params['sslCipher'])) {
            $options[PDO::MYSQL_ATTR_SSL_CIPHER] = $params['sslCipher'];
        }

        $dsn = $platform . ':host='.$params['host'].$port.$dbname;
        $dbh = new \PDO($dsn, $params['user'], $params['password'], $options);

        return $dbh;
    }

    /**
     * Get maximum index length. If $tableName is empty get a value for all database tables
     *
     * @param  string|null $tableName
     *
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
                $version = $this->getDatabaseVersion();

                if (version_compare($version, '10.0.0') >= 0) {
                    return 767; //InnoDB, MariaDB
                }

                if (version_compare($version, '5.7.0') >= 0) {
                    return 3072; //InnoDB, MySQL 5.7+
                }

                return 767; //InnoDB
                break;
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
        $connection = $this->getDbalConnection();
        if (!$connection) {
            return $default;
        }

        $version = $this->getDatabaseVersion();
        if (preg_match('/mariadb/i', $version)) {
            return 'MariaDB';
        }

        return $default;
    }

    protected function getDatabaseVersion()
    {
        $connection = $this->getDbalConnection();
        if (!$connection) {
            return null;
        }

        return $connection->fetchColumn("select version()");
    }

    /**
     * Get table/database tables engine. If $tableName is empty get a value for all database tables
     *
     * @param  string|null $tableName
     *
     * @return string
     */
    protected function getTableEngine($tableName = null, $default = null)
    {
        $connection = $this->getDbalConnection();
        if (!$connection) {
            return $default;
        }

        $query = "SHOW TABLE STATUS WHERE Engine = 'MyISAM'";
        if (!empty($tableName)) {
            $query = "SHOW TABLE STATUS WHERE Engine = 'MyISAM' AND Name = '" . $tableName . "'";
        }

        $result = $connection->fetchColumn($query);

        if (!empty($result)) {
            return 'MyISAM';
        }

        return 'InnoDB';
    }

    /**
     * Check if full text supports. If $tableName is empty get a value for all database tables
     *
     * @param  string $tableName
     *
     * @return boolean
     */
    public function isSupportsFulltext($tableName = null, $default = false)
    {
        $tableEngine = $this->getTableEngine($tableName);
        if (!$tableEngine) {
            return $default;
        }

        switch ($tableEngine) {
            case 'InnoDB':
                $version = $this->getDatabaseVersion();

                if (version_compare($version, '5.6.4') >= 0) {
                    return true; //InnoDB, MySQL 5.6.4+
                }

                return false; //InnoDB
                break;
        }

        return true; //MyISAM
    }

    public function isTableSupportsFulltext($tableName, $default = false)
    {
        return $this->isSupportsFulltext($tableName, $default);
    }

    public function getPdoDatabaseParam($name, \PDO $pdoConnection)
    {
        if (!method_exists($pdoConnection, 'prepare')) {
            return null;
        }

        $sth = $pdoConnection->prepare("SHOW VARIABLES LIKE '" . $name . "'");
        $sth->execute();
        $res = $sth->fetch(\PDO::FETCH_NUM);

        $version = empty($res[1]) ? null : $res[1];

        return $version;
    }

    public function getPdoDatabaseVersion(\PDO $pdoConnection)
    {
        return $this->getPdoDatabaseParam('version', $pdoConnection);
    }
}
