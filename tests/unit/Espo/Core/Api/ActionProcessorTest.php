<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\unit\Espo\Core\Api;

use Espo\Core\Api\ControllerActionProcessor;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\ClassFinder;
use PHPUnit\Framework\TestCase;
use tests\unit\testClasses\Controllers\TestController;

class ActionProcessorTest extends TestCase
{

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAction1()
    {
        $classFinder = $this->createMock(ClassFinder::class);
        $injectableFactory = $this->createMock(InjectableFactory::class);
        $request = $this->createMock(RequestWrapper::class);
        $response = $this->createMock(ResponseWrapper::class);

        $actionProcessor = new ControllerActionProcessor($injectableFactory, $classFinder);

        $controller = $this->getMockBuilder(TestController::class)->disableOriginalConstructor()->getMock();

        $classFinder
            ->expects($this->once())
            ->method('find')
            ->with('Controllers', 'Test')
            ->willReturn(TestController::class);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $injectableFactory
            ->expects($this->once())
            ->method('createWith')
            ->with(TestController::class, ['name' => 'Test'])
            ->willReturn($controller);

        $controller
            ->expects($this->once())
            ->method('postActionHello')
            ->with($request, $response);

        $actionProcessor->process('Test', 'hello', $request, $response);
    }
}
