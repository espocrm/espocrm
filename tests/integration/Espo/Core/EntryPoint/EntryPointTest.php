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

namespace integration\Espo\Core\EntryPoint;

use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\Method;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\ApplicationState;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\EntryPoint\OutputEmitter;
use Espo\Core\EntryPoint\RequestFactory;
use Espo\Core\EntryPoint\ResponseFactory;
use Espo\Core\EntryPoint\Starter;
use tests\integration\Core\BaseTestCase;

class EntryPointTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNoAuth(): void
    {
        $this->reCreateApplication(reuse: true, noUser: true);

        $outputEmitter = $this->createMock(OutputEmitter::class);
        $errorOutput = $this->createMock(ErrorOutput::class);

        $request = $this->createRequest(
            method: Method::GET,
            queryParams: [
                'entryPoint' => 'oauthCallback',
            ],
        );

        $response = $this->createMock(ResponseWrapper::class);

        $starter = $this->createStarter(
            request: $request,
            response: $response,
            outputEmitter: $outputEmitter,
            errorOutput: $errorOutput,
        );

        $errorOutput
            ->expects(self::never())
            ->method('processWithBodyPrinting');

        $response
            ->expects(self::once())
            ->method('writeBody')
            ->with(
                $this->callback(function (string $body) {
                    return str_contains($body, "Site URL");
                })
            );

        $starter->start();

        $applicationState = $this->getContainer()->getByClass(ApplicationState::class);

        $this->assertTrue($applicationState->hasUser());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNoPostMethod(): void
    {
        $this->reCreateApplication(reuse: true, noUser: true);

        $outputEmitter = $this->createMock(OutputEmitter::class);
        $errorOutput = $this->createMock(ErrorOutput::class);

        $request = $this->createRequest(
            method: Method::POST,
            queryParams: [
                'entryPoint' => 'oauthCallback',
            ],
        );

        $response = $this->createMock(ResponseWrapper::class);

        $starter = $this->createStarter(
            request: $request,
            response: $response,
            outputEmitter: $outputEmitter,
            errorOutput: $errorOutput,
        );

        $errorOutput
            ->expects(self::once())
            ->method('processWithBodyPrinting');

        $starter->start();

        $applicationState = $this->getContainer()->getByClass(ApplicationState::class);

        $this->assertFalse($applicationState->hasUser());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNotExposed(): void
    {
        $metadata = $this->getMetadata();
        $metadata->set('app', 'entryPoints', [
            'oauthCallback' => [
                'notExposed' => true,
            ],
        ]);
        $metadata->save();

        $this->reCreateApplication(reuse: true, noUser: true);

        $outputEmitter = $this->createMock(OutputEmitter::class);
        $errorOutput = $this->createMock(ErrorOutput::class);

        $request = $this->createRequest(
            method: Method::GET,
            queryParams: [
                'entryPoint' => 'oauthCallback',
            ],
        );

        $response = $this->createMock(ResponseWrapper::class);

        $starter = $this->createStarter(
            request: $request,
            response: $response,
            outputEmitter: $outputEmitter,
            errorOutput: $errorOutput,
        );

        $errorOutput
            ->expects(self::once())
            ->method('processWithBodyPrinting');

        $starter->start();

        $applicationState = $this->getContainer()->getByClass(ApplicationState::class);

        $this->assertFalse($applicationState->hasUser());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAllowedMethods(): void
    {
        $metadata = $this->getMetadata();
        $metadata->set('app', 'entryPoints', [
            'oauthCallback' => [
                'allowedMethods' => ['get', 'post'],
            ],
        ]);
        $metadata->save();

        $this->reCreateApplication(reuse: true, noUser: true);

        $outputEmitter = $this->createMock(OutputEmitter::class);
        $errorOutput = $this->createMock(ErrorOutput::class);

        $request = $this->createRequest(
            method: Method::POST,
            queryParams: [
                'entryPoint' => 'oauthCallback',
            ],
        );

        $response = $this->createMock(ResponseWrapper::class);

        $starter = $this->createStarter(
            request: $request,
            response: $response,
            outputEmitter: $outputEmitter,
            errorOutput: $errorOutput,
        );

        $errorOutput
            ->expects(self::never())
            ->method('processWithBodyPrinting');

        $starter->start();

        $applicationState = $this->getContainer()->getByClass(ApplicationState::class);

        $this->assertTrue($applicationState->hasUser());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthRequired(): void
    {
        $this->reCreateApplication(reuse: true, noUser: true);

        $outputEmitter = $this->createMock(OutputEmitter::class);
        $errorOutput = $this->createMock(ErrorOutput::class);

        $request = $this->createRequest(
            method: Method::GET,
            queryParams: [
                'entryPoint' => 'avatar',
            ],
        );

        $response = $this->createMock(ResponseWrapper::class);

        $starter = $this->createStarter(
            request: $request,
            response: $response,
            outputEmitter: $outputEmitter,
            errorOutput: $errorOutput,
        );

        $errorOutput
            ->expects(self::never())
            ->method('processWithBodyPrinting');

        $response
            ->expects(self::never())
            ->method('writeBody');

        $starter->start();

        $applicationState = $this->getContainer()->getByClass(ApplicationState::class);

        $this->assertFalse($applicationState->hasUser());
    }

    private function createStarter(
        RequestWrapper $request,
        ResponseWrapper $response,
        OutputEmitter $outputEmitter,
        ErrorOutput $errorOutput,
    ): Starter {

        $requestFactory = $this->createMock(RequestFactory::class);
        $responseFactory = $this->createMock(ResponseFactory::class);

        $requestFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn($request);

        $responseFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn($response);

        $binding = BindingContainerBuilder::create()
            ->bindInstance(RequestFactory::class, $requestFactory)
            ->bindInstance(ResponseFactory::class, $responseFactory)
            ->bindInstance(OutputEmitter::class, $outputEmitter)
            ->bindInstance(ErrorOutput::class, $errorOutput)
            ->build();

        return $this->getInjectableFactory()->createWithBinding(Starter::class, $binding);
    }
}
