<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
            if (!$this->getConfig()) {
                return null;
            }

            $connectionParams = $this->getConfig()->get('database');

            if (empty($connectionParams['dbname']) || empty($connectionParams['user'])) {
                return null;
            }

            $connectionParams['driverClass'] = $this->drivers[ $connectionParams['driver'] ];
            unset($connectionParams['driver']);

            $dbalConfig = new \Doctrine\DBAL\Configuration();
            $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $dbalConfig);
        }

        return $this->connection;
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
        $mysqlEngine = $this->getMysqlEngine($tableName);
        if (!$mysqlEngine) {
            return $default;
        }

        switch ($mysqlEngine) {
            case 'InnoDB':
                $mysqlVersion = $this->getMysqlVersion();

                if (version_compare($mysqlVersion, '10.0.0') >= 0) {
                    return 767; //InnoDB, MariaDB
                }

                if (version_compare($mysqlVersion, '5.7.0') >= 0) {
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

    protected function getMysqlVersion()
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
    protected function getMysqlEngine($tableName = null, $default = null)
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
        $mysqlEngine = $this->getMysqlEngine($tableName);
        if (!$mysqlEngine) {
            return $default;
        }

        switch ($mysqlEngine) {
            case 'InnoDB':
                $mysqlVersion = $this->getMysqlVersion();

                if (version_compare($mysqlVersion, '5.6.4') >= 0) {
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
}