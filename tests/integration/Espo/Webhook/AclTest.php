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

namespace tests\integration\Espo\Webhook;

use Espo\Core\ControllerManager;

class AclTest extends \tests\integration\Core\BaseTestCase
{

    public function testRegularUserNoAccess()
    {
        $this->createUser(
            [
                'userName' => 'test',
                'password' => '1',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => ['create'=> true, 'read' => 'own'],
                ],
            ]
        );

        $this->auth('test', '1');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('injectableFactory')->create(ControllerManager::class);

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $request = $this->createRequest('POST', [], ['Content-Type' => 'application/json'], '{"event":"Account.create"}');

        $result = $controllerManager->process('Webhook', 'create', $request, $this->createResponse());
    }

    public function testApiUserNoAccess1()
    {
        $this->createUser(
            [
                'userName' => 'api',
                'type' => 'api',
                'authMethod' => 'ApiKey',
                'apiKey' => 'test-key',
            ],
            [
                'data' => [
                    'Webhook' => false,
                ],
            ]
        );

        $request = $this->createRequest(
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
                'X-Api-Key' => 'test-key',
            ],
            '{"event":"Account.create", "url": "https://test"}'
        );

        $this->auth(null, null, null, 'ApiKey', $request);

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('injectableFactory')->create(ControllerManager::class);

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $result = $controllerManager->process('Webhook', 'create', $request, $this->createResponse());
    }

    public function testApiUserNoAccess2()
    {
        $this->createUser(
            [
                'userName' => 'api',
                'type' => 'api',
                'authMethod' => 'ApiKey',
                'apiKey' => 'test-key',
            ],
            [
                'data' => [
                    'Webhook' => false,
                    'Account' => false,
                ],
            ]
        );

        $request = $this->createRequest(
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
                'X-Api-Key' => 'test-key',
            ],
            '{"event":"Account.create", "url": "https://test"}'
        );

        $this->auth(null, null, null, 'ApiKey', $request);

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('injectableFactory')->create(ControllerManager::class);

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $result = $controllerManager->process('Webhook', 'create', $request, $this->createResponse());
    }

    public function testApiUserHasAccess1()
    {
        $this->createUser(
            [
                'userName' => 'api',
                'type' => 'api',
                'authMethod' => 'ApiKey',
                'apiKey' => 'test-key',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => ['create' => true, 'read' => 'own'],
                ],
            ]
        );

        $request = $this->createRequest(
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
                'X-Api-Key' => 'test-key',
            ],
            '{"event":"Account.create", "url": "https://test"}'
        );

        $this->auth(null, null, null, 'ApiKey', $request);

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('injectableFactory')->create(ControllerManager::class);

        $result = $controllerManager->process('Webhook', 'create', $request, $this->createResponse());

        $this->assertTrue(!empty($result));
    }
}
