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

use Espo\Core\Action\Actions\Merge\Merger;
use Espo\Core\Action\Api\PostProcess;
use Espo\Core\Action\Params;
use Espo\Core\Application;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\PhoneNumber;
use Espo\Core\ORM\EntityManager;

use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use tests\integration\Core\BaseTestCase;

class ActionTest extends BaseTestCase
{
    /** @var Application */
    private $app;

    /** @var EntityManager */
    private $entityManager;


    /** @var Merger */
    private $merger;

    private ?PostProcess $action = null;

    private function init(): void
    {
        $this->app = $this->createApplication();

        $this->entityManager = $this->app
            ->getContainer()
            ->get('entityManager');

        $this->action = $this->getInjectableFactory()->create(PostProcess::class);

        $this->merger = $this->app
            ->getContainer()
            ->get('injectableFactory')
            ->create(Merger::class);
    }

    public function testActionNotAllowed(): void
    {
        $this->init();

        $data = [
            'entityType' => 'Role',
            'action' => 'convertCurrency',
            'id' => 'arbitrary-id',
            'data' => (object) [],
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $this->expectException(Forbidden::class);

        $this->action->process($request);
    }

    public function testActionMerge1(): void
    {
        $this->createUser('tester', [
            'assignmentPermission' => 'all',
            'data' => [
                'Contact' => [
                    'create' => 'all',
                    'read' => 'all',
                    'edit' => 'all',
                    'delete' => 'all',
                ],
            ],
        ]);

        $this->auth('tester');

        $this->init();

        $team1 = $this->entityManager->createEntity('Team', []);
        $team2 = $this->entityManager->createEntity('Team', []);

        $account1 = $this->entityManager->createEntity('Account', []);
        $account2 = $this->entityManager->createEntity('Account', []);

        /* @var $contact1 Contact */
        $contact1 = $this->entityManager->getNewEntity('Contact');

        $emailAddressGroup1 = $contact1->getEmailAddressGroup()
            ->withAdded(EmailAddress::create('c1a@test.com'))
            ->withAdded(EmailAddress::create('c1b@test.com')->invalid());

        $contact1->setEmailAddressGroup($emailAddressGroup1);

        $phoneNumberGroup1 = $contact1->getPhoneNumberGroup()
            ->withAdded(PhoneNumber::create('+1a'))
            ->withAdded(PhoneNumber::create('+1b')->invalid());

        $contact1->setPhoneNumberGroup($phoneNumberGroup1);

        $contact1->set('accountId', $account1->getId());

        $contact1->set('teamsIds', [$team1->getId()]);

        /* @var $contact2 Contact */
        $contact2 =  $this->entityManager->getNewEntity('Contact');

        $emailAddressGroup2 = $contact1->getEmailAddressGroup()
            ->withAdded(EmailAddress::create('c2a@test.com'))
            ->withAdded(EmailAddress::create('c2b@test.com')->optedOut());

        $contact2->setEmailAddressGroup($emailAddressGroup2);

        $phoneNumberGroup2 = $contact2->getPhoneNumberGroup()
            ->withAdded(PhoneNumber::create('+2a'))
            ->withAdded(PhoneNumber::create('+2b')->optedOut());

        $contact2->setPhoneNumberGroup($phoneNumberGroup2);

        $contact2->set('accountId', $account2->getId());

        $contact2->set('teamsIds', [$team2->getId()]);

        $this->entityManager->saveEntity($contact1);
        $this->entityManager->saveEntity($contact2);

        $this->entityManager->createEntity('Note', [
            'type' => 'Post',
            'parentType' => 'Contact',
            'parentId' => $contact1->getId(),
        ]);

        $note2 = $this->entityManager->createEntity('Note', [
            'type' => 'Post',
            'parentType' => 'Contact',
            'parentId' => $contact2->getId(),
        ]);

        $opportunity1 = $this->entityManager->createEntity('Opportunity', []);
        $opportunity2 = $this->entityManager->createEntity('Opportunity', []);

        $this->entityManager
            ->getRDBRepository('Contact')
            ->getRelation($contact1, 'opportunities')
            ->relate($opportunity1);

        $this->entityManager
            ->getRDBRepository('Contact')
            ->getRelation($contact2, 'opportunities')
            ->relate($opportunity2);

        $data = [
            'entityType' => 'Contact',
            'action' => 'merge',
            'id' => $contact1->getId(),
            'data' => (object) [
                'attributes' => (object) [
                    'description' => 'merged',
                ],
                'sourceIdList' => [$contact2->getId()],
            ],
        ];

        $request = $this->createRequest(
            'POST',
            [],
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $this->action->process($request);

        /* @var $contact1Reloaded Contact */
        $contact1Reloaded = $this->entityManager->getEntityById('Contact', $contact1->getId());
        $contact2Reloaded = $this->entityManager->getEntityById('Contact', $contact2->getId());

        $this->assertEquals('merged', $contact1Reloaded->get('description'));

        $this->assertNull($contact2Reloaded);

        $emailAddressGroup = $contact1Reloaded->getEmailAddressGroup();
        $phoneNumberGroup = $contact1Reloaded->getPhoneNumberGroup();

        $this->assertEquals(4, $emailAddressGroup->getCount());
        $this->assertEquals(4, $phoneNumberGroup->getCount());

        $this->assertEquals(
            'c1a@test.com',
            $emailAddressGroup->getPrimary()->getAddress()
        );

        $this->assertEquals(
            '+1a',
            $phoneNumberGroup->getPrimary()->getNumber()
        );

        $this->assertTrue(
            $emailAddressGroup->getByAddress('c2b@test.com')->isOptedOut()
        );

        $this->assertTrue(
            $emailAddressGroup->getByAddress('c1b@test.com')->isInvalid()
        );

        $this->assertTrue(
            $phoneNumberGroup->getByNumber('+2b')->isOptedOut()
        );

        $this->assertTrue(
            $phoneNumberGroup->getByNumber('+1b')->isInvalid()
        );

        $this->assertEquals(
            $contact1->getId(),
            $this->entityManager
                ->getEntityById('Note', $note2->getId())
                ->get('parentId')
        );

        $this->assertEquals(
            2,
            $this->entityManager
                ->getRDBRepository('Contact')
                ->getRelation($contact1Reloaded, 'opportunities')
                ->count()
        );

        $this->assertEquals(
            2,
            $this->entityManager
                ->getRDBRepository('Contact')
                ->getRelation($contact1Reloaded, 'teams')
                ->count()
        );

        $this->assertEquals(
            2,
            count($contact1Reloaded->getLinkMultipleIdList('accounts'))
        );
    }

    public function testMergeRelationshipColumns(): void
    {
        $em = $this->getEntityManager();

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $accountSource = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $contact = $em->getRDBRepositoryByClass(Contact::class)->getNew();

        $em->saveEntity($account);
        $em->saveEntity($accountSource);
        $em->saveEntity($contact);

        $em->getRelation($accountSource, 'contacts')
            ->relate($contact, [
                'role' => 'Tester',
                'isInactive' => true,
            ]);

        $merger = $this->getInjectableFactory()->create(Merger::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $merger->process(
            new Params(Account::ENTITY_TYPE, $account->getId()),
            [$accountSource->getId()],
            (object) []
        );

        $em->refreshEntity($account);

        $relation = $em->getRelation($account, 'contacts');

        $this->assertEquals(
            'Tester',
            $relation->getColumn($contact, 'role')
        );

        $this->assertTrue(
            $relation->getColumn($contact, 'isInactive')
        );
    }

    public function testMergeNoEditAccess(): void
    {
        $this->createUser('tester', [
            'assignmentPermission' => 'all',
            'data' => [
                'Contact' => [
                    'create' => 'own',
                    'read' => 'all',
                    'edit' => 'own',
                    'delete' => 'no',
                ],
            ],
        ]);

        $this->auth('tester');

        $this->init();

        $contact1 = $this->entityManager->createEntity('Contact', []);
        $contact2 = $this->entityManager->createEntity('Contact', []);

        $params = new Params('Contact', $contact1->getId());

        $this->expectException(Forbidden::class);

        $this->merger->process($params, [$contact2->getId()], (object) []);
    }

    public function testMergeNoDeleteAccess(): void
    {
        $this->createUser('tester', [
            'assignmentPermission' => 'all',
            'data' => [
                'Contact' => [
                    'create' => 'own',
                    'read' => 'all',
                    'edit' => 'all',
                    'delete' => 'no',
                ],
            ],
        ]);

        $this->auth('tester');

        $this->init();

        $contact1 = $this->entityManager->createEntity('Contact', []);
        $contact2 = $this->entityManager->createEntity('Contact', []);

        $params = new Params('Contact', $contact1->getId());

        $this->expectException(Forbidden::class);

        $this->merger->process($params, [$contact2->getId()], (object) []);
    }
}
