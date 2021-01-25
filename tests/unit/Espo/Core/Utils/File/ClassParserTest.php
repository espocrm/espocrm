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

namespace tests\unit\Espo\Core\Utils\File;

use tests\unit\ReflectionHelper;

use Espo\Core\Utils\File\ClassParser;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;

class ClassParserTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected function setUp() : void
    {
        $this->objects['fileManager'] = new FileManager();

        $this->objects['config'] = $this->getMockBuilder('Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
        $this->objects['metadata'] = $this->getMockBuilder('Espo\Core\Utils\Metadata')->disableOriginalConstructor()->getMock();

        $this->dataCache = $this->getMockBuilder(DataCache::class)->disableOriginalConstructor()->getMock();

        $this->object = new ClassParser(
            $this->objects['fileManager'], $this->objects['config'], $this->objects['metadata'], $this->dataCache
        );

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }

    function testGetClassNameHash()
    {
        $paths = [
            'tests/unit/testData/EntryPoints/Espo/EntryPoints',
            'tests/unit/testData/EntryPoints/Espo/Modules/Crm/EntryPoints',
        ];

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => 'tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule'
        ];
        $this->assertEquals($result, $this->reflection->invokeMethod('getClassNameHash', [$paths, ['run']]));
    }

    function testGetDataWithCache()
    {
        $result = [
            'Download' => '\\tests\\unit\\testData\\EntryPoints\\Espo\\EntryPoints\\Download',
        ];

        $this->objects['config']
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(true));

        $cacheKey = 'entryPoints';

        $this->dataCache
            ->expects($this->once())
            ->method('has')
            ->with('entryPoints')
            ->willReturn(true);

        $this->dataCache
            ->expects($this->once())
            ->method('get')
            ->with('entryPoints')
            ->willReturn($result);

        $paths = [
            'corePath' => '/tests/unit/testData/EntryPoints/Espo/EntryPoints',
            'modulePath' => '/tests/unit/testData/EntryPoints/Espo/Modules/{*}/EntryPoints',
            'customPath' => '/tests/unit/testData/EntryPoints/Espo/Custom/EntryPoints',
        ];

        $this->assertEquals($result, $this->reflection->invokeMethod('getData', [$paths, $cacheKey, ['run']]) );
    }

    function testGetDataWithNoCache()
    {
        $this->dataCache
            ->expects($this->once())
            ->method('has')
            ->with('entryPoints')
            ->willReturn(true);

        $this->objects['config']
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->objects['metadata']
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(
                [
                    'Crm',
                ]
            ));

        $cacheKey = 'entryPoints';

        $paths = [
            'corePath' => 'tests/unit/testData/EntryPoints/Espo/EntryPoints',
            'modulePath' => 'tests/unit/testData/EntryPoints/Espo/Modules/{*}/EntryPoints',
            'customPath' => 'tests/unit/testData/EntryPoints/Espo/Custom/EntryPoints',
        ];

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => 'tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule'
        ];

        $this->assertEquals($result, $this->reflection->invokeMethod('getData', [$paths, $cacheKey, ['run']]));
    }

    function testGetDataWithNoCacheString()
    {
        $this->dataCache
            ->expects($this->once())
            ->method('has')
            ->with('entryPoints')
            ->willReturn(true);

        $this->objects['config']
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->objects['metadata']
            ->expects($this->never())
            ->method('getModuleList')
            ->will($this->returnValue(
                [
                    'Crm',
                ]
            ));

        $cacheKey = 'entryPoints';
        $path = 'tests/unit/testData/EntryPoints/Espo/EntryPoints';

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
        ];

        $this->assertEquals($result, $this->reflection->invokeMethod('getData', [$path, $cacheKey, ['run']]));
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
                [
                    'Crm',
                ]
            ));

        $paths = [
            'corePath' => 'tests/unit/testData/EntryPoints/Espo/EntryPoints',
            'customPath' => 'tests/unit/testData/EntryPoints/Espo/Custom/EntryPoints',
        ];

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
        ];

        $this->assertEquals($result, $this->reflection->invokeMethod('getData', [$paths, null, ['run']]));
    }
}
