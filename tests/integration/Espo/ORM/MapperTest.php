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

namespace tests\integration\Espo\ORM;

use Espo\ORM\EntityManager;
use tests\integration\Core\BaseTestCase;

class MapperTest extends BaseTestCase
{
    public function testRelate1()
    {
        $app = $this->createApplication();

        /** @var EntityManager $entityManager */
        $entityManager = $app->getContainer()->getByClass(EntityManager::class);

        $account = $entityManager->getNewEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getNewEntity('Contact');
        $contact->set('lastName', 'Test');
        $entityManager->saveEntity($contact);

        $entityManager->getRelation($account, 'contacts')->relate($contact);
        $isRelated = $entityManager->getRelation($account, 'contacts')->isRelated($contact);
        $this->assertTrue($isRelated);

        $entityManager->getRelation($account, 'contacts')->unrelate($contact);
        $isRelated = $entityManager->getRelation($account, 'contacts')->isRelated($contact);
        $this->assertFalse($isRelated);
    }

    public function testRelate2()
    {
        $app = $this->createApplication();

        /** @var EntityManager $entityManager */
        $entityManager = $app->getContainer()->getByClass(EntityManager::class);

        $account = $entityManager->getNewEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getNewEntity('Contact');
        $contact->set('lastName', 'Test');
        $entityManager->saveEntity($contact);

        $entityManager->getRelation($contact, 'account')->relate($account);
        $isRelated = $entityManager->getRelation($contact, 'account')->isRelated($account);
        $this->assertTrue($isRelated);

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        $entityManager->getRelation($contact, 'account')->unrelate($account);

        $isRelated = $entityManager->getRelation($contact, 'account')->isRelated($account);
        $this->assertFalse($isRelated);
    }

    public function testRelate3WithEntityReFetching()
    {
        $app = $this->createApplication();

        /** @var EntityManager $entityManager */
        $entityManager = $app->getContainer()->getByClass(EntityManager::class);

        $account = $entityManager->getNewEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getNewEntity('Contact');
        $contact->set('lastName', 'Test');
        $entityManager->saveEntity($contact);

        $entityManager->getRDBRepository('Contact')
            ->getRelation($contact, 'account')
            ->relate($account);

        $contact = $entityManager->getEntityById('Contact', $contact->get('id'));

        $isRelated = $entityManager->getRDBRepository('Contact')
            ->getRelation($contact, 'account')
            ->isRelated($account);

        $this->assertTrue($isRelated);

        /** @var EntityManager $entityManager */
        $entityManager = $app->getContainer()->getByClass(EntityManager::class);

        $entityManager->getRDBRepository('Contact')
            ->getRelation($contact, 'account')
            ->unrelate($account);

        $contact = $entityManager->getEntityById('Contact', $contact->get('id'));

        $isRelated = $entityManager
            ->getRDBRepository('Contact')
            ->getRelation($contact, 'account')
            ->isRelated($account);

        $this->assertFalse($isRelated);
    }

    public function testRelate4()
    {
        $app = $this->createApplication();

        /** @var EntityManager $entityManager */
        $entityManager = $app->getContainer()->getByClass(EntityManager::class);

        $account = $entityManager->getNewEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $task = $entityManager->getNewEntity('Task');
        $task->set('name', 'Test');
        $entityManager->saveEntity($task);


        $entityManager->getRelation($task, 'parent')->relate($account);
        $isRelated = $entityManager->getRelation($task, 'parent')->isRelated($account);
        $this->assertTrue($isRelated);

        $task = $entityManager->getEntityById('Task', $task->getId());

        $entityManager->getRelation($task, 'parent')->unrelate($account);
        $isRelated = $entityManager->getRelation($task, 'parent')->isRelated($account);
        $this->assertFalse($isRelated);
    }

    public function testRelateOneToOne1()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRelation($l1, 'createdAccount')->relate($a1);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a2);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRelation($a2, 'originalLead')->isRelated($l1);
        $this->assertFalse($isRelated);


        $em->getRelation($l1, 'createdAccount')->relate($a2);

        $isRelated = $em->getRelation($a2, 'originalLead')->isRelated($l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a2);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l1);
        $this->assertFalse($isRelated);

        $c = $em->getRDBRepository('Lead')->where(['createdAccountId' => $a1->getId()])->count();
        $this->assertEquals(0, $c);

        $c = $em->getRDBRepository('Lead')->where(['createdAccountId' => $a2->getId()])->count();
        $this->assertEquals(1, $c);
    }

    public function testRelateOneToOne2()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);

        $em->getRelation($a1, 'originalLead')->relate($l1);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a2);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRelation($a2, 'originalLead')->isRelated($l1);
        $this->assertFalse($isRelated);

        $em->getRelation($a2, 'originalLead')->relate($l1);

        $isRelated = $em->getRelation($a2, 'originalLead')->isRelated($l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a2);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l1);
        $this->assertFalse($isRelated);

        $c = $em->getRDBRepository('Lead')
            ->where(['createdAccountId' => $a1->getId()])
            ->count();

        $this->assertEquals(0, $c);

        $c = $em->getRDBRepository('Lead')
            ->where(['createdAccountId' => $a2->getId()])
            ->count();

        $this->assertEquals(1, $c);
    }

    public function testRelateOneToOne3()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $l2 = $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRelation($l1, 'createdAccount')->relate($a1);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l1);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l2, 'createdAccount')->isRelated($a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l2);
        $this->assertFalse($isRelated);

        $em->getRelation($l2, 'createdAccount')->relate($a1);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l2);
        $this->assertTrue($isRelated);

        $isRelated = $em->getRelation($l2, 'createdAccount')->isRelated($a1);
        $this->assertTrue($isRelated);

        $l1 = $em->getEntityById('Lead', $l1->getId());

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);
        $this->assertFalse($isRelated);

        $isRelated = $em->getRelation($a1, 'originalLead')->isRelated($l1);
        $this->assertFalse($isRelated);

        $c = $em->getRDBRepository('Lead')
            ->where(['createdAccountId' => $a1->getId()])
            ->count();

        $this->assertEquals(1, $c);
    }

    public function testUnrelateOneToOne1()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $em->createEntity('Account', [
            'name' => '2',
        ]);
        $l1 = $em->createEntity('Lead', [
            'lastName' => '1',
        ]);
        $em->createEntity('Lead', [
            'lastName' => '2',
        ]);

        $em->getRelation($l1, 'createdAccount')->relate($a1);
        $em->getRelation($l1, 'createdAccount')->unrelate($a1);

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);

        $this->assertFalse($isRelated);

        $em->getRelation($l1, 'createdAccount')->relate($a1);
        $em->getRelation($a1, 'originalLead')->unrelate($l1);

        $l1 = $em->getEntityById('Lead', $l1->getId());

        $isRelated = $em->getRelation($l1, 'createdAccount')->isRelated($a1);

        $this->assertFalse($isRelated);
    }
}
