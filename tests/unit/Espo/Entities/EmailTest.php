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

namespace tests\unit\Espo\Entities;

use Espo\Entities\Email;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Email
     */
    private $email;

    // TODO defs test helper
    protected $defs = array(
    'fields' =>
    array (
      'id' =>
      array (
        'type' => 'id',
        'dbType' => 'varchar',
        'len' => '24',
      ),
      'name' =>
      array (
        'type' => 'varchar',
        'len' => 255,
      ),
      'subject' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ),
      'fromName' =>
      array (
        'type' => 'varchar',
        'len' => 255,
      ),
      'from' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ),
      'to' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ),
      'cc' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ),
      'bcc' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ),
      'replyTo' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ),
      'emailAddress' =>
      array (
        'type' => 'base',
        'notStorable' => true,
      ),
      'bodyPlain' =>
      array (
        'type' => 'text',
      ),
      'body' =>
      array (
        'type' => 'text',
      ),
      'isHtml' =>
      array (
        'type' => 'bool',
        'default' => true,
      ),
      'status' =>
      array (
        'type' => 'varchar',
        'default' => 'Archived',
        'len' => 255,
      ),
      'parent' =>
      array (
        'type' => 'linkParent',
        'notStorable' => true,
      ),
      'dateSent' =>
      array (
        'type' => 'datetime',
        'notNull' => false,
      ),
      'createdAt' =>
      array (
        'type' => 'datetime',
        'notNull' => false,
      ),
      'modifiedAt' =>
      array (
        'type' => 'datetime',
        'notNull' => false,
      ),
      'deleted' =>
      array (
        'type' => 'bool',
        'default' => false,
      ),
      'bccEmailAddressesIds' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'bccEmailAddressesNames' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'ccEmailAddressesIds' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'ccEmailAddressesNames' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'toEmailAddressesIds' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'toEmailAddressesNames' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'fromEmailAddressName' =>
      array (
        'type' => 'foreign',
        'relation' => 'fromEmailAddress',
        'foreign' => 'name',
      ),
      'fromEmailAddressId' =>
      array (
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ),
      'attachmentsIds' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'attachmentsNames' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'assignedUserName' =>
      array (
        'type' => 'foreign',
        'relation' => 'assignedUser',
        'foreign' =>
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
      ),
      'assignedUserId' =>
      array (
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ),
      'modifiedByName' =>
      array (
        'type' => 'foreign',
        'relation' => 'modifiedBy',
        'foreign' =>
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
      ),
      'modifiedById' =>
      array (
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ),
      'createdByName' =>
      array (
        'type' => 'foreign',
        'relation' => 'createdBy',
        'foreign' =>
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
      ),
      'createdById' =>
      array (
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ),
      'parentId' =>
      array (
        'type' => 'foreignId',
        'index' => 'parent',
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ),
      'parentType' =>
      array (
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'dbType' => 'varchar',
        'len' => 255,
      ),
      'parentName' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'teamsIds' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'teamsNames' =>
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
    ),
    'relations' =>
    array (
      'bccEmailAddresses' =>
      array (
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' =>
        array (
          0 => 'emailId',
          1 => 'emailAddressId',
        ),
        'relationName' => 'EmailEmailAddress',
        'conditions' =>
        array (
          'addressType' => 'bcc',
        ),
        'additionalColumns' =>
        array (
          'addressType' =>
          array (
            'type' => 'varchar',
            'len' => '4',
          ),
        ),
      ),
      'ccEmailAddresses' =>
      array (
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' =>
        array (
          0 => 'emailId',
          1 => 'emailAddressId',
        ),
        'relationName' => 'EmailEmailAddress',
        'conditions' =>
        array (
          'addressType' => 'cc',
        ),
        'additionalColumns' =>
        array (
          'addressType' =>
          array (
            'type' => 'varchar',
            'len' => '4',
          ),
        ),
      ),
      'toEmailAddresses' =>
      array (
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' =>
        array (
          0 => 'emailId',
          1 => 'emailAddressId',
        ),
        'relationName' => 'EmailEmailAddress',
        'conditions' =>
        array (
          'addressType' => 'to',
        ),
        'additionalColumns' =>
        array (
          'addressType' =>
          array (
            'type' => 'varchar',
            'len' => '4',
          ),
        ),
      ),
      'fromEmailAddress' =>
      array (
        'type' => 'belongsTo',
        'entity' => 'EmailAddress',
        'key' => 'fromEmailAddressId',
        'foreignKey' => 'id',
      ),
      'attachments' =>
      array (
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
      ),
      'teams' =>
      array (
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' =>
        array (
          0 => 'entity_id',
          1 => 'team_id',
        ),
        'conditions' =>
        array (
          'entityType' => 'Email',
        ),
        'additionalColumns' =>
        array (
          'entityType' =>
          array (
            'type' => 'varchar',
            'len' => 100,
          ),
        ),
      ),
      'assignedUser' =>
      array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
      ),
      'modifiedBy' =>
      array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
      ),
      'createdBy' =>
      array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
      ),
    ),
  );


    protected function setUp() : void
    {
        $this->entityManager = $this->getMockBuilder('\Espo\Core\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        $this->repository =
          $this->getMockBuilder('Espo\Core\ORM\Repositories\Database')->disableOriginalConstructor()->getMock();

        $this->entityManager->expects($this->any())
                            ->method('getRepository')
                            ->will($this->returnValue($this->repository));

        $this->email = new Email('Email', $this->defs, $this->entityManager);
    }

    protected function tearDown() : void
    {
        $this->entityManager = null;
        $this->repository = null;
        $this->email = null;
    }


    function testGetInlineAttachments()
    {
        $this->email->set('body', 'test <img src="?entryPoint=attachment&amp;id=Id01">');

        $this->entityManager->expects($this->exactly(1))
                            ->method('getEntity')
                            ->with('Attachment', 'Id01');

        $this->email->getInlineAttachmentList();
    }

    function testGetBodyForSending()
    {
        $attachment =
            $this->getMockBuilder('Espo\Entities\Attachment')->disableOriginalConstructor()->getMock();

        $attachment
            ->method('getId')
            ->willReturn('Id01');

        $this->email->set('body', 'test <img src="?entryPoint=attachment&amp;id=Id01">');

        $this->entityManager->expects($this->any())
                            ->method('getEntity')
                            ->with('Attachment', 'Id01')
                            ->will($this->returnValue($attachment));

        $body = $this->email->getBodyForSending();
        $this->assertEquals('test <img src="cid:Id01">', $body);
    }

    function testBodyPlain()
    {
        $this->email->set('body', '<br />&nbsp;&amp;');
        $bodyPlain = $this->email->getBodyPlain();
        $this->assertEquals("\r\n &", $bodyPlain);
    }

    public function testSubjectBody(): void
    {
        $email = $this->email;

        $email->setSubject('1');
        $email->setBody('2');
        $email->setIsHtml();

        $this->assertEquals('1', $email->getSubject());
        $this->assertEquals('2', $email->getBody());
        $this->assertEquals(true, $email->isHtml());
    }

    public function testPlain(): void
    {
        $email = $this->email;

        $email->setIsPlain();

        $this->assertEquals(false, $email->isHtml());
    }

    public function testAddressList(): void
    {
        $email = $this->email;

        $email->addToAddress('test1@test.com');
        $email->addToAddress('test2@test.com');

        $this->assertEquals(
            ['test1@test.com', 'test2@test.com'],
            $email->getToAddressList()
        );

        $email->addCcAddress('test3@test.com');
        $email->addCcAddress('test4@test.com');

        $this->assertEquals(
            ['test3@test.com', 'test4@test.com'],
            $email->getCcAddressList()
        );

        $email->addBccAddress('test5@test.com');
        $email->addBccAddress('test6@test.com');

        $this->assertEquals(
            ['test5@test.com', 'test6@test.com'],
            $email->getBccAddressList()
        );

        $email->addReplyToAddress('test7@test.com');
        $email->addReplyToAddress('test8@test.com');

        $this->assertEquals(
            ['test7@test.com', 'test8@test.com'],
            $email->getReplyToAddressList()
        );
    }
}
