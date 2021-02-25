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

use Espo\Core\Utils\Util;

use Espo\Core\Utils\Config;

use Espo\Core\Utils\Database\Helper;

use Doctrine\DBAL\Connection;

use PDO;

class HelperTest extends \tests\integration\Core\BaseTestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected function initTest(bool $noConfig = false)
    {
        $config = $noConfig ? null : $this->getContainer()->get('config');

        $this->helper = new Helper($config);

        $this->reflection = new ReflectionHelper($this->helper);
    }

    public function testGetDbalConnectionWithNoConfig()
    {
        $this->initTest(true);

        $this->assertNull($this->helper->getDbalConnection());
    }

    public function testGetDbalConnectionWithConfig()
    {
        $this->initTest();

        $this->assertInstanceOf(Connection::class, $this->helper->getDbalConnection());
    }

    public function testGetPdoConnection()
    {
        $this->initTest(true);

        $this->assertNull($this->helper->getPdoConnection());
    }

    public function testGetPdoConnectionWithConfig()
    {
        $this->initTest();

        $this->assertInstanceOf(PDO::class, $this->helper->getPdoConnection());
    }

    /*public function testGetMaxIndexLength()
    {
        $this->initTest();

        $this->assertEquals(1000, $this->helper->getMaxIndexLength());
        $this->assertEquals(1000, $this->helper->getMaxIndexLength('table_name'));
        $this->assertEquals(2000, $this->helper->getMaxIndexLength('table_name', 2000));
        $this->assertEquals(1000, $this->helper->getTableMaxIndexLength('table_name'));
        $this->assertEquals(2000, $this->helper->getTableMaxIndexLength('table_name', 2000));
    }*/

    /*public function testGetDatabaseVersion()
    {
        $this->initTest();

        $this->assertNull($this->reflection->invokeMethod('getDatabaseVersion'));
    }*/

    /*public function testGetTableEngine()
    {
        $this->initTest();

        $this->assertNull($this->reflection->invokeMethod('getTableEngine'));
        $this->assertEquals('InnoDB', $this->reflection->invokeMethod('getTableEngine', [null, 'InnoDB']));
    }*/

    /*public function testIsSupportsFulltext()
    {
        $this->initTest();

        $this->assertFalse($this->helper->isSupportsFulltext());
        $this->assertFalse($this->helper->isSupportsFulltext('table_name'));
        $this->assertTrue($this->helper->isSupportsFulltext('table_name', true));
        $this->assertFalse($this->helper->isTableSupportsFulltext('table_name'));
        $this->assertTrue($this->helper->isTableSupportsFulltext('table_name', true));
    }*/

    public function testGetDatabaseType()
    {
        $this->initTest();

        $this->assertEquals('MySQL', $this->helper->getDatabaseType());
        $this->assertEquals('MariaDB', $this->helper->getDatabaseType('MariaDB'));
    }
}
