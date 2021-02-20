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
};

class HookManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $filesPath = 'tests/unit/testData/Hooks';

    protected function setUp() : void
    {

        $this->metadata =
            $this->getMockBuilder(Metadata::class)->disableOriginalConstructor()->getMock();

        $this->config =
            $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();

        $this->injectableFactory =
            $this->getMockBuilder(InjectableFactory::class)->disableOriginalConstructor()->getMock();

        $this->dataCache =
            $this->getMockBuilder(DataCache::class)->disableOriginalConstructor()->getMock();

        $this->fileManager = new FileManager();

        $this->object = new HookManager(
            $this->injectableFactory,
            $this->fileManager,
            $this->metadata,
            $this->config,
            $this->dataCache
        );

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
        $this->reflection = NULL;
    }

    public function testIsHookExists()
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
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase1/application/Espo/Hooks',
            'modulePath' => $this->filesPath . '/testCase1/application/Espo/Modules/{*}/Hooks',
            'customPath' => $this->filesPath . '/testCase1/custom/Espo/Custom/Hooks',
        ));

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
                    'className' => 'tests\\unit\\testData\\Hooks\\testCase1\\custom\\Espo\\Custom\\Hooks\\Note\\Mentions',
                    'order' => 7,
                ),
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->getProperty('data'));
    }

    public function testCase2ModuleHook()
    {
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase2/application/Espo/Hooks',
            'modulePath' => $this->filesPath . '/testCase2/application/Espo/Modules/{*}/Hooks',
            'customPath' => $this->filesPath . '/testCase2/custom/Espo/Custom/Hooks',
        ));

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
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase2/application/Espo/Hooks',
            'modulePath' => $this->filesPath . '/testCase2/application/Espo/Modules/{*}/Hooks',
            'customPath' => $this->filesPath . '/testCase2/custom/Espo/Custom/Hooks',
        ));

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
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase3/application/Espo/Hooks',
            'modulePath' => $this->filesPath . '/testCase3/application/Espo/Modules/{*}/Hooks',
            'customPath' => $this->filesPath . '/testCase3/custom/Espo/Custom/Hooks',
        ));

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

    public function noTestGetHookList()
    {
        $this->reflection->setProperty('data', array (
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
                    'className' => 'Espo\\Hooks\\Note\\Btest',
                    'order' => 9,
                ),
                array (
                    'className' => 'Espo\\Hooks\\Note\\Notifications',
                    'order' => 14,
                ),
            ),
          ),
        ));

        $resultBeforeSave = array(
            'Espo\\Hooks\\Common\\CurrencyConverted',
            'Espo\\Hooks\\Common\\Formula',
            'Espo\\Hooks\\Note\\Mentions',
            'Espo\\Hooks\\Common\\NextNumber',
        );

        $resultAfterSave = array(
            'Espo\\Hooks\\Common\\AssignmentEmailNotification',
            'Espo\\Hooks\\Note\\Btest',
            'Espo\\Hooks\\Common\\Stream',
            'Espo\\Hooks\\Common\\Notifications',
            'Espo\\Hooks\\Note\\Notifications',
        );
    }
}
