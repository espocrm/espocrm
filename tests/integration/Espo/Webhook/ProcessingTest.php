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

use Espo\Core\Webhook\Queue;
use Espo\Core\Webhook\Sender;

use Espo\ORM\EntityManager;

use Espo\Entities\Webhook;

use Espo\Modules\Crm\Entities\Account;

class ProcessingTest extends \tests\integration\Core\BaseTestCase
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

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('entityManager');

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.create',
            'userId' => $user->getId(),
            'url' => 'https://test',
        ]);

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.update',
            'userId' => $user->getId(),
            'url' => 'https://test',
        ]);

        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

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

        $dataList1 = [
            $account1->getValueMap(),
            $account2->getValueMap(),
        ];

        $account1->set('name', 'test-1-changed');

        $em->saveEntity($account1);

        $dataList2 = [
            (object) [
                'name' => $account1->get('name'),
                'modifiedById' => 'system',
                'modifiedByName' => 'System',
                'id' => $account1->getId(),
            ],
        ];

        $sender = $this->createMock(Sender::class);

        /* @var $queue Queue */
        $queue = $app->getContainer()
            ->get('injectableFactory')
            ->createWith(Queue::class, [
                'sender' => $sender,
            ]);

        $sender
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    $this->callback(
                        function (Webhook $webhook){
                            return $webhook->get('event') === 'Account.create';
                        }
                    ),
                    $dataList1,
                ],
                [
                    $this->callback(
                        function (Webhook $webhook){
                            return $webhook->get('event') === 'Account.update';
                        }
                    ),
                    $dataList2,
                ]
            )
            ->willReturn(
                200,
                200
            );

        $queue->process();

        $queue->process();
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

        $em->createEntity(Webhook::ENTITY_TYPE, [
            'event' => 'Account.delete',
            'userId' => $user->getId(),
            'url' => 'https://test',
        ]);

        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $em->removeEntity($account1);

        $dataList1 = [
            (object) [
                'id' => $account1->getId(),
            ]
        ];

        $sender = $this->createMock(Sender::class);

        /* @var $queue Queue */
        $queue = $app->getContainer()
            ->get('injectableFactory')
            ->createWith(Queue::class, [
                'sender' => $sender,
            ]);

        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->withConsecutive(
                [
                    $this->callback(
                        function (Webhook $webhook){
                            return $webhook->get('event') === 'Account.delete';
                        }
                    ),
                    $dataList1,
                ],
            )
            ->willReturn(
                200,
            );

        $queue->process();

        $queue->process();
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

        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->withConsecutive(
                [
                    $this->callback(
                        function (Webhook $webhook){
                            return $webhook->get('event') === 'Account.fieldUpdate.name';
                        }
                    ),
                    $dataList1,
                ],
            )
            ->willReturn(
                200,
            );

        $queue->process();

        $queue->process();
    }
}
