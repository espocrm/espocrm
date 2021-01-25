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

namespace tests\unit\Espo\Core\Utils;

use tests\unit\ReflectionHelper;

use Espo\Core\{
    Utils\Route,
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\Metadata,
    Utils\DataCache,
};

class RouteTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $filesPath = 'tests/unit/testData/Routes';

    protected function setUp() : void
    {
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->fileManager = new FileManager();

        $this->metadata = $this->getMockBuilder(Metadata::class)->disableOriginalConstructor()->getMock();

        $this->dataCache = $this->getMockBuilder(DataCache::class)->disableOriginalConstructor()->getMock();

        $this->object = new Route(
            $this->config, $this->metadata, $this->fileManager, $this->dataCache
        );

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
        $this->reflection = NULL;
    }

    public function testUnifyCase1CustomRoutes()
    {
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase1/application/Espo/Resources/routes.json',
            'modulePath' => $this->filesPath . '/testCase1/application/Espo/Modules/{*}/Resources/routes.json',
            'customPath' => $this->filesPath . '/testCase1/custom/Espo/Custom/Resources/routes.json',
        ));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                'Crm',
        )));

        $result = array (
          array (
            'route' => '/Custom/{scope}/{id}/{name}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Custom',
              'action' => 'list',
              'scope' => ':scope',
              'id' => ':id',
              'name' => ':name',
            ),
          ),
          array (
            'route' => '/Activities/{scope}/{id}/{name}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Activities',
              'action' => 'list',
              'scope' => ':scope',
              'id' => ':id',
              'name' => ':name',
            ),
          ),
          array (
            'route' => '/Activities',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Activities',
              'action' => 'listCalendarEvents',
            ),
          ),
          array (
            'route' => '/App/user',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'App',
              'action' => 'user',
            ),
          ),
          array (
            'route' => '/Metadata',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Metadata',
            ),
          ),
          array (
            'route' => '/{controller}/action/{action}',
            'method' => 'post',
            'params' =>
            array (
              'controller' => ':controller',
              'action' => ':action',
            ),
          ),
          array (
            'route' => '/{controller}/action/{action}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => ':controller',
              'action' => ':action',
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->invokeMethod('unify'));
    }

    public function testUnifyCase2ModuleRoutes()
    {
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase2/application/Espo/Resources/routes.json',
            'modulePath' => $this->filesPath . '/testCase2/application/Espo/Modules/{*}/Resources/routes.json',
            'customPath' => $this->filesPath . '/testCase2/custom/Espo/Custom/Resources/routes.json',
        ));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                'Crm',
                'Test',
        )));

        $result = array (
          array (
            'route' => '/Activities/{scope}/{id}/{name}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Activities',
              'action' => 'list',
              'scope' => ':scope',
              'id' => ':id',
              'name' => ':name',
            ),
          ),
          array (
            'route' => '/Activities',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Activities',
              'action' => 'listCalendarEvents',
            ),
          ),
          array (
            'route' => '/Test',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Test',
              'action' => 'listCalendarEvents',
            ),
          ),
          array (
            'route' => '/App/user',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'App',
              'action' => 'user',
            ),
          ),
          array (
            'route' => '/Metadata',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Metadata',
            ),
          ),
          array (
            'route' => '/{controller}/action/{action}',
            'method' => 'post',
            'params' =>
            array (
              'controller' => ':controller',
              'action' => ':action',
            ),
          ),
          array (
            'route' => '/{controller}/action/{action}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => ':controller',
              'action' => ':action',
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->invokeMethod('unify'));
    }

    public function testUnifyCase3ModuleRoutesWithRewrites()
    {
        $this->reflection->setProperty('paths', array(
            'corePath' => $this->filesPath . '/testCase3/application/Espo/Resources/routes.json',
            'modulePath' => $this->filesPath . '/testCase3/application/Espo/Modules/{*}/Resources/routes.json',
            'customPath' => $this->filesPath . '/testCase3/custom/Espo/Custom/Resources/routes.json',
        ));

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                'Crm',
                'Test',
        )));

        $result = array (
          array (
            'route' => '/Activities/{scope}/{id}/{name}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Test',
              'action' => 'list',
              'scope' => ':scope',
              'id' => ':id',
              'name' => ':name',
            ),
          ),
          array (
            'route' => '/Activities',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Activities',
              'action' => 'listCalendarEvents',
            ),
          ),
          array (
            'route' => '/Test',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Test',
              'action' => 'listCalendarEvents',
            ),
          ),
          array (
            'route' => '/App/user',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'App',
              'action' => 'user',
            ),
          ),
          array (
            'route' => '/Metadata',
            'method' => 'get',
            'params' =>
            array (
              'controller' => 'Metadata',
            ),
          ),
          array (
            'route' => '/{controller}/action/{action}',
            'method' => 'post',
            'params' =>
            array (
              'controller' => ':controller',
              'action' => ':action',
            ),
          ),
          array (
            'route' => '/{controller}/action/{action}',
            'method' => 'get',
            'params' =>
            array (
              'controller' => ':controller',
              'action' => ':action',
            ),
          ),
        );

        $this->assertEquals($result, $this->reflection->invokeMethod('unify'));
    }

    public function testUnifyCase4ModuleRoutesWithRewrites()
    {
        // prepare path
        $paths = [
            'corePath'   => $this->filesPath.'/testCase4/application/Espo/Resources/routes.json',
            'modulePath' => $this->filesPath.'/testCase4/application/Espo/Modules/{*}/Resources/routes.json',
            'customPath' => $this->filesPath.'/testCase4/custom/Espo/Custom/Resources/routes.json',
        ];

        $this->reflection->setProperty('paths', $paths);

        $this->metadata
            ->expects($this->once())
            ->method('getModuleList')
            ->will($this->returnValue(array(
                    'Crm',
                    'Test',
                    'TestExt'
        )));

        // prepare expected result
        $result = [
            [
                'route'  => '/Activities/{scope}/{id}/{name}',
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
                'route'  => '/Activities',
                'method' => 'get',
                'params' => [
                    'controller' => 'TestExt',
                    'action'     => 'testExtListCalendarEvents'
                ],
            ],
            [
                'route'  => '/Product',
                'method' => 'get',
                'params' => [
                    'controller' => 'Product',
                    'action'     => 'listProduct'
                ],
            ],
            [
                'route'  => '/Test',
                'method' => 'get',
                'params' => [
                    'controller' => 'Test',
                    'action'     => 'testAction'
                ],
            ],
        ];

        $this->assertEquals($result, $this->reflection->invokeMethod('unify'));
    }
}
