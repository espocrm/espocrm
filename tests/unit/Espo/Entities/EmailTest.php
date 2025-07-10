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

namespace tests\unit\Espo\Entities;

use Espo\Core\Repositories\Database;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\RDBRepository;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    /** @var Email */
    private $email;

    /** @var RDBRepository */
    private $attachmentRepository;

    // TODO defs test helper
    protected $defs = [
    'attributes' =>
    [
      'id' =>
      [
        'type' => 'id',
        'dbType' => 'varchar',
        'len' => '24',
      ],
      'name' =>
      [
        'type' => 'varchar',
        'len' => 255,
      ],
      'subject' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ],
      'fromName' =>
      [
        'type' => 'varchar',
        'len' => 255,
      ],
      'from' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ],
      'to' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ],
      'cc' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ],
      'bcc' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ],
      'replyTo' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
      ],
      'emailAddress' =>
      [
        'type' => 'base',
        'notStorable' => true,
      ],
      'bodyPlain' =>
      [
        'type' => 'text',
      ],
      'body' =>
      [
        'type' => 'text',
      ],
      'isHtml' =>
      [
        'type' => 'bool',
        'default' => true,
      ],
      'status' =>
      [
        'type' => 'varchar',
        'default' => 'Archived',
        'len' => 255,
      ],
      'parent' =>
      [
        'type' => 'linkParent',
        'notStorable' => true,
      ],
      'dateSent' =>
      [
        'type' => 'datetime',
        'notNull' => false,
      ],
      'createdAt' =>
      [
        'type' => 'datetime',
        'notNull' => false,
      ],
      'modifiedAt' =>
      [
        'type' => 'datetime',
        'notNull' => false,
      ],
      'deleted' =>
      [
        'type' => 'bool',
        'default' => false,
      ],
      'bccEmailAddressesIds' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'bccEmailAddressesNames' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'ccEmailAddressesIds' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'ccEmailAddressesNames' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'toEmailAddressesIds' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'toEmailAddressesNames' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'fromEmailAddressName' =>
      [
        'type' => 'foreign',
        'relation' => 'fromEmailAddress',
        'foreign' => 'name',
      ],
      'fromEmailAddressId' =>
      [
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ],
      'attachmentsIds' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'attachmentsNames' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'assignedUserName' =>
      [
        'type' => 'foreign',
        'relation' => 'assignedUser',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
      ],
      'assignedUserId' =>
      [
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ],
      'modifiedByName' =>
      [
        'type' => 'foreign',
        'relation' => 'modifiedBy',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
      ],
      'modifiedById' =>
      [
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ],
      'createdByName' =>
      [
        'type' => 'foreign',
        'relation' => 'createdBy',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
      ],
      'createdById' =>
      [
        'type' => 'foreignId',
        'index' => true,
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ],
      'parentId' =>
      [
        'type' => 'foreignId',
        'index' => 'parent',
        'dbType' => 'varchar',
        'len' => '24',
        'notNull' => false,
      ],
      'parentType' =>
      [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'dbType' => 'varchar',
        'len' => 255,
      ],
      'parentName' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'teamsIds' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'teamsNames' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
    ],
    'relations' =>
    [
      'bccEmailAddresses' =>
      [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' =>
        [
          0 => 'emailId',
          1 => 'emailAddressId',
        ],
        'relationName' => 'EmailEmailAddress',
        'conditions' =>
        [
          'addressType' => 'bcc',
        ],
        'additionalColumns' =>
        [
          'addressType' =>
          [
            'type' => 'varchar',
            'len' => '4',
          ],
        ],
      ],
      'ccEmailAddresses' =>
      [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' =>
        [
          0 => 'emailId',
          1 => 'emailAddressId',
        ],
        'relationName' => 'EmailEmailAddress',
        'conditions' =>
        [
          'addressType' => 'cc',
        ],
        'additionalColumns' =>
        [
          'addressType' =>
          [
            'type' => 'varchar',
            'len' => '4',
          ],
        ],
      ],
      'toEmailAddresses' =>
      [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' =>
        [
          0 => 'emailId',
          1 => 'emailAddressId',
        ],
        'relationName' => 'EmailEmailAddress',
        'conditions' =>
        [
          'addressType' => 'to',
        ],
        'additionalColumns' =>
        [
          'addressType' =>
          [
            'type' => 'varchar',
            'len' => '4',
          ],
        ],
      ],
      'fromEmailAddress' =>
      [
        'type' => 'belongsTo',
        'entity' => 'EmailAddress',
        'key' => 'fromEmailAddressId',
        'foreignKey' => 'id',
      ],
      'attachments' =>
      [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
      ],
      'teams' =>
      [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' =>
        [
          0 => 'entity_id',
          1 => 'team_id',
        ],
        'conditions' =>
        [
          'entityType' => 'Email',
        ],
        'additionalColumns' =>
        [
          'entityType' =>
          [
            'type' => 'varchar',
            'len' => 100,
          ],
        ],
      ],
      'assignedUser' =>
      [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
      ],
      'modifiedBy' =>
      [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
      ],
      'createdBy' =>
      [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
      ],
    ],
    ];


    protected function setUp() : void
    {
        $entityManager = $this->createMock(EntityManager::class);

        $this->attachmentRepository = $this->createMock(RDBRepository::class);

        $entityManager
            ->expects($this->any())
            ->method('getRDBRepositoryByClass')
            ->willReturnMap([
                [Attachment::class, $this->attachmentRepository],
            ]);

        $repository = $this->createMock(Database::class);

        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->email = new Email(
            entityType: 'Email',
            defs: $this->defs,
            entityManager: $entityManager,
        );
    }

    public function testGetInlineAttachments(): void
    {
        $this->email->set('body', 'test <img src="?entryPoint=attachment&amp;id=Id01">');

        $this->attachmentRepository
            ->expects($this->once())
            ->method('getById')
            ->with('Id01');

        $this->email->getInlineAttachmentList();
    }

    public function testGetBodyForSending(): void
    {
        $attachment = $this->createMock(Attachment::class);

        $attachment
            ->expects($this->any())
            ->method('getId')
            ->willReturn('Id01');

        $this->attachmentRepository
            ->expects($this->once())
            ->method('getById')
            ->with('Id01')
            ->willReturn($attachment);

        $body = 'test <img src="?entryPoint=attachment&amp;id=Id01">';

        $this->email->setBody($body);
        $this->email->setIsHtml();

        $body = $this->email->getBodyForSending();

        $this->assertEquals('test <img src="cid:Id01@espo">', $body);
    }

    public function testBodyPlain(): void
    {
        $this->email->setBody('<br />&nbsp;&amp;');
        $this->email->setIsHtml();
        $bodyPlain = $this->email->getBodyPlain();

        $this->assertEquals("  \r\n &", $bodyPlain);
    }

    public function testBodyPlainWithoutQuotePart(): void
    {
        $body = "Test\r\nHello\r\n\r\nOn {date} {one} wrote:\r\n> Test\n> Test\r\n>> Test\r\n";

        $this->email->set('bodyPlain', $body);

        $expected = "Test\r\nHello";

        $this->assertEquals($expected, $this->email->getBodyPlainWithoutReplyPart());
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
