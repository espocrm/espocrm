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

namespace tests\integration\Espo\Core\Formula;

use Espo\Core\Acl\Table;
use Espo\Core\Field\DateTimeOptional;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\UnsafeFunction;
use Espo\Core\Formula\Manager;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\EntityManager;
use tests\integration\Core\BaseTestCase;

class FormulaTest extends BaseTestCase
{
    public function testCountRelatedAndSumRelated()
    {
        $entityManager = $this->getContainer()->getByClass(EntityManager::class);

        $account = $entityManager->getNewEntity('Account');
        $account->set('name', 'test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getNewEntity('Contact');
        $contact->set('name', 'test');
        $entityManager->saveEntity($contact);

        $opportunity = $entityManager->getNewEntity('Opportunity');
        $opportunity->set([
            'name' => '1',
            'amount' => 10,
            'stage' => 'Closed Won',
            'accountId' => $account->getId()
        ]);
        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getNewEntity('Opportunity');
        $opportunity->set([
            'name' => '2',
            'amount' => 20,
            'stage' => 'Prospecting',
            'accountId' => $account->getId(),
        ]);
        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getNewEntity('Opportunity');
        $opportunity->set([
            'name' => '3',
            'amount' => 40,
            'stage' => 'Closed Won'
        ]);
        $entityManager->saveEntity($opportunity);

        $entityManager->getRelation($contact, 'opportunities')
            ->relate($opportunity);

        $formulaManager = $this->getContainer()->getByClass(Manager::class);

        $script = "entity\countRelated('opportunities')";
        $result = $formulaManager->run($script, $account);
        $this->assertEquals(2, $result);

        $script = "entity\countRelated('opportunities', 'won')";
        $result = $formulaManager->run($script, $account);
        $this->assertEquals(1, $result);

        $script = "entity\sumRelated('opportunities', 'amountConverted')";
        $result = $formulaManager->run($script, $account);
        $this->assertEquals(30, $result);

        $script = "entity\sumRelated('opportunities', 'amountConverted', 'won')";
        $result = $formulaManager->run($script, $account);
        $this->assertEquals(10, $result);

        $script = "entity\sumRelated('opportunities', 'amountConverted', 'won')";
        $result = $formulaManager->run($script, $contact);
        $this->assertEquals(40, $result);
    }

    public function testSumRelated()
    {
        $em = $this->getContainer()->getByClass(EntityManager::class);
        $fm = $this->getContainer()->get('formulaManager');

        $contact1 = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);
        $contact2 = $em->createEntity('Contact', [
            'lastName' => '2',
        ]);

        $em->createEntity('Opportunity', [
            'name' => '1',
            'amount' => 1,
            'stage' => 'Closed Won',
            'contactsIds' => [$contact1->getId(), $contact2->getId()],
        ]);

        $em->createEntity('Opportunity', [
            'name' => '2',
            'amount' => 1,
            'stage' => 'Closed Won',
            'contactsIds' => [$contact1->getId(), $contact2->getId()],
        ]);

        $script = "entity\sumRelated('opportunities', 'amountConverted', 'won')";
        $result = $fm->run($script, $contact1);
        $this->assertEquals(2, $result);
    }

    public function testRecordExists()
    {
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $em->createEntity('Meeting', [
            'status' => 'Held',
        ]);
        $em->createEntity('Meeting', [
            'status' => 'Planned',
        ]);

        $fm = $this->getContainer()->get('formulaManager');

        $script = "record\\exists('Meeting', 'status', 'Held')";
        $result = $fm->run($script);
        $this->assertTrue($result);

        $script = "record\\exists('Meeting', 'status', 'Not Held')";
        $result = $fm->run($script);
        $this->assertFalse($result);

        $script = "record\\exists('Meeting', 'status', list('Held', 'Planned'))";
        $result = $fm->run($script);
        $this->assertTrue($result);

        $script = "record\\exists('Meeting', 'status', list('Not Held'))";
        $result = $fm->run($script);
        $this->assertFalse($result);
    }

