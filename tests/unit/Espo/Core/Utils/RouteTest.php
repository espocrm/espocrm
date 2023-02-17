<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
 * The interactive user interfaces in modified source and route code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\{
    Utils\Route,
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\Metadata,
    Utils\DataCache,
    Utils\Resource\PathProvider,
    Api\Route as RouteItem,
};

class RouteTest extends \PHPUnit\Framework\TestCase
{
    private $route;

    private $filesPath = 'tests/unit/testData/Routes';

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->fileManager = new FileManager();

        $this->metadata = $this->getMockBuilder(Metadata::class)->disableOriginalConstructor()->getMock();

        $this->dataCache = $this->getMockBuilder(DataCache::class)->disableOriginalConstructor()->getMock();

        $this->pathProvider = $this->createMock(PathProvider::class);

        $this->route = new Route(
            $this->config,
            $this->metadata,
            $this->fileManager,
            $this->dataCache,
            $this->pathProvider
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

    public function testUnifyCase4ModuleRoutesWithRewrites()
    {
        $this->initPathProvider('testCase4');

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->willReturn(
                ['Crm', 'Test', 'TestExt']
            );

        $expected = [
            [
                'adjustedRoute'  => '/Activities/{scope}/{id}/{name}',
                'route'  => '/Activities/:scope/:id/:name',
                'method' => 'get',
                'params' => [
                    'controller' => 'TestExt',
                    'action'     => 'list',
                    'scope'      => ':scope',
                    'id'         => ':id',
                    'name'       => ':name',
                ],
            ],
            [
                'adjustedRoute'  => '/Activities',
                'route'  => '/Activities',
                'method' => 'get',
                'params' => [
                    'controller' => 'TestExt',
                    'action'     => 'testExtListCalendarEvents'
                ],
            ],
            [
                'adjustedRoute'  => '/Product',
                'route'  => '/Product',
                'method' => 'get',
                'params' => [
                    'controller' => 'Product',
                    'action'     => 'listProduct'
                ],
            ],
            [
                'adjustedRoute'  => '/Test',
                'route'  => '/Test',
                'method' => 'get',
                'params' => [
                    'controller' => 'Test',
                    'action'     => 'testAction'
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
