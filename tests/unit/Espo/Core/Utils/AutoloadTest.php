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

use Espo\Core\Utils\Autoload;
use Espo\Core\Utils\Autoload\Loader;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Resource\PathProvider;
use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    private ?Config\SystemConfig $systemConfig = null;

    private $metadata;
    private $fileManager;
    private $loader;
    private $pathProvider;
    private $autoload;

    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(Config\SystemConfig::class);
        $this->metadata = $this->createMock(Metadata::class);
        $dataCache = $this->createMock(DataCache::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->loader = $this->createMock(Loader::class);
        $this->pathProvider = $this->createMock(PathProvider::class);

        $this->initPathProvider();

        $this->autoload = new Autoload(
            $this->metadata,
            $dataCache,
            $this->fileManager,
            $this->loader,
            $this->pathProvider,
            $this->systemConfig,
        );
    }

    private function initPathProvider(string $rootPath = ''): void
    {
        $this->pathProvider
            ->method('getCustom')
            ->willReturn($rootPath . 'custom/Espo/Custom/Resources/');

        $this->pathProvider
            ->method('getCore')
            ->willReturn($rootPath . 'application/Espo/Resources/');

        $this->pathProvider
            ->method('getModule')
            ->willReturnCallback(
                function (?string $moduleName) use ($rootPath): string {
                    $path = $rootPath . 'application/Espo/Modules/{*}/Resources/';

                    if ($moduleName === null) {
                        return $path;
                    }

                    return str_replace('{*}', $moduleName, $path);
                }
            );
    }

    public function testMerge()
    {
        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->willReturn(['M1', 'M2']);

        $this->systemConfig
            ->expects($this->once())
            ->method('useCache')
            ->willReturn(false);

        $this->fileManager
            ->expects($this->any())
            ->method('isFile')
            ->willReturnMap(
 
                    [
                        ['application/Espo/Resources/autoload.json', false],
                        ['application/Espo/Modules/M1/Resources/autoload.json', true],
                        ['application/Espo/Modules/M2/Resources/autoload.json', true],
                        ['custom/Espo/Custom/Resources/autoload.json', false],
                    ]

            );

        $data1 = [
            'autoloadFileList' => ['f1.php'],
            'psr-4' => [
                't1' => 'r1',
            ],
        ];

        $data2 = [
            'autoloadFileList' => ['f2.php'],
            'psr-4' => [
                't2' => 'r2',
            ],
        ];

        $expectedData = [
            'autoloadFileList' => ['f1.php', 'f2.php'],
            'psr-4' => [
                't1' => 'r1',
                't2' => 'r2',
            ],
        ];

        $this->fileManager
            ->expects($this->any())
            ->method('getContents')
            ->willReturnMap(
                [
                    ['application/Espo/Modules/M1/Resources/autoload.json', json_encode($data1)],
                    ['application/Espo/Modules/M2/Resources/autoload.json', json_encode($data2)],
                ]
            );

        $this->loader
            ->expects($this->once())
            ->method('register')
            ->with($expectedData);

        $this->autoload->register();
    }
}
