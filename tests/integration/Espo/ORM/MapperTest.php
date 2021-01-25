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

namespace tests\integration\Espo\ORM;

class MapperTest extends \tests\integration\Core\BaseTestCase
{
    public function testRelate1()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getEntity('Contact');
        $contact->set('lastName', 'Test');;
        $entityManager->saveEntity($contact);

        $entityManager->getRepository('Account')->relate($account, 'contacts', $contact);
        $isRelated = $entityManager->getRepository('Account')->isRelated($account, 'contacts', $contact);
        $this->assertTrue($isRelated);

        $entityManager->getRepository('Account')->unrelate($account, 'contacts', $contact);
        $isRelated = $entityManager->getRepository('Account')->isRelated($account, 'contacts', $contact);
        $this->assertFalse($isRelated);
    }

    public function testRelate2()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getEntity('Contact');
        $contact->set('lastName', 'Test');;
        $entityManager->saveEntity($contact);

        $entityManager->getRepository('Contact')->relate($contact, 'account', $account);
        $isRelated = $entityManager->getRepository('Contact')->isRelated($contact, 'account', $account);
        $this->assertTrue($isRelated);

        $contact = $entityManager->getEntity('Contact', $contact->id);

        $entityManager->getRepository('Contact')->unrelate($contact, 'account', $account);

        $isRelated = $entityManager->getRepository('Contact')->isRelated($contact, 'account', $account);
        $this->assertFalse($isRelated);
    }

    public function testRelate3WithEntityRefetching()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getEntity('Contact');
        $contact->set('lastName', 'Test');;
        $entityManager->saveEntity($contact);

        $entityManager->getRepository('Contact')
            ->getRelation($contact, 'account')
            ->relate($account);

        $contact = $entityManager->getEntity('Contact', $contact->get('id'));

        $isRelated = $entityManager->getRepository('Contact')
            ->getRelation($contact, 'account')
            ->isRelated($account);

        $this->assertTrue($isRelated);

        $contact = $entityManager->getEntity('Contact', $contact->id);

        $entityManager->getRepository('Contact')
            ->getRelation($contact, 'account')
            ->unrelate($account);

        $contact = $entityManager->getEntity('Contact', $contact->get('id'));

        $isRelated = $entityManager->getRepository('Contact')
            ->getRelation($contact, 'account')
            ->isRelated($account);

        $this->assertFalse($isRelated);
    }

    public function testRelate4()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $task = $entityManager->getEntity('Task');
        $task->set('name', 'Test');;
        $entityManager->saveEntity($task);

        $entityManager->getRepository('Task')->relate($task, 'parent', $account);
        $isRelated = $entityManager->getRepository('Task')->isRelated($task, 'parent', $account);
        $this->assertTrue($isRelated);

        $task = $entityManager->getEntity('Task', $task->id);

        $entityManager->getRepository('Task')->unrelate($task, 'parent', $account);
        $isRelated = $entityManager->getRepository('Task')->isRelated($task, 'parent', $account);
        $this->assertFalse($isRelated);
    }

    public function testRelateOneToOne1()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $l2 = $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRepository('Lead')->relate($l1, 'createdAccount', $a1);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a2);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a2, 'originalLead', $l1);
        $this->assertFalse($isRelated);


        $em->getRepository('Lead')->relate($l1, 'createdAccount', $a2);

        $isRelated = $em->getRepository('Account')->isRelated($a2, 'originalLead', $l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a2);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l1);
        $this->assertFalse($isRelated);

        $c = $em->getRepository('Lead')->where(['createdAccountId' => $a1->id])->count();
        $this->assertEquals(0, $c);

        $c = $em->getRepository('Lead')->where(['createdAccountId' => $a2->id])->count();
        $this->assertEquals(1, $c);
    }

    public function testRelateOneToOne2()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $l2 = $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRepository('Account')->relate($a1, 'originalLead', $l1);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a2);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a2, 'originalLead', $l1);
        $this->assertFalse($isRelated);

        $em->getRepository('Account')->relate($a2, 'originalLead', $l1);

        $isRelated = $em->getRepository('Account')->isRelated($a2, 'originalLead', $l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a2);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l1);
        $this->assertFalse($isRelated);

        $c = $em->getRepository('Lead')->where(['createdAccountId' => $a1->id])->count();
        $this->assertEquals(0, $c);

        $c = $em->getRepository('Lead')->where(['createdAccountId' => $a2->id])->count();
        $this->assertEquals(1, $c);
    }

    public function testRelateOneToOne3()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $l2 = $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRepository('Lead')->relate($l1, 'createdAccount', $a1);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l2, 'createdAccount', $a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l2);
        $this->assertFalse($isRelated);


        $em->getRepository('Lead')->relate($l2, 'createdAccount', $a1);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l2);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRepository('Lead')->isRelated($l2, 'createdAccount', $a1);
        $this->assertTrue($isRelated);

        $l1 = $em->getEntity('Lead', $l1->id);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRepository('Account')->isRelated($a1, 'originalLead', $l1);
        $this->assertFalse($isRelated);

        $c = $em->getRepository('Lead')->where(['createdAccountId' => $a1->id])->count();
        $this->assertEquals(1, $c);
    }

    public function testUnrelateOneToOne1()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $l2 = $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRepository('Lead')->relate($l1, 'createdAccount', $a1);
        $em->getRepository('Lead')->unrelate($l1, 'createdAccount', $a1);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertFalse($isRelated);

        $em->getRepository('Lead')->relate($l1, 'createdAccount', $a1);
        $em->getRepository('Account')->unrelate($a1, 'originalLead', $l1);

        $l1 = $em->getEntity('Lead', $l1->id);

        $isRelated = $em->getRepository('Lead')->isRelated($l1, 'createdAccount', $a1);
        $this->assertFalse($isRelated);
    }
}
