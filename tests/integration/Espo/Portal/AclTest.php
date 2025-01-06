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

namespace tests\integration\Espo\Portal;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\Service;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SearchParams;
use Espo\Modules\Crm\Entities\CaseObj;
use tests\integration\Core\BaseTestCase;

class AclTest extends BaseTestCase
{
    public function testAccessContact(): void
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $contact = $em->createEntity('Contact', []);
        $portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser([
            'userName' => 'tester',
            'portalsIds' => [$portal->getId()],
            'contactId' => $contact->getId(),
        ], [
            'data' => [
                'Case' => [
                    'create' => 'no',
                    'read' => 'contact',
                    'edit' => 'no',
                    'delete' => 'no',
                    'stream' => 'contact',
                ]
            ],
        ], true);

        $this->auth('tester', null, $portal->getId());

        $app = $this->createApplication(true, $portal->getId());

        $em = $app->getContainer()->get('entityManager');

        $acl = $app->getContainer()->get('acl');

        $case1 = $em->createEntity('Case', [
            'contactId' => $contact->getId(),
        ], ['createdById' => '1']);
        $case2 = $em->createEntity('Case', [
            'contactsIds' => [$contact->getId()],
        ], ['createdById' => '1']);
        $case3 = $em->createEntity('Case', [
        ], ['createdById' => '1']);
        $case4 = $em->createEntity('Case', [
            'contactsIds' => [$contact->getId()],
            'isInternal' => true,
        ], ['createdById' => '1']);

        $this->assertFalse($acl->check('Case', 'create'));
        $this->assertFalse($acl->check('Case', 'edit'));
        $this->assertFalse($acl->check('Case', 'delete'));

        $this->assertTrue($acl->check($case1, 'read'));
        $this->assertTrue($acl->check($case2, 'read'));
        $this->assertFalse($acl->check($case3, 'read'));
        $this->assertFalse($acl->check($case4, 'read'));

        $service = $app->getContainer()->get('recordServiceContainer')->get('Case');

        $result = $service->find(SearchParams::create());

        $idList = [];
        foreach ($result->getCollection() as $e) {
            $idList[] = $e->getId();
        }

