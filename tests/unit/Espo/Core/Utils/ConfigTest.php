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

namespace tests\unit\Espo\Core\Utils;

use tests\unit\ReflectionHelper;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $defaultTestConfig = 'tests/unit/testData/Utils/Config/config.php';

    protected $configPath = 'tests/unit/testData/cache/config.php';

    protected $systemConfigPath = 'tests/unit/testData/Utils/Config/systemConfig.php';

    protected function setUp() : void
    {
        $this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();

        /*copy defaultTestConfig file to cache*/
        if (!file_exists($this->configPath)) {
            copy($this->defaultTestConfig, $this->configPath);
        }

        $this->object = new \Espo\Core\Utils\Config($this->objects['fileManager']);

        $this->reflection = new ReflectionHelper($this->object);

        $this->reflection->setProperty('configPath', $this->configPath);
        $this->reflection->setProperty('systemConfigPath', $this->systemConfigPath);
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }


    public function testLoadConfig()
    {
        $this->assertArrayHasKey('database', $this->reflection->invokeMethod('loadConfig', array()));

        $this->assertArrayHasKey('dateFormat', $this->reflection->invokeMethod('loadConfig', array()));
    }

    public function testGet()
    {
        $result = array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'espocrm',
            'user' => 'root',
            'password' => '',
        );
        $this->assertEquals($result, $this->object->get('database'));

        $result = 'pdo_mysql';
        $this->assertEquals($result, $this->object->get('database.driver'));


        $result = 'YYYY-MM-DD';
        $this->assertEquals($result, $this->object->get('dateFormat'));

        $this->assertTrue($this->object->get('isInstalled'));
    }


    public function testSet()
    {
        $setKey= 'testOption';
        $setValue= 'Test';

        $this->object->set($setKey, $setValue);
        $this->assertTrue($this->object->save());
        $this->assertEquals($setValue, $this->object->get($setKey));

        $this->object->set($setKey, 'Another Wrong Value');
        $this->assertTrue($this->object->save());

        $databaseOptions = (object) array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'espocrm',
            'user' => 'root',
            'password' => '',
        );

        $this->object->set((object) ["database" => $databaseOptions]);
        $this->object->save();
        $this->assertTrue($this->object->has("database"));
    }

    public function testSetNull()
    {
        $setKey= 'testOption';
        $setValue= 'Test';

        $this->object->set($setKey, $setValue);
        $this->assertTrue($this->object->save());
        $this->assertEquals($setValue, $this->object->get($setKey));

        $this->object->set($setKey, null);
        $this->assertTrue($this->object->save());
        $this->assertNull($this->object->get($setKey));
    }

    public function testSetArray()
    {
        $values = array(
            'testOption' => 'Test',
            'testOption2' => 'Test2',
        );

        $this->object->set($values);
        $this->assertTrue($this->object->save());
        $this->assertEquals('Test', $this->object->get('testOption'));
        $this->assertEquals('Test2', $this->object->get('testOption2'));

        $wrongArray = array(
            'testOption' => 'Another Wrong Value',
        );
        $this->object->set($wrongArray);
        $this->assertTrue($this->object->save());
    }

    public function testRemove()
    {
        $optKey = 'removeOption';
        $optValue = 'Test';

        $this->object->set($optKey, $optValue);
        $this->assertTrue($this->object->save());

        $this->assertTrue($this->object->remove($optKey));
        $this->assertTrue($this->object->save());

        $this->assertNull($this->object->get($optKey));
    }

    public function testSystemConfigMerge()
    {
        $configDataWithoutSystem = $this->objects['fileManager']->getPhpContents($this->configPath);
        $this->assertArrayNotHasKey('systemItems', $configDataWithoutSystem);
        $this->assertArrayNotHasKey('adminItems', $configDataWithoutSystem);

        $configData = $this->reflection->invokeMethod('loadConfig', array());

        $this->assertArrayHasKey('systemItems', $configData);
        $this->assertArrayHasKey('adminItems', $configData);
    }

    public function testGetConfigPath()
    {
        $this->assertSame($this->configPath, $this->object->getConfigPath());
    }

    public function testHas()
    {
        $result = array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'espocrm',
            'user' => 'root',
            'password' => '',
        );
        $this->assertTrue($this->object->has('database'));

        $result = 'pdo_mysql';
        $this->assertFalse($this->object->has('database.charset'));

        $result = 'YYYY-MM-DD';
        $this->assertTrue($this->object->has('dateFormat'));

        $this->assertTrue($this->object->has('isInstalled'));
    }

    public function testGetDefaults()
    {
        $this->assertNotNull($this->object->getDefaults());
    }

    public function testGetAllData()
    {
        $this->assertIsObject($this->object->getAllData());
    }

    public function testGetData()
    {
        $this->assertNotNull($this->object->getData());
    }

    public function testSetData()
    {
        $data = [
            "siteUrl" => "http://localhost"
        ];
        $this->object->setData((object) $data);
        $this->assertEquals($this->object->get("siteUrl"), $this->object->getSiteUrl());

        $data = [
            "siteUrl1" => "http://localhost"
        ];
        $this->object->setData($data);
        $this->assertEquals($data["siteUrl1"], $this->object->get("siteUrl1"));
    }

    public function testUpdateCacheTimeStamp()
    {
        $this->object->updateCacheTimeStamp(false);
        $this->assertNotNull($this->object->get('cacheTimestamp'));
    }

    public function testGetAdminOnlyItemList()
    {
        $adminItems = (object) [
            "adminItems" => array(
                0 => "devMode",
                1 => "outboundEmailIsShared",
                2 => "outboundEmailFromName",
                3 => "outboundEmailFromAddress",
                4 => "smtpServer",
                5 => "smtpPort",
                6 => "smtpAuth",
                7 => "smtpSecurity",
                8 => "smtpUsername",
                9 => "smtpPassword",
                10 => "cron",
            )
        ];
        $this->object->set($adminItems);
        $this->assertEquals($adminItems->adminItems, $this->object->getAdminOnlyItemList());
    }

    public function testGetSuperAdminOnlyItemList()
    {
        $superAdminOnlyItemList = [];
        $this->object->set("superAdminOnlyItemList", $superAdminOnlyItemList);
        $this->assertEmpty($this->object->getSuperAdminOnlyItemList());
    }

    public function testGetSystemOnlyItemList()
    {
        $systemItems = array(
            0 => "systemItems",
            1 => "adminItems",
            2 => "configPath",
            3 => "cachePath",
            4 => "database",
            5 => "crud",
            6 => "logger",
            7 => "isInstalled",
            8 => "defaultPermissions",
            9 => "systemUser",
            10 => "userItems",
        );
        $this->object->set("systemItems", $systemItems);
        $this->assertEquals($systemItems, $this->object->getSystemOnlyItemList());
    }

    public function testGetSuperAdminOnlySystemItemList()
    {
        $superAdminOnlySystemItemList = [];
        $this->object->set("superAdminOnlySystemItemList", $superAdminOnlySystemItemList);
        $this->assertEmpty($this->object->getSuperAdminOnlySystemItemList());
    }

    public function testGetUserOnlyItemList()
    {
        $userItems = array(
            0 => "currencyList",
            1 => "addressFormat",
            2 => "quickCreateList",
            3 => "recordsPerPage",
            4 => "recordsPerPageSmall",
            5 => "tabList",
            6 => "thousandSeparator",
            7 => "timeFormat",
            8 => "timeZone",
            9 => "weekStart",
        );
        $this->object->set("userItems", $userItems);
        $this->assertEquals($userItems, $this->object->getUserOnlyItemList());
    }

}
