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

namespace tests\unit\Espo\Core\Utils\File;

use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;
use Espo\Core\Utils\File\ClassMap;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Module;
use Espo\Core\Utils\Module\PathProvider;

class ClassMapTest extends TestCase
{
    /** @var ClassMap */
    private $classMap;
    private $dataCache;

    protected $reflection;

    private $systemConfig;

    private $customPath = 'tests/unit/testData/EntryPoints/Espo/Custom/';
    private $corePath = 'tests/unit/testData/EntryPoints/Espo/';
    private $modulePath = 'tests/unit/testData/EntryPoints/Espo/Modules/{*}/';

    /** @var Module */
    private $module;

    protected function setUp(): void
    {
        $fileManager = new FileManager();

        $this->systemConfig = $this->createMock(Config\SystemConfig::class);
        $this->module = $this->createMock(Module::class);
        $this->dataCache = $this->createMock(DataCache::class);

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
            $fileManager,
            $this->module,
            $this->dataCache,
            $pathProvider,
            $this->systemConfig,
        );

        $this->reflection = new ReflectionHelper($this->classMap);
    }

    public function testGetDataWithNoCache1(): void
    {
        $expected = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'TestEntry' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\TestEntry',
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

        $this->systemConfig
            ->expects($this->exactly(2))
            ->method('useCache')
            ->willReturn(false);

        $this->module
            ->expects($this->once())
            ->method('getOrderedList')
            ->willReturn(
                ['Crm']
            );

        $cacheKey = 'entryPoints';

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'TestEntry' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\TestEntry',
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

        $this->systemConfig
            ->expects($this->once())
            ->method('useCache')
            ->willReturn(true);

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
        $this->systemConfig
            ->expects($this->exactly(2))
            ->method('useCache')
            ->willReturn(true);

        $this->module
            ->expects($this->once())
            ->method('getOrderedList')
            ->willReturn(
                ['Crm']
            );

        $cacheKey = 'entryPoints';

        $result = [
            'Download' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\Download',
            'TestEntry' => 'tests\unit\testData\EntryPoints\Espo\EntryPoints\TestEntry',
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
