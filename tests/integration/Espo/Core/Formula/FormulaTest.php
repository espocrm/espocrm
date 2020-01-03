<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\integration\Espo\Core\Formula;

class FormulaTest extends \tests\integration\Core\BaseTestCase
{

    public function testCountRelatedAndSumRelated()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getEntity('Contact');
        $contact->set('name', 'test');
        $entityManager->saveEntity($contact);

        $opportunity = $entityManager->getEntity('Opportunity');
        $opportunity->set([
            'name' => '1',
            'amount' => 10,
            'stage' => 'Closed Won',
            'accountId' => $account->id
        ]);
        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getEntity('Opportunity');
        $opportunity->set([
            'name' => '2',
            'amount' => 20,
            'stage' => 'Prospecting',
            'accountId' => $account->id
        ]);
        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getEntity('Opportunity');
        $opportunity->set([
            'name' => '3',
            'amount' => 40,
            'stage' => 'Closed Won'
        ]);
        $entityManager->saveEntity($opportunity);

        $entityManager->getRepository('Contact')->relate($contact, 'opportunities', $opportunity);

        $formulaManager = $this->getContainer()->get('formulaManager');

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
        $em = $this->getContainer()->get('entityManager');
        $fm = $this->getContainer()->get('formulaManager');

        $contact1 = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);
        $contact2 = $em->createEntity('Contact', [
            'lastName' => '2',
        ]);

        $opportunity1 = $em->createEntity('Opportunity', [
            'name' => '1',
            'amount' => 1,
            'stage' => 'Closed Won',
            'contactsIds' => [$contact1->id, $contact2->id],
        ]);

        $opportunity2 = $em->createEntity('Opportunity', [
            'name' => '2',
            'amount' => 1,
            'stage' => 'Closed Won',
            'contactsIds' => [$contact1->id, $contact2->id],
        ]);

        $script = "entity\sumRelated('opportunities', 'amountConverted', 'won')";
        $result = $fm->run($script, $contact1);
        $this->assertEquals(2, $result);
    }

    public function testRecordExists()
    {
        $em = $this->getContainer()->get('entityManager');

        $em->createEntity('Meeting', [
            'status' => 'Held',
        ]);
        $em->createEntity('Meeting', [
            'status' => 'Planned',
        ]);

        $fm = $this->getContainer()->get('formulaManager');

        $script = "record\\exists('Meeting', 'status', 'Held')";
        $result = $fm->run($script, $contact);
        $this->assertTrue($result);

        $script = "record\\exists('Meeting', 'status', 'Not Held')";
        $result = $fm->run($script, $contact);
        $this->assertFalse($result);

        $script = "record\\exists('Meeting', 'status', list('Held', 'Planned'))";
        $result = $fm->run($script, $contact);
        $this->assertTrue($result);

        $script = "record\\exists('Meeting', 'status', list('Not Held'))";
        $result = $fm->run($script, $contact);
        $this->assertFalse($result);
    }

    public function testRecordCount()
    {
        $em = $this->getContainer()->get('entityManager');

        $em->createEntity('Meeting', [
            'status' => 'Held',
        ]);
        $em->createEntity('Meeting', [
            'status' => 'Planned',
        ]);

        $fm = $this->getContainer()->get('formulaManager');

        $script = "record\\count('Meeting', 'status', 'Held')";
        $result = $fm->run($script, $contact);
        $this->assertEquals(1, $result);

        $script = "record\\count('Meeting', 'status', 'Not Held')";
        $result = $fm->run($script, $contact);
        $this->assertEquals(0, $result);

        $script = "record\\count('Meeting', 'status', list('Held', 'Planned'))";
        $result = $fm->run($script, $contact);
        $this->assertEquals(2, $result);

        $script = "record\\count('Meeting', 'status', list('Not Held'))";
        $result = $fm->run($script, $contact);
        $this->assertEquals(0, $result);


        $script = "record\\count('Meeting', 'planned')";
        $result = $fm->run($script, $contact);
        $this->assertEquals(1, $result);
    }

    public function testRecordFindOne()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $m1 =$em->createEntity('Meeting', [
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
        $result = $fm->run($script, $contact);
        $this->assertEquals($m1->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'desc')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m4->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'planned')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m2->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Planned')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m2->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Planned', 'assignedUserId=', '1')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m4->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Not Held')";
        $result = $fm->run($script, $contact);
        $this->assertEquals(null, $result);
    }

    public function testRecordFindRelatedOne()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $account = $em->createEntity('Account', [
            'name' => 'Test',
        ]);

        $m1 = $em->createEntity('Meeting', [
            'name' => '1',
            'status' => 'Held',
            'parentType' => 'Account',
            'parentId' => $account->id,
        ]);
        $m2 = $em->createEntity('Meeting', [
            'name' => '2',
            'status' => 'Planned',
            'parentType' => 'Account',
            'parentId' => $account->id,
        ]);
        $m3 = $em->createEntity('Meeting', [
            'name' => '3',
            'status' => 'Held',
            'parentType' => 'Account',
            'parentId' => $account->id,
        ]);
        $m4 = $em->createEntity('Meeting', [
            'name' => '4',
            'status' => 'Planned',
            'assignedUserId' => '1',
            'parentType' => 'Account',
            'parentId' => $account->id,
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

        $em->getRepository('Account')->relate($account, 'contacts', $c1);
        $em->getRepository('Account')->relate($account, 'contacts', $c2);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetings', 'name', 'asc')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m1->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetings', 'name', 'desc', 'planned')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m4->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetings', 'name', 'desc', 'status', 'Held')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m3->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetingsPrimary', 'name', 'asc')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($m1->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'contacts', 'name', 'asc')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($c1->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'contacts', 'name', 'asc', 'lastName', '2')";
        $result = $fm->run($script, $contact);
        $this->assertEquals($c2->id, $result);
    }

    public function testRecordAttribute()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $m1 = $em->createEntity('Meeting', [
            'name' => '1',
            'status' => 'Held',
        ]);

        $script = "record\\attribute('Meeting', '".$m1->id."', 'name')";
        $result = $fm->run($script, $contact);
        $this->assertEquals('1', $result);
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

        $this->assertTrue($result1 !== $result);
    }

    public function testEntityGetLinkColumn()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $lead = $em->createEntity('Lead', []);
        $targetList = $em->createEntity('TargetList', []);

        $em->getRepository('Lead')->relate($lead, 'targetLists', $targetList->id, [
            'optedOut' => true,
        ]);

        $script = "entity\\getLinkColumn('targetLists', '{$targetList->id}', 'optedOut')";

        $result = $fm->run($script, $lead);
        $this->assertTrue($result);


        $em->getRepository('Lead')->relate($lead, 'targetLists', $targetList->id, [
            'optedOut' => false,
        ]);

        $result = $fm->run($script, $lead);
        $this->assertFalse($result);
    }

    public function testRecordRelate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $o = $em->createEntity('Opportunity', [
            'name' => '1',
        ]);

        $script = "record\\relate('Account', '".$a->id."', 'opportunities', '".$o->id."')";
        $result = $fm->run($script, $contact);

        $this->assertTrue($result);
        $this->assertTrue($em->getRepository('Account')->isRelated($a, 'opportunities', $o));
    }

    public function testRecordUnrelate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $o = $em->createEntity('Opportunity', [
            'name' => '1',
        ]);

        $em->getRepository('Account')->relate($a, 'opportunities', $o);

        $script = "record\\unrelate('Account', '".$a->id."', 'opportunities', '".$o->id."')";
        $result = $fm->run($script, $contact);

        $this->assertTrue($result);
        $this->assertFalse($em->getRepository('Account')->isRelated($a, 'opportunities', $o));
    }

    public function testRecordRelationColumn()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $c = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);

        $em->getRepository('Account')->relate($a, 'contacts', $c->id, ['role' => 'test']);

        $script = "record\\relationColumn('Account', '{$a->id}', 'contacts', '{$c->id}', 'role')";
        $result = $fm->run($script);

        $this->assertEquals( 'test', $result);
    }

    public function testRecordUpdateRelationColumn()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $c = $em->createEntity('Contact', [
            'lastName' => '1',
        ]);

        $em->getRepository('Account')->relate($a, 'contacts', $c->id);

        $script = "record\\updateRelationColumn('Account', '{$a->id}', 'contacts', '{$c->id}', 'role', 'test')";
        $fm->run($script);

        $value = $em->getRepository('Account')->getRelationColumn($a, 'contacts', $c->id, 'role');

        $this->assertEquals('test', $value);
    }
}
