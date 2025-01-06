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

namespace tests\integration\Espo\Core\Utils\Database;

use Espo\Core\Utils\Database\Helper;
use Doctrine\DBAL\Connection;
use PDO;

class HelperTest extends \tests\integration\Core\BaseTestCase
{
    /** @var ?Helper */
    protected $helper;

    protected function initTest()
    {
        $this->helper = $this->getInjectableFactory()->create(Helper::class);
    }

    private function getDatabaseInfo()
    {
        $pdo = $this->getContainer()->get('entityManager')->getPDO();

        $sth = $pdo->prepare("select version()");
        $sth->execute();

        $version = $sth->fetchColumn();

        $type = 'mysql';
        if (preg_match('/mariadb/i', $version)) {
            $type = 'mariadb';
        }

        if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $version, $match)) {
            $version = $match[0];
        }

        return [
            'type' => $type,
            'version' => $version,
        ];
    }

    public function testGetDbalConnectionWithConfig()
    {
        $this->initTest();

        $this->assertInstanceOf(Connection::class, $this->helper->getDbalConnection());
    }

    public function testGetPdoConnectionWithConfig()
    {
        $this->initTest();

        $this->assertInstanceOf(PDO::class, $this->helper->getPDO());
    }

    public function testGetDatabaseInfo()
    {
        $this->initTest();

        $databaseInfo = $this->getDatabaseInfo();
        if (empty($databaseInfo)) {
            return;
        }

        $this->assertEquals($databaseInfo['type'], strtolower($this->helper->getType()));
        $this->assertEquals($databaseInfo['version'], $this->helper->getVersion());
    }

    public function testGetDatabaseType()
    {
        $this->initTest();

        $databaseInfo = $this->getDatabaseInfo();
        if (empty($databaseInfo)) {
            return;
        }

        switch ($databaseInfo['type']) {
            case 'mysql':
                $this->assertEquals('MySQL', $this->helper->getType());
                break;

            case 'mariadb':
                $this->assertEquals('MariaDB', $this->helper->getType());
                break;
        }
    }
}
