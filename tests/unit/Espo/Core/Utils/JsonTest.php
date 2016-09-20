<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\Json;

class JsonTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $GLOBALS['log'] = $this->getMockBuilder('\Espo\Core\Utils\Log')->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    public function testEncode()
    {
        $testVal= array('testOption'=>'Test');
        $this->assertEquals(json_encode($testVal), Json::encode($testVal));
    }

    public function testDecode()
    {
        $testVal= array('testOption'=>'Test');
        $this->assertEquals($testVal, Json::decode(json_encode($testVal), true));

        $test= '{"folder":"data\/logs"}';
        $this->assertEquals('data/logs', Json::decode($test)->folder);
    }

    public function testIsJSON()
    {
        $this->assertTrue(Json::isJSON('{"database":{"driver":"pdo_mysql","host":"localhost"},"devMode":true}'));

        $this->assertTrue(Json::isJSON('[]'));

        $this->assertTrue(Json::isJSON('{}'));

        $this->assertTrue(Json::isJSON('true'));

        $this->assertFalse(Json::isJSON('some string'));

        $this->assertTrue(Json::isJSON(true));
        $this->assertEquals('true', json_encode(true));

        $this->assertFalse(Json::isJSON(false));
        $this->assertEquals('false', json_encode(false));
    }



}

?>