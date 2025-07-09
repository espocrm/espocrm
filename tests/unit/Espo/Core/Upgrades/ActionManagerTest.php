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

namespace tests\unit\Espo\Core\Upgrades;

use Espo\Core\Container;
use Espo\Core\Upgrades\ActionManager;
use Espo\Core\Upgrades\Base;
use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;

use Espo\Core\Utils\File\Manager as FileManager;

class ActionManagerTest extends TestCase
{
    protected $object;
    protected $objects;
    private $reflection;

    protected $params = [
        'name' => 'Extension',
        'params' => [
            'packagePath' => 'tests/unit/testData/Upgrades/data/upload/extensions',
            'backupPath' => 'tests/unit/testData/Upgrades/data/.backup/extensions',

            'scriptNames' => [
                'before' => 'BeforeInstall',
                'after' => 'AfterInstall',
                'beforeUninstall' => 'BeforeUninstall',
                'afterUninstall' => 'AfterUninstall',
            ]
        ],
    ];

    protected function setUp(): void
    {
        $this->objects['container'] = $container = $this->createMock(Container::class);

        $fileManager = $this->createMock(FileManager::class);

        $container
            ->expects($this->any())
            ->method('getByClass')
            ->willReturnMap(
                [
                    [FileManager::class, $fileManager],
                ]
            );

        $this->object = new ActionManager(
            $this->params['name'],
            $container,
            $this->params['params']
        );

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }

    public function testGetObjectExtensionUpload()
    {
        $this->object->setAction(Base::UPLOAD);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Extension\Upload', $class);
    }

    public function testGetObjectExtensionInstall()
    {
        $this->object->setAction(Base::INSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Extension\Install', $class);
    }

    public function testGetObjectExtensionUninstall()
    {
        $this->object->setAction(Base::UNINSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Extension\Uninstall', $class);
    }

    public function testGetObjectExtensionDelete()
    {
        $this->object->setAction(Base::DELETE);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Extension\Delete', $class);
    }

    public function testGetObjectExtensionNotExists()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $this->object->setAction('CustomClass');
        $class = $this->reflection->invokeMethod('getObject');
    }

    public function testGetObjectUpgradeUpload()
    {
        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(Base::UPLOAD);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Upgrade\Upload', $class);
    }

    public function testGetObjectUpgradeInstall()
    {
        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(Base::INSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Upgrade\Install', $class);
    }

    public function testGetObjectUpgradeUninstall()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(Base::UNINSTALL);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Upgrade\Uninstall', $class);

        $class->run([]);
    }

    public function testGetObjectUpgradeDelete()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('managerName', 'Upgrade');
        $this->object->setAction(Base::DELETE);

        $class = $this->reflection->invokeMethod('getObject');
        $this->assertInstanceOf('Espo\Core\Upgrades\Actions\Upgrade\Delete', $class);

        $class->run([]);
    }
}
