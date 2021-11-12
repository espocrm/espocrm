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

namespace tests\integration\Espo\Core\Utils\Database;

use tests\unit\ReflectionHelper;

use Espo\Core\Utils\Database\Helper;

use Doctrine\DBAL\Connection;

use Espo\Core\Exceptions\Error;

use PDO;

use RuntimeException;

class HelperTest extends \tests\integration\Core\BaseTestCase
{
    protected $reflection;

    protected function initTest(bool $noConfig = false)
    {
        $config = $noConfig ? null : $this->getContainer()->get('config');

        $this->helper = new Helper($config);

        $this->reflection = new ReflectionHelper($this->helper);
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

    public function testGetDbalConnectionWithNoConfig()
    {
        $this->initTest(true);

        $this->expectException(RuntimeException::class);

        $this->helper->getDbalConnection();
    }

    public function testGetDbalConnectionWithConfig()
    {
        $this->initTest();

        $this->assertInstanceOf(Connection::class, $this->helper->getDbalConnection());
    }

    public function testGetPdoConnection()
    {
        $this->initTest(true);

        $this->expectException(RuntimeException::class);

        $this->helper->getPdoConnection();
    }

    public function testGetPdoConnectionWithConfig()
    {
        $this->initTest();

        $this->assertInstanceOf(PDO::class, $this->helper->getPdoConnection());
    }

    public function testGetMaxIndexLength()
    {
        $this->initTest();

        $databaseInfo = $this->getDatabaseInfo();
        if (empty($databaseInfo)) {
            return;
        }

        $expectedMaxIndexLength = 767;

        switch ($databaseInfo['type']) {
            case 'mysql':
                if (version_compare($databaseInfo['version'], '5.7.0') >= 0) {
                    $expectedMaxIndexLength = 3072;
                }
                break;

            case 'mariadb':
                if (version_compare($databaseInfo['version'], '10.2.2') >= 0) {
                    $expectedMaxIndexLength = 3072;
                }
                break;
        }

        $engine = $this->reflection->invokeMethod('getTableEngine');
        $result = ($engine == 'MyISAM') ? 1000 : $expectedMaxIndexLength;
        $this->assertEquals($result, $this->helper->getMaxIndexLength());

        $engine = $this->reflection->invokeMethod('getTableEngine', ['account']);
        $result = ($engine == 'MyISAM') ? 1000 : $expectedMaxIndexLength;
        $this->assertEquals($expectedMaxIndexLength, $this->helper->getMaxIndexLength('account'));
    }

    public function testGetDatabaseInfo()
    {
        $this->initTest();

        $databaseInfo = $this->getDatabaseInfo();
        if (empty($databaseInfo)) {
            return;
        }

        $this->assertEquals($databaseInfo['type'], strtolower($this->helper->getDatabaseType()));
        $this->assertEquals($databaseInfo['version'], $this->helper->getDatabaseVersion());
    }

    public function testIsSupportsFulltext()
    {
        $this->initTest();

        $databaseInfo = $this->getDatabaseInfo();
        if (empty($databaseInfo)) {
            return;
        }

        switch ($databaseInfo['type']) {
            case 'mysql':
                if (version_compare($databaseInfo['version'], '5.7.0', '<')) {
                    throw new Error('You have to upgrade your MySQL to use EspoCRM.');
                }
                break;

            case 'mariadb':
                if (version_compare($databaseInfo['version'], '10.1.0', '<')) {
                    throw new Error('You have to upgrade your MariaDB to use EspoCRM.');
                }
                break;

            default:
                throw new Error('Uknown database type.');
                break;
        }

        $this->assertTrue($this->helper->doesSupportFulltext());
        $this->assertTrue($this->helper->doesSupportFulltext('dummy_table', false));
        $this->assertTrue($this->helper->doesTableSupportFulltext('account'));
        $this->assertTrue($this->helper->doesTableSupportFulltext('account', true));
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
                $this->assertEquals('MySQL', $this->helper->getDatabaseType());
                break;

            case 'mariadb':
                $this->assertEquals('MariaDB', $this->helper->getDatabaseType());
                break;
        }

        $this->assertEquals('MySQL', $this->helper->getDatabaseType('MySQL'));
        $this->assertEquals('MariaDB', $this->helper->getDatabaseType('MariaDB'));
    }
}