        $this->assertTrue(in_array($case1->getId(), $idList));
        $this->assertTrue(in_array($case2->getId(), $idList));
        $this->assertFalse(in_array($case3->getId(), $idList));
        $this->assertFalse(in_array($case4->getId(), $idList));
    }

    public function testAccessAccount(): void
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $contact = $em->createEntity('Contact', []);
        $account = $em->createEntity('Account', []);
        $portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser([
            'userName' => 'tester',
            'portalsIds' => [$portal->getId()],
            'contactId' => $contact->getId(),
            'accountsIds' => [$account->getId()],
        ], [
            'data' => [
                'Case' => [
                    'create' => 'no',
                    'read' => 'account',
                    'edit' => 'no',
                    'delete' => 'no',
                    'stream' => 'account',
                ]
            ],
        ], true);

        $this->auth('tester', null, $portal->getId());

        $app = $this->createApplication(true, $portal->getId());

        $em = $app->getContainer()->get('entityManager');

        $acl = $app->getContainer()->get('acl');

        $case1 = $em->createEntity('Case', [
            'contactId' => $contact->getId(),
        ], ['createdById' => '1']);
        $case2 = $em->createEntity('Case', [
            'contactsIds' => [$contact->getId()],
        ], ['createdById' => '1']);
        $case3 = $em->createEntity('Case', [
        ], ['createdById' => '1']);
        $case4 = $em->createEntity('Case', [
            'accountId' => $account->getId(),
        ], ['createdById' => '1']);
        $case5 = $em->createEntity('Case', [
            'accountId' => $account->getId(),
            'isInternal' => true,
        ], ['createdById' => '1']);

        $this->assertFalse($acl->check('Case', 'create'));
        $this->assertFalse($acl->check('Case', 'edit'));
        $this->assertFalse($acl->check('Case', 'delete'));

        $this->assertTrue($acl->check($case1, 'read'));
        $this->assertTrue($acl->check($case2, 'read'));
        $this->assertFalse($acl->check($case3, 'read'));
        $this->assertTrue($acl->check($case4, 'read'));
        $this->assertFalse($acl->check($case5, 'read'));

        $service = $app->getContainer()->get('recordServiceContainer')->get('Case');
        $result = $service->find(SearchParams::create());

        $idList = [];
        foreach ($result->getCollection() as $e) {
            $idList[] = $e->getId();
        }

        $this->assertTrue(in_array($case1->getId(), $idList));
        $this->assertTrue(in_array($case2->getId(), $idList));
        $this->assertFalse(in_array($case3->getId(), $idList));
        $this->assertTrue(in_array($case4->getId(), $idList));
        $this->assertFalse(in_array($case5->getId(), $idList));
    }

    public function testAccessOwn(): void
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $contact = $em->createEntity('Contact', []);
        $account = $em->createEntity('Account', []);
        $portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser([
            'userName' => 'tester',
            'portalsIds' => [$portal->getId()],
            'contactId' => $contact->getId(),
            'accountsIds' => [$account->getId()],
        ], [
            'data' => [
                'Case' => [
                    'create' => 'no',
                    'read' => 'own',
                    'edit' => 'no',
                    'delete' => 'no',
                    'stream' => 'own',
                ]
            ],
        ], true);

        $this->auth('tester', null, $portal->getId());

        $app = $this->createApplication(true, $portal->getId());

        $em = $app->getContainer()->get('entityManager');

        $acl = $app->getContainer()->get('acl');
        $user = $app->getContainer()->get('user');

        $case1 = $em->createEntity('Case', [
            'contactId' => $contact->getId(),
        ], ['createdById' => '1']);
        $case2 = $em->createEntity('Case', [
            'contactsIds' => [$contact->getId()],
        ], ['createdById' => '1']);
        $case3 = $em->createEntity('Case', [
        ], ['createdById' => '1']);
        $case4 = $em->createEntity('Case', [
            'accountId' => $account->getId(),
        ], ['createdById' => '1']);
        $case5 = $em->createEntity('Case', [
            'accountId' => $account->getId(),
        ], ['createdById' => $user->getId()]);
        $case6 = $em->createEntity('Case', [
            'accountId' => $account->getId(),
            'isInternal' => true,
        ], ['createdById' => '1']);

        $this->assertFalse($acl->check('Case', 'create'));
        $this->assertFalse($acl->check('Case', 'edit'));
        $this->assertFalse($acl->check('Case', 'delete'));

        $this->assertFalse($acl->check($case1, 'read'));
        $this->assertFalse($acl->check($case2, 'read'));
        $this->assertFalse($acl->check($case3, 'read'));
        $this->assertFalse($acl->check($case4, 'read'));
        $this->assertTrue($acl->check($case5, 'read'));
        $this->assertFalse($acl->check($case6, 'read'));

        $service = $app->getContainer()->get('recordServiceContainer')->get('Case');

        $result = $service->find(SearchParams::create());

        $idList = [];

        foreach ($result->getCollection() as $e) {
            $idList[] = $e->getId();
        }

        $this->assertFalse(in_array($case1->getId(), $idList));
        $this->assertFalse(in_array($case2->getId(), $idList));
        $this->assertFalse(in_array($case3->getId(), $idList));
        $this->assertFalse(in_array($case4->getId(), $idList));
        $this->assertTrue(in_array($case5->getId(), $idList));
        $this->assertFalse(in_array($case6->getId(), $idList));
    }

    public function testCreateCase(): void
    {
        $em = $this->getEntityManager();

        $contact = $em->createEntity('Contact');
        $account = $em->createEntity('Account');

        $contactNotOwn = $em->createEntity('Contact');
        $accountNotOwn = $em->createEntity('Account');

        $portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser([
            'userName' => 'tester',
            'portalsIds' => [$portal->getId()],
            'contactId' => $contact->getId(),
            'accountsIds' => [$account->getId()],
        ], [
            'data' => [
                'Case' => [
                    'create' => 'yes',
                    'read' => 'own',
                    'edit' => 'no',
                    'delete' => 'no',
                    'stream' => 'own',
                ]
            ],
        ], true);

        $this->auth('tester', null, $portal->getId());

        $app = $this->createApplication(true, $portal->getId());

        /** @var Service<CaseObj> $caseService */
        $caseService = $app->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(CaseObj::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $caseService->create((object) [
            'name' => 'Test 1',
            'accountId' => $account->getId(),
            'contactId' => $contact->getId(),
            'contactsIds' => [$contact->getId()],
        ], CreateParams::create());

        $isThrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $caseService->create((object)[
                'name' => 'Test 1',
                'accountId' => $accountNotOwn->getId(),
                'contactId' => $contactNotOwn->getId(),
                'contactsIds' => [$contactNotOwn->getId()],
            ], CreateParams::create());
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);
    }
}
