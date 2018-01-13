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

namespace tests\unit\Espo\Core\Utils\File;

use tests\unit\ReflectionHelper;


class ClassParserTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;


    protected function setUp()
    {
        $this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();
        $this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
        $this->objects['metadata'] = $this->getMockBuilder('\Espo\Core\Utils\Metadata')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\File\ClassParser($this->objects['fileManager'], $this->objects['config'], $this->objects['metadata']);

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    function testGetClassNameHash()
    {
        $paths = array(
            'tests/unit/testData/EntryPoints/Espo/EntryPoints',
             'tests/unit/testData/EntryPoints/Espo/Modules/Crm/EntryPoints',
        );

        $result = array(
            'Download' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => '\tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule'
        );
        $this->assertEquals( $result, $this->reflection->invokeMethod('getClassNameHash', array($paths)) );
    }


    function testGetDataWithCache()
    {
        $this->objects['config']
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(true));

        $cacheFile = 'tests/unit/testData/EntryPoints/cache/entryPoints.php';
        $paths = array(
            'corePath' => 'tests/unit/testData/EntryPoints/Espo/EntryPoints',
             'modulePath' => 'tests/unit/testData/EntryPoints/Espo/Modules/{*}/EntryPoints',
            'customPath' => 'tests/unit/testData/EntryPoints/Espo/Custom/EntryPoints',
        );

        $result = array (
          'Download' => '\\tests\\unit\\testData\\EntryPoints\\Espo\\EntryPoints\\Download',
        );

        $this->assertEquals( $result, $this->reflection->invokeMethod('getData', array($paths, $cacheFile)) );
    }

    function testGetDataWithNoCache()
    {
        $this->objects['config']
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->objects['metadata']
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(
                array(
                    'Crm',
                )
            ));

        $cacheFile = 'tests/unit/testData/EntryPoints/cache/entryPoints.php';
        $paths = array(
            'corePath' => 'tests/unit/testData/EntryPoints/Espo/EntryPoints',
             'modulePath' => 'tests/unit/testData/EntryPoints/Espo/Modules/{*}/EntryPoints',
            'customPath' => 'tests/unit/testData/EntryPoints/Espo/Custom/EntryPoints',
        );

        $result = array(
            'Download' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => '\tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule'
        );

        $this->assertEquals( $result, $this->reflection->invokeMethod('getData', array($paths, $cacheFile)) );
    }


    function testGetDataWithNoCacheString()
    {
        $this->objects['config']
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->objects['metadata']
            ->expects($this->never())
            ->method('getModuleList')
            ->will($this->returnValue(
                array(
                    'Crm',
                )
            ));

        $cacheFile = 'tests/unit/testData/EntryPoints/cache/entryPoints.php';
        $path = 'tests/unit/testData/EntryPoints/Espo/EntryPoints';

        $result = array(
            'Download' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
        );

        $this->assertEquals( $result, $this->reflection->invokeMethod('getData', array($path, $cacheFile)) );
    }


    function testGetDataWithCacheFalse()
    {
        $this->objects['config']
            ->expects($this->never())
            ->method('get')
            ->will($this->returnValue(false));

        $this->objects['metadata']
            ->expects($this->never())
            ->method('getModuleList')
            ->will($this->returnValue(
                array(
                    'Crm',
                )
            ));

        $paths = array(
            'corePath' => 'tests/unit/testData/EntryPoints/Espo/EntryPoints',
            'customPath' => 'tests/unit/testData/EntryPoints/Espo/Custom/EntryPoints',
        );

        $result = array(
            'Download' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => '\tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
        );

        $this->assertEquals( $result, $this->reflection->invokeMethod('getData', array($paths)) );
    }



}

?>
