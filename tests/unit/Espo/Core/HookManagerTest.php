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

namespace tests\unit\Espo\Core;

use tests\unit\ReflectionHelper;

use Espo\Core\{
    HookManager,
    InjectableFactory,
    Utils\Metadata,
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\DataCache,
    Utils\Log,
    Utils\Module\PathProvider,
};

class HookManagerTest extends \PHPUnit\Framework\TestCase
{
    private $hookManager;

    private $filesPath = 'tests/unit/testData/Hooks';

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(Metadata::class);
            $this->getMockBuilder(Metadata::class)->disableOriginalConstructor()->getMock();

        $this->config = $this->createMock(Config::class);

        $this->injectableFactory = $this->createMock(InjectableFactory::class);

        $this->dataCache = $this->createMock(DataCache::class);

        $this->fileManager = new FileManager();

        $this->pathProvider = $this->createMock(PathProvider::class);

        $this->hookManager = new HookManager(
            $this->injectableFactory,
            $this->fileManager,
            $this->metadata,
            $this->config,
            $this->dataCache,
            $this->createMock(Log::class),
            $this->pathProvider
        );

        $this->reflection = new ReflectionHelper($this->hookManager);
    }

    private function initPathProvider(string $folder): void
    {
        $this->pathProvider
            ->method('getCustom')
            ->willReturn($this->filesPath . '/' . $folder . '/custom/Espo/Custom/');

        $this->pathProvider
            ->method('getCore')
            ->willReturn($this->filesPath . '/' . $folder . '/application/Espo/');

        $this->pathProvider
            ->method('getModule')
            ->willReturnCallback(
                function (?string $moduleName) use ($folder): string {
                    $path = $this->filesPath . '/' . $folder . '/application/Espo/Modules/{*}/';

                    if ($moduleName === null) {
                        return $path;
                    }

                    return str_replace('{*}', $moduleName, $path);
                }
            );
    }

    public function testHookExists(): void
    {
        $data = array (
            'Espo\\Hooks\\Note\\Stream' => 8,
            'Espo\\Hooks\\Note\\Mentions' => 9,
            'Espo\\Hooks\\Note\\Notifications' => 14,
        );

        $data = array (
          array (
            'className' => 'Espo\\Hooks\\Note\\Stream',
            'order' => 8,
          ),
          array (
            'className' => 'Espo\\Hooks\\Note\\Mentions',
            'order' => 9,
          ),
          array (
            'className' => 'Espo\\Hooks\\Note\\Notifications',
            'order' => 14,
          ),
        );

        $this->assertTrue(
            $this->reflection->invokeMethod('hookExists', array('Espo\\Hooks\\Note\\Mentions', $data))
        );
        $this->assertTrue(
            $this->reflection->invokeMethod('hookExists', array('Espo\\Modules\\Crm\\Hooks\\Note\\Mentions', $data))
        );
        $this->assertTrue(
            $this->reflection->invokeMethod('hookExists', array('Espo\\Modules\\Test\\Hooks\\Note\\Mentions', $data))
        );
        $this->assertTrue(
            $this->reflection->invokeMethod('hookExists', array('Espo\\Modules\\Test\\Hooks\\Common\\Stream', $data))
        );
        $this->assertFalse(
            $this->reflection->invokeMethod('hookExists', array('Espo\\Hooks\\Note\\TestHook', $data))
        );
    }

    public function testSortHooks()
    {
        $data = array (
            'Common' =>
            array (
              'afterSave' =>
              array (
                array (
                    'className' => 'Espo\\Hooks\\Common\\AssignmentEmailNotification',
                    'order' => 9,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\Notifications',
                    'order' => 10,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\Stream',
                    'order' => 9,
                ),
              ),
              'beforeSave' =>
              array (
                array (
                    'className' => 'Espo\\Hooks\\Common\\Formula',
                    'order' => 5,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\NextNumber',
                    'order' => 10,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\CurrencyConverted',
                    'order' => 1,
                ),
              ),
            ),
            'Note' =>
            array (
              'beforeSave' =>
              array (
                array (
                    'className' => 'Espo\\Hooks\\Note\\Mentions',
                    'order' => 9,
                ),
              ),
              'afterSave' =>
              array (
                array (
                    'className' => 'Espo\\Hooks\\Note\\Notifications',
                    'order' => 14,
                ),
              ),
            ),
        );

        $result = array (
          'Common' =>
          array (
            'afterSave' =>
            array (
                array (
                    'className' => 'Espo\\Hooks\\Common\\AssignmentEmailNotification',
                    'order' => 9,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\Stream',
                    'order' => 9,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\Notifications',
                    'order' => 10,
                ),
            ),
            'beforeSave' =>
            array (
                array (
                    'className' => 'Espo\\Hooks\\Common\\CurrencyConverted',
                    'order' => 1,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\Formula',
                    'order' => 5,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Common\\NextNumber',
                    'order' => 10,
                ),
            ),
          ),
          'Note' =>
          array (
            'beforeSave' =>
            array (
                array (
                    'className' => 'Espo\\Hooks\\Note\\Mentions',
                    'order' => 9,
                ),
            ),
            'afterSave' =>
            array (
                array (
                    'className' => 'Espo\\Hooks\\Note\\Notifications',
                    'order' => 14,
                ),
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->invokeMethod('sortHooks', array($data)) );
    }

    public function testCase1CustomHook()
    {
        $this->initPathProvider('testCase1');

        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                'Crm',
                'Test',
            )));

        $this->reflection->invokeMethod('loadHooks');

        $result = array (
          'Note' =>
          array (
            'beforeSave' =>
            [
                [
                    'className' => 'tests\\unit\\testData\\Hooks\\testCase1\\custom\\Espo\\Custom\\Hooks\\Note\\Mentions',
                    'order' => 7,
                ],
            ],
          ),
        );

        $this->assertEquals($result, $this->reflection->getProperty('data'));
    }

    public function testCase2ModuleHook1()
    {
        $this->initPathProvider('testCase2');

        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                'Crm',
                'Test',
            )));

        $this->reflection->invokeMethod('loadHooks');

        $result = array (
          'Note' =>
          array (
            'beforeSave' =>
            array (
                array (
                    'className' =>
                    'tests\\unit\\testData\\Hooks\\testCase2\\application\\Espo\\Modules\\Crm\\Hooks\\Note\\Mentions',
                    'order' => 9,
                ),
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->getProperty('data'));
    }

    public function testCase2ModuleHookReverseModuleOrder()
    {
        $this->initPathProvider('testCase2');

        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                'Test',
                'Crm',
            )));

        $this->reflection->invokeMethod('loadHooks');

        $result = array (
          'Note' =>
          array (
            'beforeSave' =>
            array (
                array (
                    'className' =>
                        'tests\\unit\\testData\\Hooks\\testCase2\\application\\Espo\\Modules\\Test\\Hooks\\Note\\Mentions',
                    'order' => 9,
                ),
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->getProperty('data'));
    }

    public function testCase3CoreHook()
    {
        $this->initPathProvider('testCase3');

        $this->config
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(false));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
            )));

        $this->reflection->invokeMethod('loadHooks');

        $result = array (
          'Note' =>
          array (
            'beforeSave' =>
            array (
                array (
                    'className' => 'tests\\unit\\testData\\Hooks\\testCase3\\application\\Espo\\Hooks\\Note\\Mentions',
                    'order' => 9,
                ),
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->getProperty('data'));
    }
}
