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

namespace tests\integration\Espo\Portal;

use Espo\Core\{
    Select\SearchParams,
};

class AclTest extends \tests\integration\Core\BaseTestCase
{
    public function testAccessContact()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $contact = $em->createEntity('Contact', []);
        $portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser([
            'userName' => 'tester',
            'portalsIds' => [$portal->id],
            'contactId' => $contact->id,
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

        $this->auth('tester', null, $portal->id);

        $app = $this->createApplication(true, $portal->id);

        $em = $app->getContainer()->get('entityManager');

        $acl = $app->getContainer()->get('acl');

        $case1 = $em->createEntity('Case', [
            'contactId' => $contact->id,
        ], ['createdById' => '1']);
        $case2 = $em->createEntity('Case', [
            'contactsIds' => [$contact->id],
        ], ['createdById' => '1']);
        $case3 = $em->createEntity('Case', [
        ], ['createdById' => '1']);

        $this->assertFalse($acl->check('Case', 'create'));
        $this->assertFalse($acl->check('Case', 'edit'));
        $this->assertFalse($acl->check('Case', 'delete'));

        $this->assertTrue($acl->check($case1, 'read'));
        $this->assertTrue($acl->check($case2, 'read'));
        $this->assertFalse($acl->check($case3, 'read'));

        $service = $app->getContainer()->get('recordServiceContainer')->get('Case');

        $result = $service->find(SearchParams::create());

        $idList = [];
        foreach ($result->getCollection() as $e) {
            $idList[] = $e->id;
        }

        $this->assertTrue(in_array($case1->id, $idList));
        $this->assertTrue(in_array($case2->id, $idList));
        $this->assertFalse(in_array($case3->id, $idList));
    }

    public function testAccessAccount()
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
            'portalsIds' => [$portal->id],
            'contactId' => $contact->id,
            'accountsIds' => [$account->id],
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

        $this->auth('tester', null, $portal->id);

        $app = $this->createApplication(true, $portal->id);

        $em = $app->getContainer()->get('entityManager');

        $acl = $app->getContainer()->get('acl');

        $case1 = $em->createEntity('Case', [
            'contactId' => $contact->id,
        ], ['createdById' => '1']);
        $case2 = $em->createEntity('Case', [
            'contactsIds' => [$contact->id],
        ], ['createdById' => '1']);
        $case3 = $em->createEntity('Case', [
        ], ['createdById' => '1']);
        $case4 = $em->createEntity('Case', [
            'accountId' => $account->id,
        ], ['createdById' => '1']);

        $this->assertFalse($acl->check('Case', 'create'));
        $this->assertFalse($acl->check('Case', 'edit'));
        $this->assertFalse($acl->check('Case', 'delete'));

        $this->assertTrue($acl->check($case1, 'read'));
        $this->assertTrue($acl->check($case2, 'read'));
        $this->assertFalse($acl->check($case3, 'read'));
        $this->assertTrue($acl->check($case4, 'read'));

        $service = $app->getContainer()->get('recordServiceContainer')->get('Case');
        $result = $service->find(SearchParams::create());

        $idList = [];
        foreach ($result->getCollection() as $e) {
            $idList[] = $e->id;
        }

        $this->assertTrue(in_array($case1->id, $idList));
        $this->assertTrue(in_array($case2->id, $idList));
        $this->assertFalse(in_array($case3->id, $idList));
        $this->assertTrue(in_array($case4->id, $idList));
    }

    public function testAccessOwn()
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
            'portalsIds' => [$portal->id],
            'contactId' => $contact->id,
            'accountsIds' => [$account->id],
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

        $this->auth('tester', null, $portal->id);

        $app = $this->createApplication(true, $portal->id);

        $em = $app->getContainer()->get('entityManager');

        $acl = $app->getContainer()->get('acl');
        $user = $app->getContainer()->get('user');

        $case1 = $em->createEntity('Case', [
            'contactId' => $contact->id,
        ], ['createdById' => '1']);
        $case2 = $em->createEntity('Case', [
            'contactsIds' => [$contact->id],
        ], ['createdById' => '1']);
        $case3 = $em->createEntity('Case', [
        ], ['createdById' => '1']);
        $case4 = $em->createEntity('Case', [
            'accountId' => $account->id,
        ], ['createdById' => '1']);
        $case5 = $em->createEntity('Case', [
            'accountId' => $account->id,
        ], ['createdById' => $user->id]);

        $this->assertFalse($acl->check('Case', 'create'));
        $this->assertFalse($acl->check('Case', 'edit'));
        $this->assertFalse($acl->check('Case', 'delete'));

        $this->assertFalse($acl->check($case1, 'read'));
        $this->assertFalse($acl->check($case2, 'read'));
        $this->assertFalse($acl->check($case3, 'read'));
        $this->assertFalse($acl->check($case4, 'read'));
        $this->assertTrue($acl->check($case5, 'read'));

        $service = $app->getContainer()->get('recordServiceContainer')->get('Case');

        $result = $service->find(SearchParams::create());

        $idList = [];

        foreach ($result->getCollection() as $e) {
            $idList[] = $e->id;
        }

        $this->assertFalse(in_array($case1->id, $idList));
        $this->assertFalse(in_array($case2->id, $idList));
        $this->assertFalse(in_array($case3->id, $idList));
        $this->assertFalse(in_array($case4->id, $idList));
        $this->assertTrue(in_array($case5->id, $idList));
    }
}
