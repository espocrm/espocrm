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

namespace tests\unit\Espo\Core\Cron;

use tests\unit\ReflectionHelper;

class ScheduledJobTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected $cronSetup = array(
        'linux' => 'linux command',
        'windows' => 'windows command',
        'mac' => 'mac command',
        'default' => 'default command',
    );

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\Espo\Core\Container')->disableOriginalConstructor()->getMock();

        $this->objects['language'] = $this->getMockBuilder('\Espo\Core\Utils\Language')->disableOriginalConstructor()->getMock();

        $map = array(
            array('language', $this->objects['language']),
        );

        $this->objects['container']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->object = new \Espo\Core\Utils\ScheduledJob( $this->objects['container'] );

        $this->reflection = new ReflectionHelper($this->object);

        $this->reflection->setProperty('cronSetup', $this->cronSetup);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    public function testGetSetupMessage()
    {
        $cronSetup = array (
            'linux' => 'linux message',
            'mac' => 'mac message',
            'windows' => 'windows message',
            'default' => 'default message',
        );

        $this->objects['language']
            ->expects($this->once())
            ->method('translate')
            ->will($this->returnValue($cronSetup));

        $res = array(
            'linux' => array(
                'message' => 'linux message',
                'command' => 'linux command',
            ),
            'windows' => array(
                'message' => 'windows message',
                'command' => 'windows command',
            ),
            'mac' => array(
                'message' => 'mac message',
                'command' => 'mac command',
            ),
            'default' => array(
                'message' => 'default message',
                'command' => 'default command',
            ),
        );

        $os = $this->reflection->invokeMethod('getSystemUtil')->getOS();
        $this->assertEquals( $res[$os], $this->reflection->invokeMethod('getSetupMessage', array()) );
    }



}

?>
