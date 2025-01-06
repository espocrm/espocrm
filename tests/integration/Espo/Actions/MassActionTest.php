<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Actions;

use Espo\Core\MassAction\Api\PostProcess;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;

use Espo\Core\MassAction\ServiceParams;
use Espo\Core\MassAction\Service;
use Espo\Core\MassAction\Params as MassActionParams;
use Espo\Core\MassAction\Jobs\Process as JobProcess;
use Espo\Core\Job\Job\Data as JobData;

use Espo\Core\Select\SearchParams;
use Espo\Core\InjectableFactory;

use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Application;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\ORM\EntityManager;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Tools\MassUpdate\Data as MassUpdateData;
use Espo\Tools\MassUpdate\Processor;

class MassActionTest extends \tests\integration\Core\BaseTestCase
{
    /** @var Application */
    private $app;
    /** @var EntityManager */
    private $entityManager;

    private ?PostProcess $action = null;

    private function init(): void
    {
        $this->app = $this->createApplication();

        $this->entityManager = $this->app
            ->getContainer()
            ->get('entityManager');

        $this->action = $this->getInjectableFactory()->create(PostProcess::class);
    }

    private function getAdminUser(): User
    {
        $repository = $this->getContainer()
            ->getByClass(EntityManager::class)
            ->getRDBRepositoryByClass(User::class);

        $user = $repository
            ->where(['type' => User::TYPE_ADMIN])
            ->findOne();

        if (!$user) {
            $user = $repository->getNew();
            $user->set('userName', 'test-admin');
            $user->set('type', User::TYPE_ADMIN);

            $repository->save($user);
        }

        return $user;
    }

    public function testUpdate1(): void
    {
        $adminUser = $this->getAdminUser();

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
                'assignedUserId' => $adminUser->getId(),
            ],
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $this->action->process($request);

        $accountReloaded = $this->entityManager->getEntityById('Account', $account->getId());

        $this->assertEquals($adminUser->getId(), $accountReloaded->get('assignedUserId'));
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

        $response = $this->createMock(ResponseWrapper::class);

        $this->expectException(Forbidden::class);

        $this->action->process($request);
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

        // Mass-update for User entity type is allowed only for admins.
        $this->expectException(Error::class);

        $process->run(JobData::create()->withTargetId($massActionId));

        /*$em->refreshEntity($user);
        $em->refreshEntity($user1);

        $this->assertEquals('Tester', $user->get('title'));
        $this->assertEquals(null, $user1->get('title'));*/
    }

    public function testMassUpdateLinkAccess(): void
    {
        $user = $this->createUser('tester', [
            'massUpdatePermission' => 'yes',
            'data' => [
                'Contact' => [
                    'create' => 'no',
                    'read' => 'own',
                    'edit' => 'no',
                    'delete' => 'no'
                ],
                'Opportunity' => [
                    'create' => 'yes',
                    'read' => 'team',
                    'edit' => 'own',
                    'delete' => 'own'
                ],
            ],
        ]);

        $this->auth('tester');
        $this->setApplication($this->createApplication());

        $contact1 = $this->getEntityManager()
            ->createEntity(Contact::ENTITY_TYPE, [
                'lastName' => 'Contact 1',
                'assignedUserId' => $user->getId(),
            ]);

        $contact2 = $this->getEntityManager()
            ->createEntity(Contact::ENTITY_TYPE, [
                'lastName' => 'Contact 2',
            ]);

        $opp1 = $this->getEntityManager()
            ->createEntity(Opportunity::ENTITY_TYPE, [
                'name' => 'Opp 1',
                'assignedUserId' => $user->getId(),
            ]);

        $opp2 = $this->getEntityManager()
            ->createEntity(Opportunity::ENTITY_TYPE, [
                'name' => 'Opp 2',
                'assignedUserId' => $user->getId(),
            ]);

        $processor = $this->getInjectableFactory()->create(Processor::class);

        $params = MassActionParams::createWithIds(Opportunity::ENTITY_TYPE, [$opp1->getId(), $opp2->getId()]);

        $data = MassUpdateData::create()
            ->with('contactsIds', [$contact1->getId()]);
        $result = $processor->process($params, $data);
        $this->assertEquals(2, $result->getCount());

        $data = MassUpdateData::create()
            ->with('contactsIds', [$contact2->getId()]);
        $result = $processor->process($params, $data);
        $this->assertEquals(0, $result->getCount());
    }
}
