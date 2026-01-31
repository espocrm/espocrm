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

namespace tests\integration\Espo\Core\Authentication;

use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\Response;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Authentication\Util\DelayUtil;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;
use Espo\Core\Utils\Config\ConfigWriter;
use Slim\Psr7\Factory\ServerRequestFactory;
use tests\integration\Core\BaseTestCase;

class FailedLoginAttemptsTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUsernameFailedLogin(): void
    {
        $delay = 5;

        $configWriter = $this->getInjectableFactory()->create(ConfigWriter::class);
        $configWriter->setMultiple([
            'authUsernameFailedAttemptsLimitEnabled' => true,
            'authUsernameFailedAttemptsDelay' => $delay,
            'authMaxUsernameFailedAttemptNumber' => 3,
        ]);
        $configWriter->save();

        $delayUtil = $this->createMock(DelayUtil::class);

        $app = $this->createApplication(
            binding: new class ($delayUtil) implements BindingProcessor {

                public function __construct(private DelayUtil $delayUtil) {}

                public function process(Binder $binder): void
                {
                    $binder->bindInstance(DelayUtil::class, $this->delayUtil);
                }
            },
        );
        $this->setApplication($app);

        $delayUtil->expects($this->once())
            ->method('delay')
            ->with($delay * 1000);

        $username = 'test';

        $data = AuthenticationData::create()
            ->withUsername($username)
            ->withPassword('1');

        $time = microtime(true);

        $authentication = $this->getInjectableFactory()->create(Authentication::class);

        $request = $this->createApiRequest($time, '1.0.0.1', $username);
        $response = $this->createMock(Response::class);
        $authentication->login($data, $request, $response);

        $request = $this->createApiRequest($time, '1.0.0.2', $username);
        $response = $this->createMock(Response::class);
        $authentication->login($data, $request, $response);

        $request = $this->createApiRequest($time, '1.0.0.3', $username);
        $response = $this->createMock(Response::class);
        $authentication->login($data, $request, $response);

        $request = $this->createApiRequest($time, '1.0.0.4', $username);
        $response = $this->createMock(Response::class);
        $authentication->login($data, $request, $response);
    }

    private function createApiRequest(float $time, string $ipAddress, string $username): RequestWrapper
    {
        $authorization = 'Basic ' . base64_encode($username . ':1');

        $request = (new ServerRequestFactory())->createServerRequest('POST', 'http://localhost/api/v1/App/user', [
            'REMOTE_ADDR' => $ipAddress,
            'REQUEST_TIME_FLOAT' => $time,
        ]);

        $request = $request->withHeader(HeaderKey::AUTHORIZATION, $authorization);

        return new RequestWrapper($request);
    }
}
