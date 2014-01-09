<?php

namespace tests\Espo\Core\Utils\Database;

require_once('tests/testBootstrap.php');


use Espo\Utils as Utils;

class RelationsTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	private $app;

	public function __construct()
	{
		$this->app = $GLOBALS['app'];
	}


    protected function setUp()
    {
        $this->object = new \Espo\Core\Utils\Database\Relations();
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


	public function invokeMethod($methodName, array $parameters = array())
	{
	    $reflection = new \ReflectionClass(get_class($this->object));
	    $method = $reflection->getMethod($methodName);
	    $method->setAccessible(true);

	    return $method->invokeArgs($this->object, $parameters);
	}
	//$this->invokeMethod('cryptPassword', array('passwordToCrypt'));



	function testGetSortEntities()
	{
    	$input = array(
			'entity1' => 'ContactTest',
			'entity2' => 'CallTest',
		);

		$result = array(
			0 => 'callTest',
			1 => 'contactTest',
		);

		$this->assertEquals($result, $this->invokeMethod('getSortEntities', array($input['entity1'], $input['entity2'])));
	}


	function testGetJoinTable()
	{
    	$input = array(
			'entity1' => 'ContactTest',
			'entity2' => 'CallTest',
		);

		$result = 'callTestContactTest';

		$this->assertEquals( $result, $this->invokeMethod('getJoinTable', array($input['entity1'], $input['entity2'])) );
	}


	function testManyMany()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'Call',
			  'link' =>
			  array (
			    'name' => 'contacts',
			    'params' =>
			    array (
			      'type' => 'hasMany',
			      'entity' => 'Contact',
			      'foreign' => 'calls',
			    ),
			  ),
			  'targetEntity' => 'Contact',
			),


			'foreignParams' => array (
			  'entityName' => 'Contact',
			  'link' =>
			  array (
			    'name' => 'calls',
			    'params' =>
			    array (
			      'type' => 'hasMany',
			      'entity' => 'Call',
			      'foreign' => 'contacts',
			    ),
			  ),
			  'targetEntity' => 'Call',
			),
		);

		$result = array(
		'Call' =>
		  array (
		    'relations' =>
		    array (
		      'contacts' =>
		      array (
		        'type' => 'manyMany',
		        'entity' => 'Contact',
		        'relationName' => 'callContact',
		        'key' => 'id',
		        'foreignKey' => 'id',
		        'midKeys' =>
		        array (
		          'callId',
		          'contactId',
		        ),
		      ),
		    ),
		  ),
		);


		$this->assertEquals( $result, $this->invokeMethod('manyMany', array($input['params'], $input['foreignParams'])) );

		//test reverse: result should be an empty array
		$result = array();
		$this->assertEquals( $result, $this->invokeMethod('manyMany', array($input['foreignParams'], $input['params'])) );
	}


	function testHasMany()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'Account',
			  'link' =>
			  array (
			    'name' => 'contacts',
			    'params' =>
			    array (
			      'type' => 'hasMany',
			      'entity' => 'Contact',
			      'foreign' => 'account',
			    ),
			  ),
			  'targetEntity' => 'Contact',
			),

			'foreignParams' => array (
			  'entityName' => 'Contact',
			  'link' =>
			  array (
			    'name' => 'account',
			    'params' =>
			    array (
			      'type' => 'belongsTo',
			      'entity' => 'Account',
			    ),
			  ),
			  'targetEntity' => 'Account',
			),
		);

		$result = array (
		  'Account' =>
		  array (
		    'relations' =>
		    array (
		      'contacts' =>
		      array (
		        'type' => 'hasMany',
		        'entity' => 'Contact',
		        'foreignKey' => 'accountId',
		      ),
		    ),
		  ),
		);


		$this->assertEquals( $result, $this->invokeMethod('hasMany', array($input['params'], $input['foreignParams'])) );
	}


	function testBelongsTo()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'Attachment',
			  'link' =>
			  array (
			    'name' => 'createdBy',
			    'params' =>
			    array (
			      'type' => 'belongsTo',
			      'entity' => 'User',
			    ),
			  ),
			  'targetEntity' => 'User',
			),

			'foreignParams' => array (
			  'entityName' => 'User',
			  'link' => false,
			  'targetEntity' => 'Attachment',
			),
		);

		$result = array (
		  'Attachment' =>
		  array (
		    'fields' =>
		    array (
		      'createdByName' =>
		      array (
		        'type' => 'foreign',
		        'relation' => 'createdBy',
		        'notStorable' => true,
		      ),
		      'createdById' =>
		      array (
		        'type' => 'foreignId',
		      ),
		    ),
		    'relations' =>
		    array (
		      'createdBy' =>
		      array (
		        'type' => 'belongsTo',
		        'entity' => 'User',
		        'key' => 'createdById',
		        'foreignKey' => 'id',
		      ),
		    ),
		  ),
		);


		$this->assertEquals( $result, $this->invokeMethod('belongsTo', array($input['params'], $input['foreignParams'])) );
	}


	function testHasChildren()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'Post',
			  'link' =>
			  array (
			    'name' => 'notes',
			    'params' =>
			    array (
			    	"type" => "hasChildren",
		            "entity" => "Post",
		            "foreign" => "parent",
			    ),
			  ),
			  'targetEntity' => 'Note',
			),

			'foreignParams' => array (
			  'entityName' => 'Note',
			  'link' => array (
			    'name' => 'parent',
			    'params' =>
			    array (
			    	"type" => "belongsToParent",
		            "entities" => array("Post", "Account", "Case"),
		            "foreign" => "notes"
			    ),
			  ),
			  'targetEntity' => 'Post',
			),
		);

		$result = array (
		  'Post' =>
		  array (
		    'relations' =>
		    array (
		      'notes' =>
		      array (
		        'type' => 'hasChildren',
				'entity' => 'Note',
				'foreignKey' => 'parentId',
				'foreignType' => 'parentType',
		      ),
		    ),
		  ),

		  /*'Note' => array(
          	'fields' => array(
				'parentId' => array(
					'type' => 'foreignId',
				),
				'parentType' => array(
					'type' => 'foreignType',
				),
				'parentName' => array(
					'type' => 'varchar',
					'notStorable' => true,
				),
			),
		  ),*/
		);


		$this->assertEquals( $result, $this->invokeMethod('hasChildren', array($input['params'], $input['foreignParams'])) );
	}


	function testBelongsToParent_Single()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'Note',
			  'link' =>
			  array (
			    'name' => 'parent',
			    'params' =>
			    array (
			      'type' => 'belongsToParent',
			      'foreign' => 'notes',
			    ),
			  ),
			  'targetEntity' => 'Note',
			),

			'foreignParams' => array (
			  'entityName' => 'Note',
			  'link' =>
			  array (
			    'name' => 'attachments',
			    'params' =>
			    array (
			      'type' => 'hasChildren',
			      'entity' => 'Attachment',
			      'foreign' => 'parent',
			    ),
			  ),
			  'targetEntity' => 'Note',
			),
		);

		$result = array (
		  'Note' =>
		  array (
		    'fields' =>
		    array (
		      'parentId' =>
		      array (
		        'type' => 'foreignId',
		      ),
		      'parentType' =>
		      array (
		        'type' => 'foreignType',
		      ),
		      'parentName' =>
		      array (
		        'type' => 'varchar',
		        'notStorable' => true,
		      ),
		    ),
		  ),
		);

		$this->assertEquals( $result, $this->invokeMethod('belongsToParent', array($input['params'], $input['foreignParams'])) );
	}


	function testBelongsToParent()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'Call',
			  'link' =>
			  array (
			    'name' => 'parent',
			    'params' =>
			    array (
			      'type' => 'belongsToParent',
			      'entities' =>
			      array (
			        'Account',
			        'Opportunity',
			        'Case',
			      ),
			      'foreign' => 'calls',
			    ),
			  ),
			  'targetEntity' => 'Call',
			),

			'foreignParams' => array (
			  'entityName' => 'Call',
			  'link' => false,
			  'targetEntity' => 'Call',
			),
		);

		$result = array (
		  'Account' =>
		  array (
		    'fields' =>
		    array (
		      'parentId' =>
		      array (
		        'type' => 'foreignId',
		      ),
		      'parentType' =>
		      array (
		        'type' => 'foreignType',
		      ),
		      'parentName' =>
		      array (
		        'type' => 'varchar',
		        'notStorable' => true,
		      ),
		    ),
		  ),
		  'Opportunity' =>
		  array (
		    'fields' =>
		    array (
		      'parentId' =>
		      array (
		        'type' => 'foreignId',
		      ),
		      'parentType' =>
		      array (
		        'type' => 'foreignType',
		      ),
		      'parentName' =>
		      array (
		        'type' => 'varchar',
		        'notStorable' => true,
		      ),
		    ),
		  ),
		  'Case' =>
		  array (
		    'fields' =>
		    array (
		      'parentId' =>
		      array (
		        'type' => 'foreignId',
		      ),
		      'parentType' =>
		      array (
		        'type' => 'foreignType',
		      ),
		      'parentName' =>
		      array (
		        'type' => 'varchar',
		        'notStorable' => true,
		      ),
		    ),
		  ),
		);

		$this->assertEquals( $result, $this->invokeMethod('belongsToParent', array($input['params'], $input['foreignParams'])) );
	}


	function testEntityEmailAddress()
	{
		$input = array(
			'params' => array (
			  'entityName' => 'User',
			  'link' =>
			  array (
			    'name' => 'email',
			  ),
			  'targetEntity' => 'User',
			),

			'foreignParams' => array (
			  'entityName' => 'User',
			  'link' =>
			  array (
			  ),
			  'targetEntity' => 'User',
			),
		);

		$result = array (
		  'User' =>
		  array (
		    'relations' =>
		    array (
		      'email' =>
		      array (
		        'type' => 'manyMany',
		        'entity' => 'EmailAddress',
		        'relationName' => 'entityEmailAddress',
		        'midKeys' =>
		        array (
		          0 => 'entity_id',
		          1 => 'email_address_id',
		        ),
		        'conditions' =>
		        array (
		          'entityType' => 'User',
		        ),
		        'additionalColumns' =>
		        array (
		          'primary' =>
		          array (
		            'type' => 'bool',
		            'default' => false,
		          ),
		        ),
		      ),
		    ),
		  ),
		);

		//todo: move to RelationManager file
		$this->assertEquals( $result, $this->invokeMethod('entityEmailAddress', array($input['params'], $input['foreignParams'])) );
	}






}