    public function testRecordCount()
    {
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $em->createEntity('Meeting', [
            'status' => 'Held',
        ]);
        $em->createEntity('Meeting', [
            'status' => 'Planned',
        ]);

        $fm = $this->getContainer()->get('formulaManager');

        $script = "record\\count('Meeting', 'status', 'Held')";
        $result = $fm->run($script);
        $this->assertEquals(1, $result);

        $script = "record\\count('Meeting', 'status', 'Not Held')";
        $result = $fm->run($script);
        $this->assertEquals(0, $result);

        $script = "record\\count('Meeting', 'status', list('Held', 'Planned'))";
        $result = $fm->run($script);
        $this->assertEquals(2, $result);

        $script = "record\\count('Meeting', 'status', list('Not Held'))";
        $result = $fm->run($script);
        $this->assertEquals(0, $result);


        $script = "record\\count('Meeting', 'planned')";
        $result = $fm->run($script);
        $this->assertEquals(1, $result);
    }

    public function testRecordFindOne()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $m1 = $em->createEntity('Meeting', [
            'name' => '1',
            'status' => 'Held',
        ]);
        $m2 = $em->createEntity('Meeting', [
            'name' => '2',
            'status' => 'Planned',
        ]);
        $m3 = $em->createEntity('Meeting', [
            'name' => '3',
            'status' => 'Held',
        ]);
        $m4 = $em->createEntity('Meeting', [
            'name' => '4',
            'status' => 'Planned',
            'assignedUserId' => '1',
        ]);

        $script = "record\\findOne('Meeting', 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertEquals($m1->getId(), $result);

