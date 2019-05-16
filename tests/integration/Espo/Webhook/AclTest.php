<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class AclTest extends \tests\integration\Core\BaseTestCase
{
    /*protected $dataFile = 'User/Login.php';

    protected $userName = 'admin';
    protected $password = '1';*/


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

        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = [];
        $data = '{"event":"Account.create"}';

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $request = $this->createRequest('POST', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('Webhook', 'create', $params, $data, $request);
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

        $this->auth('test-key', null, null, 'ApiKey');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $data = '{"event":"Account.create", "url": "https://test"}';

        $params = [];

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $request = $this->createRequest('POST', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('Webhook', 'create', $params, $data, $request);
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

        $this->auth('test-key', null, null, 'ApiKey');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $data = '{"event":"Account.create", "url": "https://test"}';

        $params = [];

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $request = $this->createRequest('POST', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('Webhook', 'create', $params, $data, $request);
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
                    'Account' => ['create'=> true, 'read' => 'own'],
                ],
            ]
        );

        $this->auth('test-key', null, null, 'ApiKey');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $data = '{"event":"Account.create", "url": "https://test"}';

        $params = [];

        $request = $this->createRequest('POST', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('Webhook', 'create', $params, $data, $request);

        $this->assertTrue(!empty($result));
    }
}
