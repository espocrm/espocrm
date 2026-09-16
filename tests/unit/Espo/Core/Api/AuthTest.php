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

use Espo\Core\Api\Auth;
use Espo\Core\Api\Method;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\Response;
use Espo\Core\Api\Route\ContentType;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Authentication\Login\MethodResolver;
use Espo\Core\Authentication\Result;
use Espo\Core\Authentication\Result\Data;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class AuthTest extends TestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testProcessNoAuth(): void
    {
        $request = $this->createRequestInstance();

        $response = $this->createMock(Response::class);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('login')
            ->willReturn(
                Result::secondStepRequired($this->createMock(User::class), Data::create())
            );

        $auth = new Auth(
            log: $this->createMock(Log::class),
            authentication: $authentication,
            methodResolver: $this->createMock(MethodResolver::class),
            authRequired: false,
        );

        $result = $auth->process($request, $response);

        $this->assertTrue($result->isResolvedUseNoAuth());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testProcessAuthFail(): void
    {
        $request = $this->createRequestInstance();

        $response = $this->createMock(Response::class);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('login')
            ->willReturn(
                Result::fail()
            );

        $auth = new Auth(
            log: $this->createMock(Log::class),
            authentication: $authentication,
            methodResolver: $this->createMock(MethodResolver::class),
        );

        $result = $auth->process($request, $response);

        $this->assertFalse($result->isResolved());
        $this->assertFalse($result->isResolvedUseNoAuth());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCgiHeader(): void
    {
        $request = $this->createRequest(
            method: Method::GET,
            headers: [
                'Http-Espo-Cgi-Auth' => 'Basic ' . base64_encode('test:1'),
            ],
        );

        $response = $this->createMock(Response::class);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('login')
            ->with(
                AuthenticationData::create()
                    ->withUsername('test')
                    ->withPassword('1')
            )
            ->willReturn(
                Result::success($this->createMock(User::class))
            );

        $auth = new Auth(
            log: $this->createMock(Log::class),
            authentication: $authentication,
            methodResolver: $this->createMock(MethodResolver::class),
            authRequired: true,
        );

        $result = $auth->process($request, $response);

        $this->assertTrue($result->isResolved());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCookie(): void
    {
        $request = $this->createRequest(
            method: Method::GET,
            cookieParams: [
                'auth-token' => '001',
            ],
        );

        $response = $this->createMock(Response::class);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('login')
            ->with(
                AuthenticationData::create()
                    ->withPassword('001')
                    ->withByTokenOnly(true)
            )
            ->willReturn(
                Result::success($this->createMock(User::class))
            );

        $auth = new Auth(
            log: $this->createMock(Log::class),
            authentication: $authentication,
            methodResolver: $this->createMock(MethodResolver::class),
            authRequired: true,
        );

        $result = $auth->process($request, $response);

        $this->assertTrue($result->isResolved());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIgnoreCookie(): void
    {
        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Sec-Fetch-Mode' => 'navigate',
                'Content-Type' => ContentType::MULTIPART_FORM_DATA . '; charset=utf-8'
            ],
            cookieParams: [
                'auth-token' => '001',
            ]
        );

        $response = $this->createMock(Response::class);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::never())
            ->method('login');

        $auth = new Auth(
            log: $this->createMock(Log::class),
            authentication: $authentication,
            methodResolver: $this->createMock(MethodResolver::class),
            authRequired: true,
        );

        $result = $auth->process($request, $response);

        $this->assertFalse($result->isResolved());
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $queryParams
     * @param array<string, string> $cookieParams
     * @noinspection PhpSameParameterValueInspection
     */
    private function createRequest(
        string $method,
        array $queryParams = [],
        array $headers = [],
        ?string $body = null,
        array $cookieParams = [],
    ): RequestWrapper {

        $request = (new RequestFactory())
            ->createRequest($method, 'http://localhost/?' . http_build_query($queryParams));

        if (!$request instanceof ServerRequestInterface) {
            throw new RuntimeException();
        }


        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withCookieParams($cookieParams);

        if ($body) {
            $request = $request->withBody(
                (new StreamFactory)->createStream($body)
            );
        }

        return new RequestWrapper($request, '', []);
    }

    private function createRequestInstance(): RequestWrapper
    {
        return $this->createRequest(
            method: 'POST',
            headers: [
                'Content-Type' => 'application/json',
                HeaderKey::AUTHORIZATION => base64_encode('test:1'),
            ],
            body: Json::encode((object) []),
        );
    }
}
