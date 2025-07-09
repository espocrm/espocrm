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

namespace tests\unit\Espo\Core\Upgrades\Actions;

use Espo\Core\Utils\Id\RecordIdGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;
use Espo\Core\Utils\Util;
use Espo\Core\Container;
use Espo\Core\Upgrades\ActionManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config\ConfigWriter;

class BaseTest extends TestCase
{
    protected $object;
    protected $objects;
    protected $fileManager;
    protected $reflection;
    private $fileManagerOriginal;
    private $config;


    protected $actionManagerParams = [
        'name' => 'Extension',
        'packagePath' => 'tests/unit/testData/Upgrades/data/upload/extensions',
        'backupPath' => 'tests/unit/testData/Upgrades/data/.backup/extensions',

        'scriptNames' => [
            'before' => 'BeforeInstall',
            'after' => 'AfterInstall',
            'beforeUninstall' => 'BeforeUninstall',
            'afterUninstall' => 'AfterUninstall',
        ]
    ];

    protected $currentVersion = '11.5.2';

    protected function setUp(): void
    {
        $container = $this->createMock(Container::class);
        $actionManager = $this->createMock(ActionManager::class);
        $this->config = $this->createMock(Config::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $injectableFactory = $this->createMock(InjectableFactory::class);
        $configWriter = $this->createMock(ConfigWriter::class);
        $log = $this->createMock(Log::class);

        $systemConfig = $this->createMock(Config\SystemConfig::class);

        $map = [
            [Config::class, $this->config],
            [FileManager::class, $this->fileManager],
            [InjectableFactory::class, $injectableFactory],
            [Log::class, $log],
            [Config\SystemConfig::class, $systemConfig]
        ];

        $idGenerator = $this->createMock(RecordIdGenerator::class);
        $idGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturn(Util::generateId());

        $injectableFactory
            ->expects($this->any())
            ->method('createResolved')
            ->willReturnMap([
                [RecordIdGenerator::class, null, $idGenerator]
            ]);

        $injectableFactory
            ->expects($this->any())
            ->method('create')
            ->with(ConfigWriter::class)
            ->willReturn($configWriter);

        $container
            ->expects($this->any())
            ->method('getByClass')
            ->willReturnMap($map);

        $actionManagerParams = $this->actionManagerParams;
        $actionManager
            ->expects($this->once())
            ->method('getParams')
            ->willReturn($actionManagerParams);

        $this->object = new Base($container, $actionManager);

        $this->reflection = new ReflectionHelper($this->object);

        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        /** @noinspection SpellCheckingInspection */
        $this->reflection->setProperty('processId', 'ngkdf54n566n45');

        /* create a package directory with manifest.json file */
        $packagePath = $this->reflection->invokeMethod('getPath');
        $manifestName = $this->reflection->getProperty('manifestName');

        $filename = $packagePath . '/' .$manifestName;

        $this->fileManagerOriginal = new FileManager();

        $this->fileManagerOriginal->putContents($filename, '');
        /* END */
    }

    protected function tearDown() : void
    {
        $this->object = NULL;

        $processId = $this->reflection->getProperty('processId');
        if (isset($processId)) {
            $packagePath = $this->reflection->invokeMethod('getPath');

            $this->fileManagerOriginal->removeInDir($packagePath, true);
        }
    }

    public function testCreateProcessIdWithExists()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $this->reflection->invokeMethod('createProcessId');
    }

    public function testCreateProcessId()
    {
        $this->reflection->setProperty('processId', null);

        $processId = $this->reflection->invokeMethod('createProcessId');
        $this->assertEquals( $processId, $this->reflection->invokeMethod('getProcessId') );
    }

    public function testGetProcessId()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('processId', null);
        $this->reflection->invokeMethod('getProcessId');
    }

    public function testGetManifestIncorrect()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $manifest = '{
            "name": "Upgrade 1.0-b3 to 1.0-b4"
        }';

        $this->fileManager
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($manifest);

        $this->config
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->reflection->invokeMethod('getManifest');
    }

    public function testGetManifest()
    {
        $manifest = '{
            "name": "Extension Test",
            "version": "1.2.0",
            "acceptableVersions": [
            ],
            "releaseDate": "2014-09-25",
            "author": "EspoCRM",
            "description": "My Description"
        }';

        $this->fileManager
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($manifest);

        $this->assertEquals( json_decode($manifest,true), $this->reflection->invokeMethod('getManifest') );
    }

    static public function acceptableVersions()
    {
        return [
          ['11.5.2'],
          [['11.5.2']],
          [['1.4', '11.5.2']],
          ['11.*',],
          ['11.5.*',],
          ['~11.5',],
          ['~11',],
          ['^11.1',],
          ['^11',],
          ['11.1 - 11.9',],
          ['>=11.1',],
          ['<=12',],
          ['>=11 <=12',],
        ];
    }

    #[DataProvider('acceptableVersions')]
    public function testCheckVersions($versions, $currentVersion = null)
    {
        if (!isset($currentVersion)) {
            $currentVersion = $this->currentVersion;
        }

        $this->assertTrue(
            $this->reflection->invokeMethod('checkVersions', [$versions, $currentVersion, 'error'])
        );
    }

    static public function unacceptableVersions()
    {
        return [
          ['1.*',],
          ['11\.*',],
          ['11\.5\.2',],
          ['11.5*',],
          ['11.1-11.9',],
          ['.0.1'],
        ];
    }

    #[DataProvider('unacceptableVersions')]
    public function testCheckVersionsException($versions, $currentVersion = null)
    {
        if (!isset($currentVersion)) {
            $currentVersion = $this->currentVersion;
        }

        $this->expectException('Espo\Core\Exceptions\Error');

        $this->config
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->reflection->invokeMethod('checkVersions', [$versions, $currentVersion, 'error']);
    }

    public function testIsAcceptableEmpty()
    {
        $version = [];

        $this->reflection->setProperty('data', ['manifest' => ['acceptableVersions' => $version]]);
        $this->assertTrue($this->reflection->invokeMethod('isAcceptable') );
    }

    public function testGetPath()
    {
        $packageId = $this->reflection->invokeMethod('getProcessId');
        $packagePath = Util::fixPath($this->actionManagerParams['packagePath'] . '/' . $packageId);

        $this->assertEquals($packagePath, $this->reflection->invokeMethod('getPath') );
        $this->assertEquals($packagePath, $this->reflection->invokeMethod('getPath', ['packagePath']) );

        $postfix = 'z';
        $this->assertEquals($packagePath.$postfix, $this->reflection->invokeMethod('getPath', ['packagePath', true]));

        $backupPath = Util::fixPath($this->actionManagerParams['backupPath'] . '/' . $packageId);

        $this->assertEquals( $backupPath, $this->reflection->invokeMethod('getPath', ['backupPath']) );
    }

    public function testCheckPackageType()
    {
        $this->reflection->setProperty('data', ['manifest' => []]);
        $this->assertTrue( $this->reflection->invokeMethod('checkPackageType') );

        $this->reflection->setProperty('data', ['manifest' => ['type' => 'extension']]);
        $this->assertTrue( $this->reflection->invokeMethod('checkPackageType') );
    }

    public function testCheckPackageTypeUpgrade()
    {
        $this->expectException('Espo\Core\Exceptions\Error');

        $this->config
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->reflection->setProperty('data', ['manifest' => ['type' => 'upgrade']]);

        $this->assertTrue( $this->reflection->invokeMethod('checkPackageType') );
    }
}