        $script = "record\\findOne('Meeting', 'name', 'desc')";
        $result = $fm->run($script);
        $this->assertEquals($m4->getId(), $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'planned')";
        $result = $fm->run($script);
        $this->assertEquals($m2->getId(), $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Planned')";
        $result = $fm->run($script);
        $this->assertEquals($m2->getId(), $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Planned', 'assignedUserId=', '1')";
        $result = $fm->run($script);
        $this->assertEquals($m4->getId(), $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Not Held')";
        $result = $fm->run($script);
        $this->assertEquals(null, $result);
    }

    public function testFindMany(): void
    {
        $fm = $this->getContainer()->getByClass(Manager::class);
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $m1 = $em->createEntity('Meeting', [
            'name' => '1',
            'status' => 'Held',
        ]);

        $m2 = $em->createEntity('Meeting', [
            'name' => '2',
            'status' => 'Planned',
        ]);

        $m3 = $em->createEntity('Meeting', [
            'name' => '3',
            'status' => 'Held',
        ]);

        $m4 = $em->createEntity('Meeting', [
            'name' => '4',
            'status' => 'Planned',
            'assignedUserId' => '1',
        ]);

        $script = "record\\findMany('Meeting', 2, 'name', null, 'status=', 'Held')";
        $result = $fm->run($script);
        $this->assertEquals([$m1->getId(), $m3->getId()], $result);

        $script = "record\\findMany('Meeting', 2, 'name', true, 'status=', 'Held')";
        $result = $fm->run($script);
        $this->assertEquals([$m3->getId(), $m1->getId()], $result);

        $script = "record\\findMany('Meeting', 1, 'name', 'desc', 'status=', 'Held')";
        $result = $fm->run($script);
        $this->assertEquals([$m3->getId()], $result);

        $script = "record\\findMany('Meeting', 2, 'name', 'ASC', 'planned')";
        $result = $fm->run($script);
        $this->assertEquals([$m2->getId(), $m4->getId()], $result);
    }

    public function testRecordFindRelatedOne1()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $account = $em->createEntity('Account', [
            'name' => 'Test',
        ]);

        $m1 = $em->createEntity('Meeting', [
            'name' => '1',
            'status' => 'Held',
            'parentType' => 'Account',
            'parentId' => $account->getId(),
        ]);
        $m2 = $em->createEntity('Meeting', [
            'name' => '2',
            'status' => 'Planned',
            'parentType' => 'Account',
            'parentId' => $account->getId(),
        ]);
        $m3 = $em->createEntity('Meeting', [
            'name' => '3',
            'status' => 'Held',
            'parentType' => 'Account',
            'parentId' => $account->getId(),
        ]);
        $m4 = $em->createEntity('Meeting', [
            'name' => '4',
            'status' => 'Planned',
            'assignedUserId' => '1',
            'parentType' => 'Account',
            'parentId' => $account->getId(),
        ]);

        $c0 = $em->createEntity('Contact', [
            'lastName' => '0',
        ]);

        $c1 = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);
        $c2 = $em->createEntity('Contact', [
            'lastName' => '2',
        ]);

        $em->getRelation($account, 'contacts')->relate($c1);
        $em->getRelation($account, 'contacts')->relate($c2);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'meetings', 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertEquals($m1->getId(), $result);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'meetings', 'name', 'desc', 'planned')";
        $result = $fm->run($script);
        $this->assertEquals($m4->getId(), $result);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'meetings', 'name', 'desc', 'held')";
        $result = $fm->run($script);
        $this->assertEquals($m3->getId(), $result);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'meetings', 'name', 'desc', 'status', 'Held')";
        $result = $fm->run($script);
        $this->assertEquals($m3->getId(), $result);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'meetingsPrimary', 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertEquals($m1->getId(), $result);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'contacts', 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertEquals($c1->getId(), $result);

        $script = "record\\findRelatedOne('Account', '".$account->getId()."', 'contacts', 'name', 'asc', 'lastName', '2')";
        $result = $fm->run($script);
        $this->assertEquals($c2->getId(), $result);
    }

    public function testRecordFindRelatedOne2()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
        ]);

        $o = $em->createEntity('Opportunity', [
            'accountId' => $a->getId(),
        ]);

        $script = "record\\findRelatedOne('Opportunity', '".$o->getId()."', 'account')";
        $result = $fm->run($script);
        $this->assertEquals($a->getId(), $result);
    }

    public function testRecordFindRelatedMany()
    {
        $fm = $this->getContainer()->getByClass(Manager::class);
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', []);
        $c1 = $em->createEntity('Contact', []);
        $c2 = $em->createEntity('Contact', []);

        $o1 = $em->createEntity('Opportunity', [
            'accountId' => $a->getId(),
            'stage' => 'Prospecting',
            'name' => '1',
            'contactsIds' => [$c1->getId()],
        ]);
        $o2 = $em->createEntity('Opportunity', [
            'accountId' => $a->getId(),
            'stage' => 'Closed Won',
            'name' => '2',
            'contactsIds' => [$c1->getId()],
        ]);
        $o3 = $em->createEntity('Opportunity', [
            'accountId' => $a->getId(),
            'stage' => 'Prospecting',
            'name' => '3',
            'contactsIds' => [$c2->getId()],
        ]);

        $em->createEntity('Opportunity', []);

        $script = "record\\findRelatedMany('Account', '".$a->getId()."', 'opportunities', 2, null, null, 'open')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, in_array($o1->getId(), $result));
        $this->assertEquals(true, in_array($o3->getId(), $result));

        $script = "record\\findRelatedMany('Account', '".$a->getId()."', 'opportunities', 3)";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, in_array($o1->getId(), $result));
        $this->assertEquals(true, in_array($o2->getId(), $result));


        $script = "record\\findRelatedMany('Account', '".$a->getId()."', 'opportunities', 3, 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result));
        $this->assertEquals([$o1->getId(), $o2->getId(), $o3->getId()], $result);

        $script = "record\\findRelatedMany('Account', '".$a->getId()."', 'opportunities', 3, 'name', 'asc', 'stage=', 'Prospecting')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals([$o1->getId(), $o3->getId()], $result);

        $script = "record\\findRelatedMany('Contact', '{$c1->getId()}', 'opportunities', 10, 'name')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals([$o1->getId(), $o2->getId()], $result);
    }

    public function testRecordAttribute()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $m1 = $em->createEntity('Meeting', [
            'name' => '1',
            'status' => 'Held',
        ]);

        $script = "record\\attribute('Meeting', '".$m1->getId()."', 'name')";
        $result = $fm->run($script);
        $this->assertEquals('1', $result);
    }

    /**
     * @throws Error
     */
    public function testRecordCreateUpdate()
    {
        $em = $this->getEntityManager();
        $fm = $this->getContainer()->getByClass(Manager::class);

        $script = "record\\create('Meeting', 'name', 'test', 'assignedUserId', '1')";
        $id = $fm->run($script);

        $this->assertIsString('string', $id);

        $meeting = $em->getEntityById(Meeting::ENTITY_TYPE, $id);

        $this->assertNotNull($meeting);
        $this->assertEquals('1', $meeting->get('assignedUserId'));

        //

        $script = "record\\update('Meeting', '$id', 'name', 'test-changed', 'assignedUserId', '2')";
        $fm->run($script);

        $meeting = $em->getEntityById(Meeting::ENTITY_TYPE, $id);
        $this->assertEquals('2', $meeting->get('assignedUserId'));

        //

        $script = "
            \$data = object\\create();
            object\\set(\$data, 'name', 'test-1');

            \$id = record\\create('Meeting', \$data);
        ";

        $vars = (object) [];

        $fm->run($script, null, $vars);

        $id2 = $vars->id;

        $this->assertIsString('string', $id2);

        $meeting = $em->getEntityById(Meeting::ENTITY_TYPE, $id2);

        $this->assertNotNull($meeting);
        $this->assertEquals('test-1', $meeting->get('name'));

        //

        $script = "
            \$data = object\\create();
            object\\set(\$data, 'name', 'test-updated');

            record\\update('Meeting', '$id2', \$data);
        ";

        $fm->run($script);

        $meeting = $em->getEntityById(Meeting::ENTITY_TYPE, $id2);
        $this->assertEquals('test-updated', $meeting->get('name'));
    }

    public function testRecordDelete(): void
    {
        $em = $this->getContainer()->getByClass(EntityManager::class);
        $fm = $this->getContainer()->getByClass(Manager::class);

        $entity = $em->createEntity(Meeting::ENTITY_TYPE, ['name' => 'Test']);

        $script = "record\\delete('Meeting', '{$entity->getId()}')";
        $fm->run($script);

        $entity = $em->getEntityById(Meeting::ENTITY_TYPE, $entity->getId());

        $this->assertNull($entity);
    }

    public function testPasswordGenerate()
    {
        $fm = $this->getContainer()->get('formulaManager');

        $script = "password\\generate()";
        $result = $fm->run($script);
        $this->assertTrue(is_string($result));
    }

    public function testPasswordHash()
    {
        $fm = $this->getContainer()->get('formulaManager');

        $script1 = "password\\hash('1')";
        $result1 = $fm->run($script1);

        $script2 = "password\\hash('2')";
        $result2 = $fm->run($script2);

        $this->assertTrue(is_string($result1));

        $this->assertTrue($result1 !== $result2);
    }

    public function testEntityGetLinkColumn()
    {
        $fm = $this->getContainer()->get('formulaManager');
         $em = $this->getContainer()->getByClass(EntityManager::class);

        $lead = $em->createEntity('Lead', []);
        $targetList = $em->createEntity('TargetList', []);

        $em->getRelation($lead, 'targetLists')->relateById($targetList->getId(), [
            'optedOut' => true,
        ]);

        $script = "entity\\getLinkColumn('targetLists', '{$targetList->getId()}', 'optedOut')";

        $result = $fm->run($script, $lead);
        $this->assertTrue($result);


        $em->getRelation($lead, 'targetLists')->relateById($targetList->getId(), [
            'optedOut' => false,
        ]);

        $result = $fm->run($script, $lead);
        $this->assertFalse($result);
    }

    public function testRecordRelate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $o = $em->createEntity('Opportunity', [
            'name' => '1',
        ]);

        $script = "record\\relate('Account', '".$a->getId()."', 'opportunities', '".$o->getId()."')";
        $result = $fm->run($script);

        $this->assertTrue($result);
        $this->assertTrue($em->getRelation($a, 'opportunities')->isRelated($o));
    }

    public function testRecordRelate1()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $o = $em->createEntity('Opportunity', [
            'name' => '1',
        ]);

        $script = "record\\relate('Account', '".$a->getId()."', 'opportunities', list('".$o->getId()."'))";
        $result = $fm->run($script);

        $this->assertTrue($result);
        $this->assertTrue($em->getRelation($a, 'opportunities')->isRelated($o));
    }

    public function testRecordUnrelate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);

        $o = $em->createEntity('Opportunity', [
            'name' => '1',
        ]);

        $em->getRelation($a, 'opportunities')->relate($o);

        $script = "record\\unrelate('Account', '".$a->getId()."', 'opportunities', '".$o->getId()."')";
        $result = $fm->run($script);

        $this->assertTrue($result);
        $this->assertFalse($em->getRelation($a, 'opportunities')->isRelated($o));
    }

    public function testRecordRelationColumn()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $c = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);

        $em->getRelation($a, 'contacts')->relateById($c->getId(), ['role' => 'test']);

        $script = "record\\relationColumn('Account', '{$a->getId()}', 'contacts', '{$c->getId()}', 'role')";
        $result = $fm->run($script);

        $this->assertEquals('test', $result);
    }

    public function testRecordUpdateRelationColumn()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $c = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);

        $em->getRelation($a, 'contacts')->relateById($c->getId());

        $script = "record\\updateRelationColumn('Account', '{$a->getId()}', 'contacts', '{$c->getId()}', 'role', 'test')";
        $fm->run($script);

        $value = $em->getRelation($a, 'contacts')->getColumnById($c->getId(), 'role');

        $this->assertEquals('test', $value);
    }

    public function testExtAccountFindByEmailAddress()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a1 = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $a2 = $em->createEntity('Account', [
            'name' => '2',
            'emailAddress' => 'a@gmail.com',
        ]);
        $a3 = $em->createEntity('Account', [
            'name' => '3',
            'emailAddress' => 'a@hello-test.com',
        ]);
        $a4 = $em->createEntity('Account', [
            'name' => '4',
            'emailAddress' => 'a@brom.com',
        ]);

        $c2 = $em->createEntity('Contact', [
            'lastName' => '2',
            'emailAddress' => 'c@gmail.com',
            'accountId' => $a2->getId(),
        ]);
        $c4 = $em->createEntity('Contact', [
            'lastName' => '4',
            'emailAddress' => 'c@brom.com',
        ]);

        $script = "ext\\account\\findByEmailAddress('b@hello-test.com')";
        $this->assertEquals($a3->getId(), $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('b@gmail.com')";
        $this->assertEquals(null, $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('c@gmail.com')";
        $this->assertEquals($a2->getId(), $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('b@brom.com')";
        $this->assertEquals($a4->getId(), $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('c@brom.com')";
        $this->assertEquals($a4->getId(), $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('')";
        $this->assertEquals(null, $fm->run($script));
    }

    public function testExtEmailApplyTemplate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);

        $c = $em->createEntity('Contact', [
            'lastName' => 'Contact 1',
            'emailAddress' => 'test@tester.com',
        ]);

        $attachment1 = $em->createEntity('Attachment', [
            'name' => 'a1',
            'contents' => '1',
        ]);
        $attachment2 = $em->createEntity('Attachment', [
            'name' => 'a2',
            'contents' => '2',
        ]);

        $emailTemplate = $em->createEntity('EmailTemplate', [
            'name' => '1',
            'subject' => 'Test',
            'body' => 'Test {Account.name} Hello',
            'isHtml' => false,
            'attachmentsIds' => [$attachment2->getId()],
        ]);

        $email = $em->createEntity('Email', [
            'to' => 'test@tester.com',
            'status' => 'Draft',
            'attachmentsIds' => [$attachment1->getId()],
        ]);

        $script = "ext\\email\\applyTemplate('{$email->getId()}', '{$emailTemplate->getId()}', 'Account', '{$a->getId()}')";
        $fm->run($script);

        $email = $em->getEntityById('Email', $email->getId());

        $attachmentsIds = $email->getLinkMultipleIdList('attachments');

        $this->assertEquals('Test', $email->get('name'));
        $this->assertEquals('Test 1 Hello', $email->get('body'));
        $this->assertEquals(false, $email->get('isHtml'));
        $this->assertEquals(2, count($attachmentsIds));

        $case = $em->createEntity('Case', [
            'name' => 'Case 1',
        ]);

        $email = $em->createEntity('Email', [
            'to' => 'test@tester.com',
            'status' => 'Draft',
            'parentId' => $case->getId(),
            'parentType' => 'Case',
        ]);
        $emailTemplate = $em->createEntity('EmailTemplate', [
            'name' => '1',
            'subject' => 'Test',
            'body' => 'Test {Person.name} Hello, {Case.name}',
            'isHtml' => false,
        ]);

        $script = "ext\\email\\applyTemplate('{$email->getId()}', '{$emailTemplate->getId()}')";
        $fm->run($script);

        $email = $em->getEntity('Email', $email->getId());

        $this->assertEquals('Test', $email->get('name'));
        $this->assertEquals('Test Contact 1 Hello, Case 1', $email->get('body'));
    }

    public function testExtPdfGenerate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);

        $template = $em->createEntity('Template', [
            'body' => 'Test {{name}} hello',
            'entityType' => 'Account',
        ]);

        $script = "ext\\pdf\\generate('Account', '{$a->getId()}', '{$template->getId()}', 'test')";
        $id = $fm->run($script);

        $this->assertIsString($id);

        $attachment = $em->getEntity('Attachment', $id);

        $this->assertNotNull($attachment);
        $this->assertEquals('test.pdf', $attachment->get('name'));
        $this->assertTrue(file_exists('data/upload/' . $attachment->getId()));


        $script = "ext\\pdf\\generate('Account', '{$a->getId()}', '{$template->getId()}', 'test.pdf')";
        $id = $fm->run($script);

        $attachment = $em->getEntity('Attachment', $id);

        $this->assertEquals('test.pdf', $attachment->get('name'));


        $script = "ext\\pdf\\generate('Account', '{$a->getId()}', '{$template->getId()}')";
        $id = $fm->run($script);

        $attachment = $em->getEntity('Attachment', $id);

        $this->assertEquals('1.pdf', $attachment->get('name'));
    }

    public function testEnvUserAttribute()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $user = $this->getContainer()->get('user');

        $script = "env\\userAttribute('id')";
        $id = $fm->run($script);
        $this->assertEquals($id, $user->getId());
    }

    public function testCalendarUserBusy(): void
    {
        $fm = $this->getContainer()->getByClass(Manager::class);
        $user = $this->getContainer()->getByClass(User::class);
        $em = $this->getContainer()->getByClass(EntityManager::class);

        $dateStart = DateTimeOptional::createNow();
        $dateEnd = $dateStart->addHours(1);

        /** @var Meeting $meeting */
        $meeting = $em->getRDBRepositoryByClass(Meeting::class)->getNew();

        $meeting
            ->setDateStart($dateStart)
            ->setDateEnd($dateEnd)
            ->setAssignedUserId($user->getId());

        $em->saveEntity($meeting);

        $script = sprintf(
            "ext\\calendar\\userIsBusy('%s', '%s', '%s')",
            $user->getId(),
            $dateStart->toString(),
            $dateEnd->toString()
        );
        $this->assertTrue($fm->run($script));

        $script = sprintf(
            "ext\\calendar\\userIsBusy('%s', '%s', '%s')",
            $user->getId(),
            $dateStart->addHours(-1)->toString(),
            $dateEnd->addHours(1)->toString()
        );
        $this->assertTrue($fm->run($script));

        $script = sprintf(
            "ext\\calendar\\userIsBusy('%s', '%s', '%s')",
            $user->getId(),
            $dateStart->addDays(-1)->toString(),
            $dateEnd->addDays(-1)->toString()
        );
        $this->assertFalse($fm->run($script));

        $script = sprintf(
            "ext\\calendar\\userIsBusy('%s', '%s', '%s', '%s', '%s')",
            $user->getId(),
            $dateStart->toString(),
            $dateEnd->toString(),
            $meeting->getEntityType(),
            $meeting->getId()
        );
        $this->assertFalse($fm->run($script));
    }

    public function testIsRelated(): void
    {
        $em = $this->getEntityManager();
        $fm = $this->getContainer()->getByClass(Manager::class);

        $account = $em->createEntity(Account::ENTITY_TYPE, []);
        $opp = $em->createEntity(Opportunity::ENTITY_TYPE, []);

        $em
            ->getRDBRepositoryByClass(Account::class)
            ->getRelation($account, 'opportunities')
            ->relate($opp);

        $script = sprintf(
            "entity\\isRelated('opportunities', '%s')",
            $opp->getId()
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertTrue($fm->run($script, $account));
    }

    public function testAcl(): void
    {
        $em = $this->getEntityManager();
        $fm = $this->getContainer()->getByClass(Manager::class);

        $user = $this->createUser('test', [
            'massUpdatePermission' => Table::LEVEL_YES,
            'data' => [
                Account::ENTITY_TYPE => [
                    'create' => Table::LEVEL_NO,
                    'read' => Table::LEVEL_ALL,
                    'edit' => Table::LEVEL_OWN,
                    'delete' => Table::LEVEL_NO,
                ],
            ],
        ]);

        $account1 = $em->createEntity(Account::ENTITY_TYPE, [
            'assignedUserId' => $user->getId(),
        ]);

        $account2 = $em->createEntity(Account::ENTITY_TYPE);

        $script = "ext\\acl\\checkEntity('{$user->getId()}', 'Account', '{$account2->getId()}')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertTrue($fm->run($script));

        $script = "ext\\acl\\checkEntity('{$user->getId()}', 'Account', '{$account1->getId()}', 'edit')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertTrue($fm->run($script));

        $script = "ext\\acl\\checkEntity('{$user->getId()}', 'Account', '{$account2->getId()}', 'edit')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertFalse($fm->run($script));

        $script = "ext\\acl\\checkScope('{$user->getId()}', 'Account')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertTrue($fm->run($script));

        $script = "ext\\acl\\checkScope('{$user->getId()}', 'Account', 'delete')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertFalse($fm->run($script));

        $script = "ext\\acl\\getLevel('{$user->getId()}', 'Account', 'delete')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(Table::LEVEL_NO, $fm->run($script));

        $script = "ext\\acl\\getLevel('{$user->getId()}', 'Account', 'read')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(Table::LEVEL_ALL, $fm->run($script));

        $script = "ext\\acl\\getPermissionLevel('{$user->getId()}', 'massUpdate')";
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(Table::LEVEL_YES, $fm->run($script));
    }

    public function testSafe(): void
    {
        $fm = $this->getContainer()->getByClass(Manager::class);

        $script = "record\\create('Account')";

        $this->expectException(UnsafeFunction::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $fm->runSafe($script);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFilterWhere(): void
    {
        $em = $this->getEntityManager();
        $fm = $this->getContainer()->getByClass(Manager::class);

        $account1 = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'a1']);

        $em->createEntity(Account::ENTITY_TYPE, ['name' => 'a2']);

        $contact1 = $em->createEntity(Contact::ENTITY_TYPE, [
            'lastName' => 'c1_1',
            'accountId' => $account1->getId(),
        ]);

        $em->createEntity(Contact::ENTITY_TYPE, [
            'lastName' => 'c1_2',
            'accountId' => $account1->getId(),
        ]);

        $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account1->getId(),
            'amount' => 100.0,
            'name' => 'o1_1',
        ]);

        $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account1->getId(),
            'amount' => 150.0,
            'name' => 'o1_2',
        ]);

        $script = "
            \$where = object\create(\$w);
            \$where['type'] = 'and';
            \$where['value'] = list(
                (
                    \$item1 = object\create();
                    \$item1['type'] = 'equals';
                    \$item1['attribute'] = 'name';
                    \$item1['value'] = 'a1';
                    \$item1;
                )
            );

            record\\findMany('Account', 2, null, null, \$where);
        ";
        $result = $fm->run($script);
        $this->assertEquals([$account1->getId()], $result);

        $script = "
            \$where = object\create(\$w);
            \$where['type'] = 'and';
            \$where['value'] = list(
                (
                    \$item1 = object\create();
                    \$item1['type'] = 'equals';
                    \$item1['attribute'] = 'name';
                    \$item1['value'] = 'a1';
                    \$item1;
                )
            );

            record\\findOne('Account', null, null, \$where);
        ";
        $result = $fm->run($script);
        $this->assertEquals($account1->getId(), $result);

        $script = "
            \$where = object\create(\$w);
            \$where['type'] = 'and';
            \$where['value'] = list(
                (
                    \$item1 = object\create();
                    \$item1['type'] = 'equals';
                    \$item1['attribute'] = 'name';
                    \$item1['value'] = 'a1';
                    \$item1;
                )
            );

            record\\exists('Account', \$where);
        ";
        $result = $fm->run($script);
        $this->assertTrue($result);

        $script = "
            \$where = object\create(\$w);
            \$where['type'] = 'and';
            \$where['value'] = list(
                (
                    \$item1 = object\create();
                    \$item1['type'] = 'equals';
                    \$item1['attribute'] = 'name';
                    \$item1['value'] = 'a1';
                    \$item1;
                )
            );

            record\\count('Account', \$where);
        ";
        $result = $fm->run($script);
        $this->assertEquals(1, $result);

        $script = "
            \$item = object\create();
            \$item['type'] = 'equals';
            \$item['attribute'] = 'lastName';
            \$item['value'] = 'c1_1';
            \$item;

            record\\findRelatedOne('Account', '{$account1->getId()}', 'contacts', null, null, \$item);
        ";
        $result = $fm->run($script);
        $this->assertEquals($contact1->getId(), $result);

        $script = "
            \$item = object\create();
            \$item['type'] = 'equals';
            \$item['attribute'] = 'lastName';
            \$item['value'] = 'c1_1';
            \$item;

            record\\findRelatedMany('Account', '{$account1->getId()}', 'contacts', 2, null, null, \$item);
        ";
        $result = $fm->run($script);
        $this->assertEquals([$contact1->getId()], $result);

        $script = "
            \$item = object\create();
            \$item['type'] = 'equals';
            \$item['attribute'] = 'lastName';
            \$item['value'] = 'c1_1';
            \$item;

            entity\\countRelated('contacts', \$item);
        ";
        $result = $fm->run($script, $account1);
        $this->assertEquals(1, $result);

        $script = "
            \$item = object\create();
            \$item['type'] = 'equals';
            \$item['attribute'] = 'name';
            \$item['value'] = 'o1_1';
            \$item;

            entity\\sumRelated('opportunities', 'amount', \$item);
        ";
        $result = $fm->run($script, $account1);
        $this->assertEquals(100.0, $result);
    }
}
