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

namespace tests\integration\Espo\Webhook;

use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Webhook\Queue;
use Espo\Core\Webhook\Sender;
use Espo\ORM\EntityManager;
use Espo\Entities\Webhook;
use Espo\Modules\Crm\Entities\Account;
use tests\integration\Core\BaseTestCase;

class ProcessingTest extends BaseTestCase
{
    public function testProcessing1(): void
    {
        $user = $this->createUser(
            [
                'userName' => 'test',
                'password' => '1',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => [
                        'create' => 'yes',
                        'read' => 'own',
                    ],
                ],
            ]
        );

        $em = $this->getContainer()->getByClass(EntityManager::class);

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.create',
            'userId' => $user->getId(),
            'url' => 'https://test',
            'skipOwn' => true,
        ]);

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.update',
            'userId' => $user->getId(),
            'url' => 'https://test',
            'skipOwn' => true,
        ]);

        $app = $this->createApplication();

        $em = $app->getContainer()->getByClass(EntityManager::class);

        $account1 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test1',
            'assignedUserId' => $user->getId(),
        ]);

        $account2 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test2',
            'assignedUserId' => $user->getId(),
        ]);

        $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test3',
        ]);

        $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test_skip',
            'assignedUserId' => $user->getId(),
        ], [SaveOption::CREATED_BY_ID => $user->getId()]);

        $dataList1 = [
            $account1->getValueMap(),
            $account2->getValueMap(),
        ];

        $account1->set('name', 'test-1-changed');
        $em->saveEntity($account1);

        $account1->set('description', 'test-skip');
        $em->saveEntity($account1, [SaveOption::MODIFIED_BY_ID => $user->getId()]);

        $dataList2 = [
            (object) [
                'name' => $account1->get('name'),
                'modifiedById' => 'system',
                'modifiedByName' => 'System',
                'id' => $account1->getId(),
            ],
        ];

        $sender = $this->createMock(Sender::class);

        /** @var Queue $queue */
        $queue = $app->getContainer()
            ->getByClass(InjectableFactory::class)
            ->createWith(Queue::class, [
                'sender' => $sender,
            ]);

        $invokedCount = $this->exactly(2);

        $notSame = false;

        $sender
            ->expects($invokedCount)
            ->method('send')
            ->willReturnCallback(function (Webhook $webhook, $dataList) use ($invokedCount, $dataList1, $dataList2, &$notSame) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    if (count($dataList) !== count($dataList1)) {
                        $notSame = true;
                    }

                    if ('Account.create' !== $webhook->getEvent()) {
                        $notSame = true;
                    }

                    return 200;
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    if (count($dataList) !== count($dataList2)) {
                        $notSame = true;
                    }

                    if ('Account.update' !== $webhook->getEvent()) {
                        $notSame = true;
                    }

                    return 200;
                }

                return 0;
            });

        $queue->process();
        $queue->process();

        $this->assertFalse($notSame);
    }

    public function testProcessing2(): void
    {
        $user = $this->createUser(
            [
                'userName' => 'test',
                'password' => '1',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => [
                        'create' => 'yes',
                        'read' => 'own',
                    ],
                ],
            ]
        );

        $app = $this->createApplication();

        /* @var $em EntityManager */
        $em = $app->getContainer()->get('entityManager');

        $account1 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test1',
            'assignedUserId' => $user->getId(),
        ]);

        $account2 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test2',
            'assignedUserId' => $user->getId(),
        ]);

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.delete',
            'userId' => $user->getId(),
            'url' => 'https://test',
            'skipOwn' => true,
        ]);

        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $em->removeEntity($account1);
        $em->removeEntity($account2, [SaveOption::MODIFIED_BY_ID => $user->getId()]);

        $sender = $this->createMock(Sender::class);

        /* @var $queue Queue */
        $queue = $app->getContainer()
            ->get('injectableFactory')
            ->createWith(Queue::class, [
                'sender' => $sender,
            ]);

        $dataList1 = [
            (object) [
                'id' => $account1->getId(),
            ]
        ];

        $invokedCount = $this->exactly(1);

        $notSame = false;

        $sender
            ->expects($invokedCount)
            ->method('send')
            ->willReturnCallback(function (Webhook $webhook, $dataList) use ($invokedCount, $dataList1, &$notSame) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    if ('Account.delete' !== $webhook->getEvent()) {
                        $notSame = true;
                    }

                    if (json_encode($dataList) !== json_encode($dataList1)) {
                        $notSame = true;
                    }

                    return 200;
                }

                return 0;
            });

        $queue->process();
        $queue->process();

        $this->assertFalse($notSame);
    }

    public function testProcessing3(): void
    {
        $user = $this->createUser(
            [
                'userName' => 'test',
                'password' => '1',
            ],
            [
                'data' => [
                    'Webhook' => true,
                    'Account' => [
                        'create' => 'yes',
                        'read' => 'own',
                    ],
                ],
            ]
        );

        $app = $this->createApplication();

        /* @var $em EntityManager */
        $em = $app->getContainer()->get('entityManager');

        $account1 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'test1',
            'assignedUserId' => $user->getId(),
        ]);

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.fieldUpdate.name',
            'userId' => $user->getId(),
            'url' => 'https://test',
        ]);

        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $account1->set('name', 'test-1-changed');

        $em->saveEntity($account1);

        $dataList1 = [
            (object) [
                'id' => $account1->getId(),
                'name' => 'test-1-changed',
            ]
        ];

        $sender = $this->createMock(Sender::class);

        /* @var $queue Queue */
        $queue = $app->getContainer()
            ->get('injectableFactory')
            ->createWith(Queue::class, [
                'sender' => $sender,
            ]);

        $invokedCount = $this->exactly(1);

        $notSame = false;

        $sender
            ->expects($invokedCount)
            ->method('send')
            ->willReturnCallback(function (Webhook $webhook, $dataList) use ($invokedCount, $dataList1, &$notSame) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    if (json_encode($dataList) !== json_encode($dataList1)) {
                        $notSame = true;
                    }

                    if ('Account.fieldUpdate.name' !== $webhook->getEvent()) {
                        $notSame = true;
                    }

                    return 200;
                }

                return 0;
            });

        $queue->process();
        $queue->process();

        $this->assertFalse($notSame);
    }
}
