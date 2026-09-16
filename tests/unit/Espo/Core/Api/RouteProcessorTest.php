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

use Espo\Core\Api\AuthBuilderFactory;
use Espo\Core\Api\ControllerActionProcessor;
use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\Method;
use Espo\Core\Api\MiddlewareProvider;
use Espo\Core\Api\ProcessData;
use Espo\Core\Api\Route;
use Espo\Core\Api\Route\ContentType;
use Espo\Core\Api\RouteProcessor;
use Espo\Core\ApplicationUser;
use Espo\Core\Authentication\AuthenticationFactory;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;

class RouteProcessorTest extends TestCase
{
    public function testConsumesFail(): void
    {
        $request = AuthTest::createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => ContentType::TEXT_PLAIN,
            ],
        );

        $route = new Route(
            method: Method::POST,
            route: '/test',
            adjustedRoute: '/test',
            params: [],
            noAuth: false,
            consumes: [
                ContentType::APPLICATION_JSON,
            ],
            actionClassName: null,
        );

        $errorOutput = $this->createMock(ErrorOutput::class);

        $processor = new RouteProcessor(
            authenticationFactory: $this->createMock(AuthenticationFactory::class),
            authBuilderFactory: $this->createMock(AuthBuilderFactory::class),
            errorOutput: $errorOutput,
            config: $this->createMock(Config::class),
            log: $this->createMock(Log::class),
            applicationUser: $this->createMock(ApplicationUser::class),
            actionProcessor: $this->createMock(ControllerActionProcessor::class),
            middlewareProvider: $this->createMock(MiddlewareProvider::class),
            injectableFactory: $this->createMock(InjectableFactory::class),
        );

        $response = (new ResponseFactory())->createResponse();

        $errorOutput->expects(self::once())
            ->method('process')
            ->with(
                self::anything(),
                self::anything(),
                $this->callback(function ($e) {
                    return $e instanceof BadRequest;
                })
            );

        $processor->process(
            processData: new ProcessData(
                route: $route,
                basePath: '',
                routeParams: [],
            ),
            request: $request->toPsr7(),
            response: $response,
        );
    }
}
