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

namespace tests\integration\Espo\Webhook;

use Espo\Core\Api\ControllerActionProcessor;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Exceptions\Forbidden;
use tests\integration\Core\BaseTestCase;

class AclTest extends BaseTestCase
{
    public function testRegularUserNoAccess(): void
    {
        $this->createUser(
            [
                'userName' => 'test',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => ['create'=> 'yes', 'read' => 'own'],
                ],
            ]
        );

        $this->authenticate('test');

        $processor = $this->getInjectableFactory()->create(ControllerActionProcessor::class);

        $this->expectException(Forbidden::class);

        $request = $this
            ->createRequest('POST', [], ['Content-Type' => 'application/json'], '{"event":"Account.create"}');

        $processor->process('Webhook', 'create', $request, $this->createResponse());
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

        $this->getDataManager()->clearCache();

        $request = $this->createRequest(
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
                'X-Api-Key' => 'test-key',
            ],
            '{"event":"Account.create", "url": "https://test.com"}'
        );

        $this->authenticate(method: 'ApiKey', request: $request);

        $processor = $this->getInjectableFactory()->create(ControllerActionProcessor::class);

        $this->expectException(Forbidden::class);

        $processor->process('Webhook', 'create', $request, $this->createResponse());
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

        $this->getDataManager()->clearCache();

        $request = $this->createRequest(
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
                'X-Api-Key' => 'test-key',
            ],
            '{"event":"Account.create", "url": "https://test.com"}'
        );

        $this->authenticate(method: 'ApiKey', request: $request);

        $processor = $this->getInjectableFactory()->create(ControllerActionProcessor::class);

        $this->expectException(Forbidden::class);

        $processor->process('Webhook', 'create', $request, $this->createResponse());
    }

    public function testApiUserHasAccess1()
    {
        $configWriter = $this->getInjectableFactory()->create(ConfigWriter::class);
        $configWriter->set('webhookAllowedAddressList', ['test.com:443']);
        $configWriter->save();

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
                    'Account' => ['create' => 'yes', 'read' => 'own'],
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
            '{"event":"Account.create", "url": "https://test.com"}'
        );

        $this->authenticate(method: 'ApiKey', request: $request);

        $processor = $this->getInjectableFactory()->create(ControllerActionProcessor::class);

        $response = $this->createMock(ResponseWrapper::class);

        $response
            ->expects($this->once())
            ->method('writeBody');

        $processor->process('Webhook', 'create', $request, $response);
    }

    public function testApiUserHasAccessDelete(): void
    {
        $user = $this->createUser(
            [
                'userName' => 'api',
                'type' => 'api',
                'authMethod' => 'ApiKey',
                'apiKey' => 'test-key',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => ['create' => 'yes', 'read' => 'own'],
                ],
            ]
        );

        $em = $this->getEntityManager();

        $webhook = $em->createEntity('Webhook', [
            'event' => 'Account.create',
            'url' => 'https://test.com',
            'userId' => $user->getId(),
        ]);

        $request = $this->createRequest(
            'DELETE',
            [],
            [
                'Content-Type' => 'application/json',
                'X-Api-Key' => 'test-key',
            ],
            null,
            [
                'id' => $webhook->getId(),
            ]
        );

        $this->authenticate(method: 'ApiKey', request: $request);

        $em = $this->getEntityManager();

        $response = $this->createMock(ResponseWrapper::class);

        $processor = $this->getInjectableFactory()->create(ControllerActionProcessor::class);

        $processor->process('Webhook', 'delete', $request, $response);

        $fetchedWebhook = $em->getEntityById('Webhook', $webhook->getId());

        $this->assertNull($fetchedWebhook);
    }
}
