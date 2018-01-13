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

namespace tests\unit\Espo\Core\Utils;

use tests\unit\ReflectionHelper;
use Espo\Core\Utils\Util;

class LayoutTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected $filesPath= 'tests/unit/testData/FileManager';

    protected function setUp()
    {
        $this->objects['fileManager'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->disableOriginalConstructor()->getMock();
        $this->objects['metadata'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Metadata')->disableOriginalConstructor()->getMock();
        $this->objects['user'] = $this->getMockBuilder('\\Espo\\Entities\\User')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\Layout($this->objects['fileManager'], $this->objects['metadata'], $this->objects['user']);

        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('params', array(
            'application/Espo/Core/defaults',
        ) );
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    function testGetLayoutPathCore()
    {
        $this->objects['metadata']
            ->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));

        $this->assertEquals(Util::fixPath('application/Espo/Resources/layouts/User'), $this->reflection->invokeMethod('getLayoutPath', array('User')) );
        $this->assertEquals(Util::fixPath('custom/Espo/Custom/Resources/layouts/User'), $this->reflection->invokeMethod('getLayoutPath', array('User', true)) );
    }


    function testGetLayoutPathModule()
    {
        $this->objects['metadata']
            ->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue('Crm'));

        $this->assertEquals(Util::fixPath('application/Espo/Modules/Crm/Resources/layouts/Call'), $this->reflection->invokeMethod('getLayoutPath', array('Call')) );
        $this->assertEquals(Util::fixPath('custom/Espo/Custom/Resources/layouts/Call'), $this->reflection->invokeMethod('getLayoutPath', array('Call', true)) );
    }

    function testGet()
    {
        $result = '[{"label":"Overview","rows":[[{"name":"userName"},{"name":"isAdmin"}],[{"name":"name"},{"name":"title"}],[{"name":"defaultTeam"}],[{"name":"emailAddress"},{"name":"phone"}]]}]';

        $this->objects['metadata']
            ->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));

        $this->objects['fileManager']
            ->expects($this->exactly(1))
            ->method('getContents')
            ->will($this->returnValue($result));

        $this->assertEquals($result, $this->object->get('Note', 'detail'));
    }



}

?>
