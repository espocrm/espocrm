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

namespace tests\unit\Espo\Core\Console;

use Espo\Core\{
    InjectableFactory,
    Log\HandlerListLoader,
    Log\EspoRotatingFileHandlerLoader,
    Log\DefaultHandlerLoader,
    Log\Handler\EspoRotatingFileHandler,
};

use Monolog\{
    Logger,
};

class HandlerListLoaderTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->injectableFactory = $this->getMockBuilder(InjectableFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testLoad1()
    {
        $defaultLoader = $this->getMockBuilder(DefaultHandlerLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listLoader = new HandlerListLoader($this->injectableFactory, $defaultLoader);

        $dataList =  [
            [
                'className' => 'Espo\\Core\\Log\\Handler\\EspoRotatingFileHandler',
                'params' => [
                    'filename' => 'data/logs/test-1.log',
                ],
                'level' => 'DEBUG',
                    'formatter' => [
                        'className' => 'Monolog\\Formatter\\LineFormatter',
                        'params' => [
                        'dateFormat' => 'Y-m-d H:i:s',
                    ],
                ]
            ],
            [
            'loaderClassName' => EspoRotatingFileHandlerLoader::class,
                'params' => [
                    'filename' => 'data/logs/test-2.log',
                ],
                'level' => 'NOTICE',
            ],
        ];

        $handler1 = $this->getMockBuilder(EspoRotatingFileHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $defaultLoader
            ->expects($this->once())
            ->method('load')
            ->with($dataList[0], 'NOTICE')
            ->willReturn($handler1);

        $loader = $this->getMockBuilder(EspoRotatingFileHandlerLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler = $this->getMockBuilder(EspoRotatingFileHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->injectableFactory
            ->expects($this->once())
            ->method('create')
            ->with(EspoRotatingFileHandlerLoader::class)
            ->willReturn($loader);

        $params = [
            'filename' => 'data/logs/test-2.log',
            'level' => Logger::NOTICE,
        ];

        $loader
            ->expects($this->once())
            ->method('load')
            ->with($params)
            ->willReturn($handler);

        $list = $listLoader->load($dataList, 'NOTICE');

        $this->assertEquals(2, count($list));

        $this->assertInstanceOf(EspoRotatingFileHandler::class, $list[0]);
    }
}
