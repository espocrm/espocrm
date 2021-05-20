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

namespace tests\unit\Espo\Core\Upgrades;

use tests\unit\ReflectionHelper,
    Espo\Core\Upgrades\ExtensionManager,
    Espo\Core\Upgrades\UpgradeManager;

use Espo\Core\Utils\File\Manager as FileManager;

class ActionManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $params = array(
        'name' => 'Extension',
        'params' => array(
            'packagePath' => 'tests/unit/testData/Upgrades/data/upload/extensions',
            'backupPath' => 'tests/unit/testData/Upgrades/data/.backup/extensions',

            'scriptNames' => array(
                'before' => 'BeforeInstall',
                'after' => 'AfterInstall',
                'beforeUninstall' => 'BeforeUninstall',
                'afterUninstall' => 'AfterUninstall',
            )
        ),
    );

    protected function setUp() : void
    {
        $this->objects['container'] =
            $container = $this->getMockBuilder('Espo\Core\Container')->disableOriginalConstructor()->getMock();

        $fileManager = $this->getMockBuilder(FileManager::class)->disableOriginalConstructor()->getMock();

        $container
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap([
                    ['fileManager', $fileManager],
                ])
            );

        $this->object = new \Espo\Core\Upgrades\ActionManager(
            $this->params['name'], $this->objects['container'], $this->params['params']
        );

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }

    public function testGetObjectExtensionUpload()
    {
        $this->object->setAction(ExtensionManager::UPLOAD);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Extension\Upload', $class);
    }

    public function testGetObjectExtensionInstall()
    {
        $this->object->setAction(ExtensionManager::INSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Extension\Install', $class);
    }

    public function testGetObjectExtensionUninstall()
    {
        $this->object->setAction(ExtensionManager::UNINSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Extension\Uninstall', $class);
    }

    public function testGetObjectExtensionDelete()
    {
        $this->object->setAction(ExtensionManager::DELETE);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Extension\Delete', $class);
    }

    public function testGetObjectExtensionNotExists()
    {
        $this->expectException('\Espo\Core\Exceptions\Error');

        $this->object->setAction('CustomClass');
        $class = $this->reflection->invokeMethod('getObject');
    }

    public function testGetObjectUpgradeUpload()
    {
        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(UpgradeManager::UPLOAD);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Upgrade\Upload', $class);
    }

    public function testGetObjectUpgradeInstall()
    {
        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(UpgradeManager::INSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Upgrade\Install', $class);
    }

    public function testGetObjectUpgradeUninstall()
    {
        $this->expectException('\Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(UpgradeManager::UNINSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Upgrade\Uninstall', $class);

        $class->run(array());
    }

    public function testGetObjectUpgradeDelete()
    {
        $this->expectException('\Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(UpgradeManager::DELETE);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Upgrade\Delete', $class);

        $class->run(array());
    }

}
