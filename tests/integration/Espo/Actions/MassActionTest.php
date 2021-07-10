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

namespace tests\integration\Espo\Actions;

use Espo\Core\{
    Api\ActionProcessor,
    Api\Response,
    ORM\EntityManager,
    Application,
    Exceptions\Forbidden,
};

class MassActionTest extends \tests\integration\Core\BaseTestCase
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ActionProcessor
     */
    private $actionProcessor;

    private function init(): void
    {
        $this->app = $this->createApplication();

        $this->entityManager = $this->app
            ->getContainer()
            ->get('entityManager');

        $this->actionProcessor = $this->app
            ->getContainer()
            ->get('injectableFactory')
            ->create(ActionProcessor::class);
    }

    public function testUpdate1(): void
    {
        $this->createUser([
            'userName' => 'admin-test',
            'type' => 'admin',
        ]);

        $this->auth('admin-test');

        $this->init();

        $account = $this->entityManager->createEntity('Account', [
            'name' => 'test',
        ]);

        $data = [
            'entityType' => 'Account',
            'action' => 'update',
            'params' => [
                'ids' => [$account->getId()],
            ],
            'data' => [
                'assignedUserId' => '1'
            ],
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

        $this->actionProcessor->process('MassAction', 'process', $request, $response);

        $accountReloaded = $this->entityManager->getEntity('Account', $account->getId());

        $this->assertEquals('1', $accountReloaded->get('assignedUserId'));
    }

    public function testUpdateNotAllowed(): void
    {
        $this->init();

        $data = [
            'entityType' => 'Role',
            'action' => 'update',
            'params' => [
                'ids' => ['arbitrary-id'],
            ],
            'data' => [
                'name' => '1'
            ],
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $response = $this->createMock(Response::class);

        $this->expectException(Forbidden::class);

        $this->actionProcessor->process('MassAction', 'process', $request, $response);
    }
}
