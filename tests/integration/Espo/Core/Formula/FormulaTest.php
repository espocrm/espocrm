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
        $em = $this->getContainer()->get('entityManager');

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
        $result = $fm->run($script);
        $this->assertEquals($m1->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'desc')";
        $result = $fm->run($script);
        $this->assertEquals($m4->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'planned')";
        $result = $fm->run($script);
        $this->assertEquals($m2->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Planned')";
        $result = $fm->run($script);
        $this->assertEquals($m2->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Planned', 'assignedUserId=', '1')";
        $result = $fm->run($script);
        $this->assertEquals($m4->id, $result);

        $script = "record\\findOne('Meeting', 'name', 'asc', 'status=', 'Not Held')";
        $result = $fm->run($script);
        $this->assertEquals(null, $result);
    }

    public function testRecordFindRelatedOne1()
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
        $result = $fm->run($script);
        $this->assertEquals($m1->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetings', 'name', 'desc', 'planned')";
        $result = $fm->run($script);
        $this->assertEquals($m4->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetings', 'name', 'desc', 'held')";
        $result = $fm->run($script);
        $this->assertEquals($m3->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetings', 'name', 'desc', 'status', 'Held')";
        $result = $fm->run($script);
        $this->assertEquals($m3->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'meetingsPrimary', 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertEquals($m1->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'contacts', 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertEquals($c1->id, $result);

        $script = "record\\findRelatedOne('Account', '".$account->id."', 'contacts', 'name', 'asc', 'lastName', '2')";
        $result = $fm->run($script);
        $this->assertEquals($c2->id, $result);
    }

    public function testRecordFindRelatedOne2()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
        ]);

        $o = $em->createEntity('Opportunity', [
            'accountId' => $a->id,
        ]);

        $script = "record\\findRelatedOne('Opportunity', '".$o->id."', 'account')";
        $result = $fm->run($script);
        $this->assertEquals($a->id, $result);
    }

    public function testRecordFindRelatedMany()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', []);

        $o1 = $em->createEntity('Opportunity', [
            'accountId' => $a->id,
            'stage' => 'Prospecting',
            'name' => '1',
        ]);
        $o2 = $em->createEntity('Opportunity', [
            'accountId' => $a->id,
            'stage' => 'Closed Won',
            'name' => '2',
        ]);
        $o3 = $em->createEntity('Opportunity', [
            'accountId' => $a->id,
            'stage' => 'Prospecting',
            'name' => '3',
        ]);

        $ow1 = $em->createEntity('Opportunity', []);


        $script = "record\\findRelatedMany('Account', '".$a->id."', 'opportunities', 2, null, null, 'open')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, in_array($o1->id, $result));
        $this->assertEquals(true, in_array($o3->id, $result));

        $script = "record\\findRelatedMany('Account', '".$a->id."', 'opportunities', 3)";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, in_array($o1->id, $result));
        $this->assertEquals(true, in_array($o2->id, $result));


        $script = "record\\findRelatedMany('Account', '".$a->id."', 'opportunities', 3, 'name', 'asc')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result));
        $this->assertEquals([$o1->id, $o2->id, $o3->id], $result);

        $script = "record\\findRelatedMany('Account', '".$a->id."', 'opportunities', 3, 'name', 'asc', 'stage=', 'Prospecting')";
        $result = $fm->run($script);
        $this->assertIsArray($result);
        $this->assertEquals([$o1->id, $o3->id], $result);
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
        $result = $fm->run($script);
        $this->assertEquals('1', $result);
    }

    public function testRecordCreateUpdate()
    {
        $em = $this->getContainer()->get('entityManager');
        $fm = $this->getContainer()->get('formulaManager');

        $script = "record\\create('Meeting', 'name', 'test', 'assignedUserId', '1')";
        $id = $fm->run($script);

        $this->assertIsString('string', $id);

        $m = $em->getEntity('Meeting', $id);

        $this->assertNotNull($m);
        $this->assertEquals('1', $m->get('assignedUserId'));

        $script = "record\\update('Meeting', '{$id}', 'name', 'test-chanhed', 'assignedUserId', '2')";
        $result = $fm->run($script);

        $this->assertTrue($result);

        $m = $em->getEntity('Meeting', $id);
        $this->assertEquals('2', $m->get('assignedUserId'));
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

    public function testRecordRelate1()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);
        $o = $em->createEntity('Opportunity', [
            'name' => '1',
        ]);

        $script = "record\\relate('Account', '".$a->id."', 'opportunities', list('".$o->id."'))";
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

        $this->assertEquals('test', $result);
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

    public function testExtAccountFindByEmailAddress()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

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
            'accountId' => $a2->id,
        ]);
        $c4 = $em->createEntity('Contact', [
            'lastName' => '4',
            'emailAddress' => 'c@brom.com',
        ]);

        $script = "ext\\account\\findByEmailAddress('b@hello-test.com')";
        $this->assertEquals($a3->id, $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('b@gmail.com')";
        $this->assertEquals(null, $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('c@gmail.com')";
        $this->assertEquals($a2->id, $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('b@brom.com')";
        $this->assertEquals($a4->id, $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('c@brom.com')";
        $this->assertEquals($a4->id, $fm->run($script));

        $script = "ext\\account\\findByEmailAddress('')";
        $this->assertEquals(null, $fm->run($script));
    }

    public function testExtEmailApplyTemplate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

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
            'attachmentsIds' => [$attachment2->id],
        ]);

        $email = $em->createEntity('Email', [
            'to' => 'test@tester.com',
            'status' => 'Draft',
            'attachmentsIds' => [$attachment1->id],
        ]);

        $script = "ext\\email\\applyTemplate('{$email->id}', '{$emailTemplate->id}', 'Account', '{$a->id}')";
        $fm->run($script);

        $email = $em->getEntity('Email', $email->id);

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
            'parentId' => $case->id,
            'parentType' => 'Case',
        ]);
        $emailTemplate = $em->createEntity('EmailTemplate', [
            'name' => '1',
            'subject' => 'Test',
            'body' => 'Test {Person.name} Hello, {Case.name}',
            'isHtml' => false,
        ]);

        $script = "ext\\email\\applyTemplate('{$email->id}', '{$emailTemplate->id}')";
        $fm->run($script);

        $email = $em->getEntity('Email', $email->id);

        $this->assertEquals('Test', $email->get('name'));
        $this->assertEquals('Test Contact 1 Hello, Case 1', $email->get('body'));
    }

    public function testExtPdfGenerate()
    {
        $fm = $this->getContainer()->get('formulaManager');
        $em = $this->getContainer()->get('entityManager');

        $a = $em->createEntity('Account', [
            'name' => '1',
        ]);

        $template = $em->createEntity('Template', [
            'body' => 'Test {{name}} hello',
            'entityType' => 'Account',
        ]);

        $script = "ext\\pdf\\generate('Account', '{$a->id}', '{$template->id}', 'test')";
        $id = $fm->run($script);

        $this->assertIsString($id);

        $attachment = $em->getEntity('Attachment', $id);

        $this->assertNotNull($attachment);
        $this->assertEquals('test.pdf', $attachment->get('name'));
        $this->assertTrue(file_exists('data/upload/' . $attachment->id));


        $script = "ext\\pdf\\generate('Account', '{$a->id}', '{$template->id}', 'test.pdf')";
        $id = $fm->run($script);

        $attachment = $em->getEntity('Attachment', $id);

        $this->assertEquals('test.pdf', $attachment->get('name'));


        $script = "ext\\pdf\\generate('Account', '{$a->id}', '{$template->id}')";
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
        $this->assertEquals($id, $user->id);
    }
}
