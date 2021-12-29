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

use Espo\Modules\Crm\Entities\Account;

use Espo\Core\MassAction\ServiceParams;
use Espo\Core\MassAction\Service;
use Espo\Core\MassAction\Params as MassActionParams;
use Espo\Core\MassAction\Jobs\Process as JobProcess;
use Espo\Core\Job\Job\Data as JobData;

use Espo\Core\Select\SearchParams;

use Espo\Core\InjectableFactory;

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

    public function testAcl1()
    {
        /** @var EntityManager $em */
        $em = $this->getApplication()->getContainer()->get('entityManager');

        $user = $this->createUser('tester', [
            'massUpdatePermission' => 'yes',
            'data' => [
                'Account' => [
                    'create' => 'no',
                    'read' => 'all',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
            ],
        ]);

        $account1 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => '1',
            'assignedUserId' => $user->getId(),
        ]);

        $account2 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => '2',
        ]);

        $this->auth('tester');

        $app = $this->createApplication();

        /** @var InjectableFactory $injectableFactory */
        $injectableFactory = $app->getContainer()->get('injectableFactory');

        $this->assertEquals($user->getId(), $app->getContainer()->get('user')->getId());

        /** @var Service $service */
        $service = $injectableFactory->create(Service::class);

        $serviceParams = ServiceParams
            ::create(
                MassActionParams::createWithSearchParams('Account', SearchParams::create())
            )
            ->withIsIdle();

        $serviceResult = $service->process(
            'Account',
            'update',
            $serviceParams,
            (object) [
                'type' => 'Customer',
            ]
        );

        $this->assertFalse($serviceResult->hasResult());

        $massActionId = $serviceResult->getId();

        $this->auth(null);

        $app1 = $this->getApplication();

        $this->assertEquals('system', $app1->getContainer()->get('user')->getId());

        /** @var InjectableFactory $injectableFactory1 */
        $injectableFactory1 = $app1->getContainer()->get('injectableFactory');

        $process = $injectableFactory1->create(JobProcess::class);

        $process->run(JobData::create()->withTargetId($massActionId));

        $em->refreshEntity($account1);
        $em->refreshEntity($account2);

        $this->assertEquals('Customer', $account1->get('type'));
        $this->assertEquals(null, $account2->get('type'));
    }

    public function testAcl2()
    {
        /** @var EntityManager $em */
        $em = $this->getApplication()->getContainer()->get('entityManager');

        $user = $this->createUser('tester', [
            'massUpdatePermission' => 'yes',
            'data' => [
                'User' => [
                    'create' => 'no',
                    'read' => 'all',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
            ],
        ]);

        $user1 = $this->createUser('tester-1', []);

        $this->auth('tester');

        $app = $this->createApplication();

        /** @var InjectableFactory $injectableFactory */
        $injectableFactory = $app->getContainer()->get('injectableFactory');

        $this->assertEquals($user->getId(), $app->getContainer()->get('user')->getId());

        /** @var Service $service */
        $service = $injectableFactory->create(Service::class);

        $serviceParams = ServiceParams
            ::create(
                MassActionParams::createWithSearchParams('User', SearchParams::create())
            )
            ->withIsIdle();

        $serviceResult = $service->process(
            'User',
            'update',
            $serviceParams,
            (object) [
                'title' => 'Tester',
            ]
        );

        $this->assertFalse($serviceResult->hasResult());

        $massActionId = $serviceResult->getId();

        $this->auth(null);

        $app1 = $this->getApplication();

        $this->assertEquals('system', $app1->getContainer()->get('user')->getId());

        /** @var InjectableFactory $injectableFactory1 */
        $injectableFactory1 = $app1->getContainer()->get('injectableFactory');

        $process = $injectableFactory1->create(JobProcess::class);

        $process->run(JobData::create()->withTargetId($massActionId));

        $em->refreshEntity($user);
        $em->refreshEntity($user1);

        $this->assertEquals('Tester', $user->get('title'));
        $this->assertEquals(null, $user1->get('title'));
    }
}
