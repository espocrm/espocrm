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

namespace tests\integration\Espo\User;

use Espo\Core\{
    Api\ActionProcessor,
    Api\Response,
};

class AclAdminTest extends \tests\integration\Core\BaseTestCase
{
    public function testCreateUser()
    {
        $this->createUser([
            'userName' => 'admin-test',
            'type' => 'admin',
        ]);

        $this->auth('admin-test');

        $app = $this->createApplication();

        $processor = $app->getContainer()
            ->get('injectableFactory')
            ->create(ActionProcessor::class);

        $data = [
            'userName' => 'test',
            'lastName' => 'Test',
            'password' => '1',
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $response = $this->createMock(Response::class);

        $response
            ->expects($this->once())
            ->method('writeBody');

        $processor->process('User', 'create', $request, $response);
    }

    public function testCreateTeam()
    {
        $this->createUser([
            'userName' => 'admin-test',
            'type' => 'admin',
        ]);

        $this->auth('admin-test');

        $app = $this->createApplication();

        $processor = $app->getContainer()
            ->get('injectableFactory')
            ->create(ActionProcessor::class);

        $data = [
            'name' => 'test',
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $response = $this->createMock(Response::class);

        $response
            ->expects($this->once())
            ->method('writeBody');

        $processor->process('Team', 'create', $request, $response);
    }

    public function testCreateRole()
    {
        $this->createUser([
            'userName' => 'admin-test',
            'type' => 'admin',
        ]);

        $this->auth('admin-test');

        $app = $this->createApplication();

        $processor = $app->getContainer()
            ->get('injectableFactory')
            ->create(ActionProcessor::class);

        $data = [
            'name' => 'test',
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $response = $this->createMock(Response::class);

        $response
            ->expects($this->once())
            ->method('writeBody');

        $processor->process('Role', 'create', $request, $response);
    }

    public function testCreatePortal()
    {
        $this->createUser([
            'userName' => 'admin-test',
            'type' => 'admin',
        ]);

        $this->auth('admin-test');

        $app = $this->createApplication();

        $processor = $app->getContainer()
            ->get('injectableFactory')
            ->create(ActionProcessor::class);

        $data = [
            'name' => 'test',
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $response = $this->createMock(Response::class);

        $response
            ->expects($this->once())
            ->method('writeBody');

        $processor->process('Portal', 'create', $request, $response);
    }
}
