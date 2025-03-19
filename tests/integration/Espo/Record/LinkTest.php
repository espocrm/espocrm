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

namespace tests\integration\Espo\Record;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\Date;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\EntityManager;
use Espo\ORM\Type\RelationType;
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

    public function testLinkCheckEmailToCase(): void
    {
        $user = $this->createUser('test', [
            'data' => [
                'Account' => [
                    'create' => 'no',
                    'read' => 'own',
                    'edit' => 'no',
                    'delete' => 'no',
                ],
                'Case' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
                'Contact' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
                'Lead' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
                'Email' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
            ],
        ]);

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['assignedUserId' => $user->getId()]);
        $lead = $em->createEntity(Lead::ENTITY_TYPE);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'contact']);
        $email = $em->createEntity(Email::ENTITY_TYPE, [
            'assignedUserId' => $user->getId(),
            'parentId' => $lead->getId(),
            'parentType' => Lead::ENTITY_TYPE,
        ]);

        $this->auth('test');
        $this->reCreateApplication();

        $caseService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(CaseObj::class);

        // Should not create if no access to the Lead.

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $caseService->create((object) [
                'name' => '1',
                'assignedUserId' => $user->getId(),
                'leadId' => $lead->getId(),
                'accountId' => $account->getId(),
            ], CreateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        // Should create if an email with a Lead parent is passed.

        /** @noinspection PhpUnhandledExceptionInspection */
        $caseService->create((object) [
            'name' => '1',
            'assignedUserId' => $user->getId(),
            'leadId' => $lead->getId(),
            'accountId' => $account->getId(),
            'originalEmailId' => $email->getId(),
        ], CreateParams::create());

        //

        $account = $em->createEntity(Account::ENTITY_TYPE);
        $email = $em->createEntity(Email::ENTITY_TYPE, [
            'assignedUserId' => $user->getId(),
            'parentId' => $account->getId(),
            'parentType' => Account::ENTITY_TYPE,
        ]);

        // Should not create if no access to the Account.

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $caseService->create((object) [
                'name' => '1',
                'assignedUserId' => $user->getId(),
                'accountId' => $account->getId(),
            ], CreateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        // Should create if an email with an Account parent is passed.

        /** @noinspection PhpUnhandledExceptionInspection */
        $caseService->create((object) [
            'name' => '1',
            'assignedUserId' => $user->getId(),
            'accountId' => $account->getId(),
            'originalEmailId' => $email->getId(),
        ], CreateParams::create());

        //

        $account = $em->createEntity(Account::ENTITY_TYPE, ['assignedUserId' => $user->getId()]);

        $email = $em->createEntity(Email::ENTITY_TYPE, [
            'assignedUserId' => $user->getId(),
            'parentId' => $contact->getId(),
            'parentType' => Contact::ENTITY_TYPE,
        ]);

        // Should not create if no access to the Contact.

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $caseService->create((object) [
                'name' => '1',
                'assignedUserId' => $user->getId(),
                'accountId' => $account->getId(),
                'contactId' => $contact->getId(),
                'contactsIds' => [$contact->getId()],
            ], CreateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        // Should create if an email with a Contact parent is passed.

        /** @noinspection PhpUnhandledExceptionInspection */
        $caseService->create((object) [
            'name' => '1',
            'assignedUserId' => $user->getId(),
            'accountId' => $account->getId(),
            'contactId' => $contact->getId(),
            'contactsIds' => [$contact->getId()],
            'originalEmailId' => $email->getId(),
        ], CreateParams::create());

        // Should not allow more than 1 ID.

        $contactAnother = $this->getEntityManager()->createEntity(Contact::ENTITY_TYPE);

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $caseService->create((object) [
                'name' => '1',
                'assignedUserId' => $user->getId(),
                'contactId' => $contact->getId(),
                'contactsIds' => [$contact->getId(), $contactAnother->getId()],
                'originalEmailId' => $email->getId(),
            ], CreateParams::create());
        }
        catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);
    }

    public function testLoadNames(): void
    {
        $caseService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(CaseObj::class);

        $em = $this->getEntityManager();

        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'contact']);
        $contact1 = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'contact1']);

        /** @noinspection PhpUnhandledExceptionInspection */
        $case = $caseService->create((object) [
            'name' => '1',
            'contactId' => $contact->getId(),
            'contactsIds' => [$contact->getId()],
        ], CreateParams::create());

        $this->assertEquals(
            (object) [
                $contact->getId() => $contact->get('lastName'),
            ],
            $case->get('contactsNames')
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $case = $caseService->update($case->getId(), (object) [
            'contactsIds' => [$contact->getId(), $contact1->getId()],
        ], UpdateParams::create());

        $this->assertEquals(
            (object) [
                $contact->getId() => $contact->get('lastName'),
                $contact1->getId() => $contact1->get('lastName'),
            ],
            $case->get('contactsNames')
        );
    }

    public function testOneToOne(): void
    {
        $metadata = $this->getContainer()->getByClass(Metadata::class);

        $metadata->set('entityDefs', CaseObj::ENTITY_TYPE, [
            'links' => [
                'task' => [
                    'foreign' => 'case',
                    'type' => RelationType::BELONGS_TO,
                    'entity' => Task::ENTITY_TYPE,
                ],
            ]
        ]);

        $metadata->set('entityDefs', Task::ENTITY_TYPE, [
            'fields' => [
                'case' => [
                    'type' => FieldType::LINK_ONE,
                ],
            ],
            'links' => [
                'case' => [
                    'foreign' => 'task',
                    'type' => RelationType::HAS_ONE,
                    'entity' => CaseObj::ENTITY_TYPE,
                ],
            ]
        ]);

        $metadata->save();

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getDataManager()->rebuild();
        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $task = $em->getRDBRepositoryByClass(Task::class)->getNew();
        $task->setMultiple(['name' => 'Task']);
        $em->saveEntity($task);

        $case = $em->getRDBRepositoryByClass(CaseObj::class)->getNew();
        $case->setMultiple(['name' => 'Case']);
        $em->saveEntity($case);

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(Task::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->update($task->getId(), (object) [
            'caseId' => $case->getId(),
        ], UpdateParams::create());

        $this->assertTrue(
            $em->getRDBRepositoryByClass(Task::class)
                ->getRelation($task, 'case')
                ->isRelatedById($case->getId())
        );
    }
}
