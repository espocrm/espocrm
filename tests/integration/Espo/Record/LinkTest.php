<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Record;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\Date;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\EntityManager;
use tests\integration\Core\BaseTestCase;

class LinkTest extends BaseTestCase
{
    public function testUnlinkRequired1(): void
    {
        $metadata = $this->getContainer()->getByClass(Metadata::class);

        $metadata->set('entityDefs', CaseObj::ENTITY_TYPE, [
            'fields' => [
                'account' => ['required' => true]
            ]
        ]);
        $metadata->save();

        $this->reCreateApplication();

        $em = $this->getContainer()->getByClass(EntityManager::class);

        $account = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Test',
        ]);

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [
            'name' => 'Test',
            'accountId' => $account->getId(),
        ]);

        $accountService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        $this->expectException(Forbidden::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $accountService->unlink($account->getId(), 'cases', $case->getId());
    }

    public function testLinkCheck1(): void
    {
        $user = $this->createUser('test', [
            'data' => [
                'Account' => [
                    'create' => 'no',
                    'read' => 'own',
                    'edit' => 'no',
                    'delete' => 'no',
                ],
                'Opportunity' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
                'Task' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
            ]
        ]);

        $em = $this->getContainer()->getByClass(EntityManager::class);

        $account1 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => '1',
            'assignedUserId' => $user->getId(),
        ]);

        $account2 = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => '2',
        ]);

        $this->auth('test');
        $this->reCreateApplication();

        $oppService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Opportunity::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $oppService->create((object) [
            'name' => '1',
            'accountId' => $account1->getId(),
            'assignedUserId' => $user->getId(),
            'amount' => 1.0,
            'amountCurrency' => 'USD',
            'probability' => 10,
            'closeDate' => Date::createToday()->toString(),
        ], CreateParams::create());

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $oppService->create((object) [
                'name' => '2',
                'accountId' => $account2->getId(),
                'assignedUserId' => $user->getId(),
                'amount' => 1.0,
                'amountCurrency' => 'USD',
                'probability' => 10,
                'closeDate' => Date::createToday()->toString(),
            ], CreateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        //

        $taskService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Task::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $task1 = $taskService->create((object) [
            'name' => '1',
            'accountId' => $account1->getId(),
            'parentType' => $account1->getEntityType(),
            'assignedUserId' => $user->getId(),
        ], CreateParams::create());

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $taskService->create((object) [
                'name' => '2',
                'parentId' => $account2->getId(),
                'parentType' => $account2->getEntityType(),
                'assignedUserId' => $user->getId(),
            ], CreateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        //

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $taskService->update($task1->getId(), (object) [
                'parentId' => $account2->getId(),
                'parentType' => $account2->getEntityType(),
            ], UpdateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        //

        $metadata = $this->getContainer()->getByClass(Metadata::class);

        $metadata->set('entityDefs', Opportunity::ENTITY_TYPE, [
            'fields' => [
                'account' => [
                    'defaultAttributes' => [
                        'accountId' => $account2->getId(),
                    ]
                ]
            ]
        ]);
        $metadata->save();

        $this->reCreateApplication();

        $oppService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Opportunity::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $oppService->create((object) [
            'name' => '2',
            'accountId' => $account2->getId(),
            'assignedUserId' => $user->getId(),
            'amount' => 1.0,
            'amountCurrency' => 'USD',
            'probability' => 10,
            'closeDate' => Date::createToday()->toString(),
        ], CreateParams::create());
    }
}
