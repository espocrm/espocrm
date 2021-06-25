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

use Espo\Core\Utils\File\ClassMap;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Module;

use Espo\Core\Utils\Module\PathProvider;

class ClassMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClassMap
     */
    protected $classMap;

    protected $reflection;

    private $customPath = 'tests/unit/testData/EntryPoints/Espo/Custom/';

    private $corePath = 'tests/unit/testData/EntryPoints/Espo/';

    private $modulePath = 'tests/unit/testData/EntryPoints/Espo/Modules/{*}/';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $module;

    protected function setUp(): void
    {
        $this->fileManager = new FileManager();

        $this->config = $this->createMock(Config::class);
        $this->module = $this->createMock(Module::class);

        $this->dataCache = $this->createMock(DataCache::class);

        $this->log = $this->createMock(Log::class);

        $pathProvider = $this->createMock(PathProvider::class);

        $pathProvider
            ->method('getCustom')
            ->willReturn($this->customPath);

        $pathProvider
            ->method('getCore')
            ->willReturn($this->corePath);

        $pathProvider
            ->method('getModule')
            ->willReturnCallback(
                function (?string $moduleName): string {
                    if ($moduleName === null) {
                        return $this->modulePath;
                    }

                    return str_replace('{*}', $moduleName, $this->modulePath);
                }
            );

        $this->module
            ->method('getOrderedList')
            ->willReturn(['Crm']);

        $this->classMap = new ClassMap(
            $this->fileManager,
            $this->config,
            $this->module,
            $this->dataCache,
            $this->log,
            $pathProvider
        );

        $this->reflection = new ReflectionHelper($this->classMap);
    }

    public function testGetDataWithNoCache1(): void
    {
        $expected = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => 'tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule'
        ];

        $this->assertEquals($expected, $this->classMap->getData('EntryPoints', null, ['run']));
    }

    public function testGetDataWithNoCache2(): void
    {
        $this->dataCache
            ->expects($this->once())
            ->method('has')
            ->with('entryPoints')
            ->willReturn(true);

        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->module
            ->expects($this->once())
            ->method('getOrderedList')
            ->will($this->returnValue(
                ['Crm']
            ));

        $cacheKey = 'entryPoints';

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => 'tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule',
       ];

        $this->assertEquals(
            $result,
            $this->classMap->getData('EntryPoints', $cacheKey, ['run'])
        );
    }

    public function testGetDataWithCache(): void
    {
        $result = [
            'Download' => 'tests\\unit\\testData\\EntryPoints\\Espo\\EntryPoints\\Download',
        ];

        $this->config
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

        $this->module
            ->expects($this->never())
            ->method('getOrderedList');

        $this->assertEquals($result, $this->classMap->getData('EntryPoints', $cacheKey, ['run']));
    }

    public function testGetDataWithNoCacheString(): void
    {
        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->with('useCache')
            ->will($this->returnValue(true));

        $this->module
            ->expects($this->once())
            ->method('getOrderedList')
            ->will($this->returnValue(
                ['Crm']
            ));

        $cacheKey = 'entryPoints';

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => 'tests\unit\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule',
        ];

        $this->dataCache
            ->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);

        $this->dataCache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->assertEquals(
            $result,
            $this->classMap->getData('EntryPoints', $cacheKey, ['run'])
        );
    }
}
