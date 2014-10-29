<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/
namespace tests\Espo\Core\Upgrades;

use Espo\Core\ExtensionManager;
use Espo\Core\UpgradeManager;
use Espo\Core\Upgrades\ActionManager;
use tests\ReflectionHelper;

class ActionManagerTest extends
    \PHPUnit_Framework_TestCase
{

    protected $object;

    protected $objects;

    protected $params = array(
        'name' => 'Extension',
        'params' => array(
            'packagePath' => 'tests/testData/Upgrades/data/upload/extensions',
            'backupPath' => 'tests/testData/Upgrades/data/.backup/extensions',
            'scriptNames' => array(
                'before' => 'BeforeInstall',
                'after' => 'AfterInstall',
                'beforeUninstall' => 'BeforeUninstall',
                'afterUninstall' => 'AfterUninstall',
            )
        ),
    );

    protected $reflection;

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
        $this->setExpectedException('\Espo\Core\Exceptions\Error');
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
        $this->setExpectedException('\Espo\Core\Exceptions\Error');
        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(UpgradeManager::UNINSTALL);
        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Upgrade\Uninstall', $class);
    }

    public function testGetObjectUpgradeDelete()
    {
        $this->setExpectedException('\Espo\Core\Exceptions\Error');
        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(UpgradeManager::DELETE);
        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('\Espo\Core\Upgrades\Actions\Upgrade\Delete', $class);
    }

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\Espo\Core\Container')->disableOriginalConstructor()->getMock();
        $this->object = new ActionManager($this->params['name'], $this->objects['container'], $this->params['params']);
        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}
