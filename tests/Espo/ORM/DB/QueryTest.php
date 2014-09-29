<?php

use Espo\ORM\DB\Query;
use Espo\ORM\EntityFactory;

use Espo\Entities\Post;
use Espo\Entities\Comment;
use Espo\Entities\Tag;
use Espo\Entities\Note;

require_once 'tests/testData/DB/Entities.php';
require_once 'tests/testData/DB/MockPDO.php';
require_once 'tests/testData/DB/MockDBResult.php';

class QueryTest extends PHPUnit_Framework_TestCase
{
	protected $query;
	
	protected $pdo;

	protected $entityFactory;

	protected function setUp()
	{
		$this->pdo = $this->getMock('MockPDO');
		$this->pdo
				->expects($this->any())
				->method('quote')
				->will($this->returnCallback(function() {
					$args = func_get_args();
					return "'" . $args[0] . "'";
				}));
				

		$this->entityFactory = $this->getMockBuilder('\\Espo\\ORM\\EntityFactory')->disableOriginalConstructor()->getMock();
		$this->entityFactory->expects($this->any())
		                    ->method('create')
		                    ->will($this->returnCallback(function() {
                            	$args = func_get_args();
		                    	$className = "\\Espo\\Entities\\" . $args[0];
		                 	 	return new $className();
			                }));

		$this->query = new Query($this->pdo, $this->entityFactory);
		
		$this->post = new \Espo\Entities\Post();
		$this->comment = new \Espo\Entities\Comment();
		$this->tag = new \Espo\Entities\Tag();
		$this->note = new \Espo\Entities\Note();

		$this->contact = new \Espo\Entities\Contact();
		$this->account = new \Espo\Entities\Account();
	}

	protected function tearDown()
	{
		unset($this->query);
		unset($this->pdo);
		unset($this->post);
		unset($this->tag);
		unset($this->note);
		unset($this->contact);
		unset($this->account);
	}
	
	public function testSelectAllColumns()
	{
		$sql = $this->query->createSelectQuery('Account', array(
			'orderBy' => 'name',
			'order' => 'ASC',
			'offset' => 10,
			'limit' => 20
		));
		
		$expectedSql = 
			"SELECT account.id AS `id`, account.name AS `name`, account.deleted AS `deleted` FROM `account` " .
			"WHERE account.deleted = '0' ORDER BY account.name ASC LIMIT 10, 20";
		
		$this->assertEquals($expectedSql, $sql);
	}
	
	public function testSelectWithBelongsToJoin()
	{
		$sql = $this->query->createSelectQuery('Comment', array(

		));
		
		$expectedSql = 
			"SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `postName`, comment.name AS `name`, comment.deleted AS `deleted` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0'";
		
		$this->assertEquals($expectedSql, $sql);
	}
	
	public function testSelectWithSpecifiedColumns()
	{
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('id', 'name')
		));		
		$expectedSql = 
			"SELECT comment.id AS `id`, comment.name AS `name` FROM `comment` " .
			"WHERE comment.deleted = '0'";
		
		$this->assertEquals($expectedSql, $sql);
		
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('id', 'name', 'postName')
		));		
		$expectedSql = 
			"SELECT comment.id AS `id`, comment.name AS `name`, post.name AS `postName` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0'";
		
		$this->assertEquals($expectedSql, $sql);
		
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('id', 'name', 'postName'),
			'leftJoins' => array('post')
		));		
		$expectedSql = 
			"SELECT comment.id AS `id`, comment.name AS `name`, post.name AS `postName` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0'";
		
		$this->assertEquals($expectedSql, $sql);
		
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('id', 'name'),
			'leftJoins' => array('post')
		));		
		$expectedSql = 
			"SELECT comment.id AS `id`, comment.name AS `name` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0'";
		
		$this->assertEquals($expectedSql, $sql);
	}
	
	public function testWithSpecifiedFunction()
	{
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('id', 'postId', 'post.name', 'COUNT:id'),
			'leftJoins' => array('post'),
			'groupBy' => array('postId', 'post.name')
		));
		$expectedSql = 
			"SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `post.name`, COUNT(id) AS `COUNT:id` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0' " .
			"GROUP BY comment.post_id, post.name";		
		$this->assertEquals($expectedSql, $sql);
		
		
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('id', 'COUNT:id', 'YEAR:post.createdAt'),
			'leftJoins' => array('post'),
			'groupBy' => array('YEAR:post.createdAt')
		));
		$expectedSql = 
			"SELECT comment.id AS `id`, COUNT(id) AS `COUNT:id`, YEAR(post.created_at) AS `YEAR:post.createdAt` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0' " .
			"GROUP BY YEAR(post.created_at)";		
		$this->assertEquals($expectedSql, $sql);
	}
	
	public function testOrderBy()
	{
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('COUNT:id', 'YEAR:post.createdAt'),
			'leftJoins' => array('post'),
			'groupBy' => array('YEAR:post.createdAt'),
			'orderBy' => 2
		));
		$expectedSql = 
			"SELECT COUNT(id) AS `COUNT:id`, YEAR(post.created_at) AS `YEAR:post.createdAt` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0' " .
			"GROUP BY YEAR(post.created_at) ".
			"ORDER BY 2 ASC";
		$this->assertEquals($expectedSql, $sql);
		
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('COUNT:id', 'post.name'),
			'leftJoins' => array('post'),
			'groupBy' => array('post.name'),
			'orderBy' => 'LIST:post.name:Test,Hello',
		));
		
		$expectedSql = 
			"SELECT COUNT(id) AS `COUNT:id`, post.name AS `post.name` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE comment.deleted = '0' " .
			"GROUP BY post.name ".
			"ORDER BY FIELD(post.name, 'Test', 'Hello')";
		$this->assertEquals($expectedSql, $sql);
	}
	
	public function testForeign()
	{
		$sql = $this->query->createSelectQuery('Comment', array(
			'select' => array('COUNT:comment.id', 'postId', 'postName'),
			'leftJoins' => array('post'),
			'groupBy' => array('postId'),
			'whereClause' => array(
				'post.createdById' => 'id_1'
			),
		));
		$expectedSql = 
			"SELECT COUNT(comment.id) AS `COUNT:comment.id`, comment.post_id AS `postId`, post.name AS `postName` FROM `comment` " .
			"LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
			"WHERE post.created_by_id = 'id_1' AND comment.deleted = '0' " .
			"GROUP BY comment.post_id";		
		$this->assertEquals($expectedSql, $sql);		
	}

}


