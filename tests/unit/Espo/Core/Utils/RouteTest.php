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

use Espo\Core\Api\Route as RouteItem;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Resource\PathProvider;
use Espo\Core\Utils\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    private $route;
    private $filesPath = 'tests/unit/testData/Routes';

    private $pathProvider;
    private $metadata;

    protected function setUp(): void
    {
        $fileManager = new FileManager();

        $this->metadata = $this->createMock(Metadata::class);
        $dataCache = $this->createMock(DataCache::class);
        $this->pathProvider = $this->createMock(PathProvider::class);

        $this->route = new Route(
            $this->metadata,
            $fileManager,
            $dataCache,
            $this->pathProvider,
            $this->createMock(Config\SystemConfig::class),
        );
    }

    private function initPathProvider(string $folder): void
    {
        $this->pathProvider
            ->method('getCustom')
            ->willReturn($this->filesPath . '/' . $folder . '/custom/Espo/Custom/Resources/');

        $this->pathProvider
            ->method('getCore')
            ->willReturn($this->filesPath . '/' . $folder . '/application/Espo/Resources/');

        $this->pathProvider
            ->method('getModule')
            ->willReturnCallback(
                function (?string $moduleName) use ($folder): string {
                    $path = $this->filesPath . '/' . $folder . '/application/Espo/Modules/{*}/Resources/';

                    if ($moduleName === null) {
                        return $path;
                    }

                    return str_replace('{*}', $moduleName, $path);
                }
            );
    }

    public function testUnifyCase1CustomRoutes()
    {
        $this->initPathProvider('testCase1');

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->willReturn(
                ['Crm']
            );

        $expected = [
            [
                'adjustedRoute' => '/Custom/{scope}/{id}/{name}',
                'route' => '/Custom/:scope/:id/:name',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Custom',
                        'action' => 'list',
                        'scope' => ':scope',
                        'id' => ':id',
                        'name' => ':name',
                    ],
            ],
            [
                'adjustedRoute' => '/Test',
                'route' => '/Test',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'TestOverridden',
                    ],
            ],
            [
                'adjustedRoute' => '/Activities/{scope}/{id}/{name}',
                'route' => '/Activities/:scope/:id/:name',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Activities',
                        'action' => 'list',
                        'scope' => ':scope',
                        'id' => ':id',
                        'name' => ':name',
                    ],
            ],
            [
                'adjustedRoute' => '/Activities',
                'route' => '/Activities',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Activities',
                        'action' => 'listCalendarEvents',
                    ],
            ],
            [
                'adjustedRoute' => '/App/user',
                'route' => '/App/user',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'App',
                        'action' => 'user',
                    ],
            ],
            [
                'adjustedRoute' => '/Metadata',
                'route' => '/Metadata',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Metadata',
                    ],
            ],
            [
                'adjustedRoute' => '/{controller}/action/{action}',
                'route' => '/:controller/action/:action',
                'method' => 'post',
                'params' =>
                    [
                        'controller' => ':controller',
                        'action' => ':action',
                    ],
            ],
            [
                'adjustedRoute' => '/{controller}/action/{action}',
                'route' => '/:controller/action/:action',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => ':controller',
                        'action' => ':action',
                    ],
            ],
        ];

        $expectedItemList = array_map(
            function (array $item) {
                return new RouteItem(
                    $item['method'],
                    $item['route'],
                    $item['adjustedRoute'],
                    $item['params'] ?? [],
                    $item['noAuth'] ?? false,
                    null
                );
            },
            $expected
        );

        $this->assertEquals($expectedItemList, $this->route->getFullList());
    }

    public function testUnifyCase2ModuleRoutes()
    {
        $this->initPathProvider('testCase2');

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->willReturn(
                ['Crm', 'Test']
            );

        $expected = [
            [
                'adjustedRoute' => '/Test',
                'route' => '/Test',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Test',
                        'action' => 'listCalendarEvents',
                    ],
            ],
            [
                'adjustedRoute' => '/Activities/{scope}/{id}/{name}',
                'route' => '/Activities/:scope/:id/:name',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Activities',
                        'action' => 'list',
                        'scope' => ':scope',
                        'id' => ':id',
                        'name' => ':name',
                    ],
            ],
            [
                'adjustedRoute' => '/Activities',
                'route' => '/Activities',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Activities',
                        'action' => 'listCalendarEvents',
                    ],
            ],
            [
                'adjustedRoute' => '/App/user',
                'route' => '/App/user',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'App',
                        'action' => 'user',
                    ],
            ],
            [
                'adjustedRoute' => '/Metadata',
                'route' => '/Metadata',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Metadata',
                    ],
            ],
            [
                'adjustedRoute' => '/{controller}/action/{action}',
                'route' => '/:controller/action/:action',
                'method' => 'post',
                'params' =>
                    [
                        'controller' => ':controller',
                        'action' => ':action',
                    ],
            ],
            [
                'adjustedRoute' => '/{controller}/action/{action}',
                'route' => '/:controller/action/:action',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => ':controller',
                        'action' => ':action',
                    ],
            ],
        ];

        $expectedItemList = array_map(
            function (array $item) {
                return new RouteItem(
                    $item['method'],
                    $item['route'],
                    $item['adjustedRoute'],
                    $item['params'] ?? [],
                    $item['noAuth'] ?? false,
                    null
                );
            },
            $expected
        );

        $this->assertEquals($expectedItemList, $this->route->getFullList());
    }

    public function testUnifyCase3ModuleRoutesWithRewrites()
    {
        $this->initPathProvider('testCase3');

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->willReturn(
                ['Crm', 'Test']
            );

        $expected = [
            [
                'adjustedRoute' => '/Activities/{scope}/{id}/{name}',
                'route' => '/Activities/:scope/:id/:name',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Test',
                        'action' => 'list',
                        'scope' => ':scope',
                        'id' => ':id',
                        'name' => ':name',
                    ],
            ],
            [
                'adjustedRoute' => '/Test',
                'route' => '/Test',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Test',
                        'action' => 'listCalendarEvents',
                    ],
            ],
            [
                'adjustedRoute' => '/Activities',
                'route' => '/Activities',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Activities',
                        'action' => 'listCalendarEvents',
                    ],
            ],
            [
                'adjustedRoute' => '/App/user',
                'route' => '/App/user',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'App',
                        'action' => 'user',
                    ],
            ],
            [
                'adjustedRoute' => '/Metadata',
                'route' => '/Metadata',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => 'Metadata',
                    ],
            ],
            [
                'adjustedRoute' => '/{controller}/action/{action}',
                'route' => '/:controller/action/:action',
                'method' => 'post',
                'params' =>
                    [
                        'controller' => ':controller',
                        'action' => ':action',
                    ],
            ],
            [
                'adjustedRoute' => '/{controller}/action/{action}',
                'route' => '/:controller/action/:action',
                'method' => 'get',
                'params' =>
                    [
                        'controller' => ':controller',
                        'action' => ':action',
                    ],
            ],
        ];

        $expectedItemList = array_map(
            function (array $item) {
                return new RouteItem(
                    $item['method'],
                    $item['route'],
                    $item['adjustedRoute'],
                    $item['params'] ?? [],
                    $item['noAuth'] ?? false,
                    false
                );
            },
            $expected
        );

        $this->assertEquals($expectedItemList, $this->route->getFullList());
    }
}
