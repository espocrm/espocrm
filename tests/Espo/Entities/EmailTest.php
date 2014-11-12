<?php

namespace tests\Espo\Entities;

use tests\ReflectionHelper;

use \Espo\Entities\Email;

class EmailTest extends \PHPUnit_Framework_TestCase
{
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


    protected function setUp()
    {           
        $this->entityManager = $this->getMockBuilder('\Espo\Core\ORM\EntityManager')->disableOriginalConstructor()->getMock();
       
        $this->repository = $this->getMockBuilder('\Espo\Core\ORM\Repositories\RDB')->disableOriginalConstructor()->getMock();
       
        $this->entityManager->expects($this->any())
                            ->method('getRepository')
                            ->will($this->returnValue($this->repository));


        
        $this->email = new Email($this->defs, $this->entityManager);
        
    }

    protected function tearDown()
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
                         
       
        $this->email->getInlineAttachments();
    } 
    
    function testGetBodyForSending()
    {
        $attachment = new \stdClass();
        $attachment->id = 'Id01';
        
        $this->email->set('body', 'test <img src="?entryPoint=attachment&amp;id=Id01">');
       
        $this->entityManager->expects($this->any())
                            ->method('getEntity')
                            ->with('Attachment', 'Id01')
                            ->will($this->returnValue($attachment));                         
       
        $body = $this->email->getBodyForSending();        
        $this->assertEquals($body, 'test <img src="cid:Id01">');
    }

}


