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

namespace tests\unit\Espo\Core\Utils;

use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigFileManager;

class ConfigTest extends TestCase
{
    private ?Config $config = null;

    private $defaultTestConfig = 'tests/unit/testData/Utils/Config/config.php';
    private $configPath = 'tests/unit/testData/cache/config.php';
    private $systemConfigPath = 'tests/unit/testData/Utils/Config/systemConfig.php';
    private $internalConfigPath = 'tests/unit/testData/cache/config-internal.php';

    private $reflection;
    private $fileManager;

    protected function setUp(): void
    {
        $this->fileManager = new ConfigFileManager;

        /*copy defaultTestConfig file to cache*/
        if (!file_exists($this->configPath)) {
            copy($this->defaultTestConfig, $this->configPath);
        }

        $this->config = new Config($this->fileManager);

        $this->reflection = new ReflectionHelper($this->config);

        $this->reflection->setProperty('configPath', $this->configPath);
        $this->reflection->setProperty('systemConfigPath', $this->systemConfigPath);
        $this->reflection->setProperty('internalConfigPath', $this->internalConfigPath);
    }

    protected function tearDown(): void
    {
        $this->config = NULL;
    }

    public function testLoadConfig()
    {
        $this->assertArrayHasKey('database', $this->reflection->invokeMethod('getData', []));

        $this->assertArrayHasKey('dateFormat', $this->reflection->invokeMethod('getData', []));
    }

    public function testGet1(): void
    {
        $result = [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'espocrm',
            'user' => 'root',
            'password' => '',
        ];

        $this->assertEquals($result, $this->config->get('database'));

        $result = 'pdo_mysql';
        $this->assertEquals($result, $this->config->get('database.driver'));


        $result = 'YYYY-MM-DD';
        $this->assertEquals($result, $this->config->get('dateFormat'));

        $this->assertTrue($this->config->get('isInstalled'));
    }

    public function testSystemConfigMerge()
    {
        $configDataWithoutSystem = $this->fileManager->getPhpContents($this->configPath);
        $this->assertArrayNotHasKey('systemItems', $configDataWithoutSystem);
        $this->assertArrayNotHasKey('adminItems', $configDataWithoutSystem);

        $configData = $this->reflection->invokeMethod('getData', []);

        $this->assertArrayHasKey('systemItems', $configData);
        $this->assertArrayHasKey('adminItems', $configData);
    }

    public function testGet2(): void
    {
        $fileManager = $this->createMock(ConfigFileManager::class);

        $fileManager
            ->method('isFile')
            ->willReturnMap(
                [
                    ['data/config.php', true],
                    ['data/config-internal.php', true],
                    ['application/Espo/Resources/defaults/systemConfig.php', true],
                    ['data/config-override.php', false],
                    ['data/config-internal-override.php', false],
                ]
            );

        $data = [
            'test1' => '1',
        ];

        $dataInternal = [
            'test2' => '2',
        ];

        $dataSystem = [
            'test3' => '3',
        ];

        $fileManager
            ->method('getPhpContents')
            ->willReturnMap(
                [
                    ['data/config.php', $data],
                    ['data/config-internal.php', $dataInternal],
                    ['application/Espo/Resources/defaults/systemConfig.php', $dataSystem],
                ]
            );


        $config = new Config($fileManager);

        $this->assertEquals('1', $config->get('test1'));
        $this->assertEquals('2', $config->get('test2'));
        $this->assertEquals('3', $config->get('test3'));

        $this->assertFalse($config->isInternal('test1'));
        $this->assertTrue($config->isInternal('test2'));
        $this->assertFalse($config->isInternal('test3'));

        $this->assertEquals(
            (object) [
                'test1' => '1',
                'test3' => '3',
            ],
            $config->getAllNonInternalData(),
        );
    }

    public function testGet3(): void
    {
        $fileManager = $this->createMock(ConfigFileManager::class);

        $fileManager
            ->method('isFile')
            ->willReturnMap(
                [
                    ['data/config.php', true],
                    ['data/config-internal.php', true],
                    ['application/Espo/Resources/defaults/systemConfig.php', true],
                    ['data/config-override.php', false],
                    ['data/config-internal-override.php', false],
                ]
            );

        $data = [
            'a' => [
                '1' => 'a1',
            ],
            'b' => (object) [
                '1' => 'b1',
            ],
            'c' => 'c',
            'd' => ['d'],
        ];

        $dataInternal = [
        ];

        $dataSystem = [
        ];

        $fileManager
            ->method('getPhpContents')
            ->willReturnMap(
                [
                    ['data/config.php', $data],
                    ['data/config-internal.php', $dataInternal],
                    ['application/Espo/Resources/defaults/systemConfig.php', $dataSystem],
                ]
            );

        $config = new Config($fileManager);

        $this->assertEquals(['1' => 'a1'], $config->get('a'));
        $this->assertEquals('a1', $config->get('a.1'));
        $this->assertEquals('b1', $config->get('b.1'));
        $this->assertEquals('c', $config->get('c'));
        $this->assertEquals(['d'], $config->get('d'));

        $this->assertFalse($config->has('a.2'));
        $this->assertTrue($config->has('a.1'));

        $this->assertTrue($config->has('a'));
        $this->assertFalse($config->has('0'));
    }

    public function testGetWithOverride(): void
    {
        $fileManager = $this->createMock(ConfigFileManager::class);

        $fileManager
            ->method('isFile')
            ->willReturnMap(
                [
                    ['application/Espo/Resources/defaults/systemConfig.php', true],
                    ['data/config.php', true],
                    ['data/config-internal.php', true],
                    ['data/config-override.php', true],
                    ['data/config-internal-override.php', true],
                ]
            );

        $dataSystem = [];

        $data = [
            'a' => 'a',
            'c' => 'c',
            'b' => 'b0',
        ];

        $dataInternal = [
            'b' => 'b0',
            'e' => 'e0',
        ];

        $dataOverride = [
            'c' => 'c1',
        ];

        $dataInternalOverride = [
            'e' => 'e1',
            'h' => 'h1',
        ];

        $fileManager
            ->method('getPhpContents')
            ->willReturnMap(
                [
                    ['application/Espo/Resources/defaults/systemConfig.php', $dataSystem],
                    ['data/config.php', $data],
                    ['data/config-internal.php', $dataInternal],
                    ['data/config-override.php', $dataOverride],
                    ['data/config-internal-override.php', $dataInternalOverride],
                ]
            );

        $config = new Config($fileManager);

        $this->assertEquals('a', $config->get('a'));

        $this->assertEquals('c1', $config->get('c'));
        $this->assertEquals('e1', $config->get('e'));
        $this->assertEquals('h1', $config->get('h'));

        $this->assertFalse($config->isInternal('c'));
        $this->assertTrue($config->isInternal('e'));
        $this->assertTrue($config->isInternal('h'));
        $this->assertTrue($config->isInternal('b'));

        $nonInternalData = $config->getAllNonInternalData();

        $this->assertTrue(isset($nonInternalData->a));
        $this->assertTrue(isset($nonInternalData->c));
        $this->assertFalse(isset($nonInternalData->e));
        $this->assertFalse(isset($nonInternalData->h));
    }
}
