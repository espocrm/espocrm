<?php

use Espo\ORM\DB\MysqlMapper;
use Espo\ORM\EntityFactory;

use Espo\Entities\Post;
use Espo\Entities\Comment;
use Espo\Entities\Tag;
use Espo\Entities\Note;

require_once 'tests/testData/DB/Entities.php';
require_once 'tests/testData/DB/MockPDO.php';
require_once 'tests/testData/DB/MockDBResult.php';

class DBMapperTest extends PHPUnit_Framework_TestCase
{
	protected $db;
	protected $pdo;	
	protected $post;
	protected $note;
	protected $comment;	
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
				
		$this->entityFactory = $this->getMock('\\Espo\\ORM\\EntityFactory');
		$this->entityFactory->expects($this->any())
		                    ->method('create')
		                    ->will($this->returnCallback(function() {
                            	$args = func_get_args();					
		                    	$className = "\\Espo\\Entities\\" . $args[0];
		                 	 	return new $className();
			                }));
						
		$this->db = new MysqlMapper($this->pdo, $this->entityFactory);	
		$this->post = new \Espo\Entities\Post();
		$this->comment = new \Espo\Entities\Comment();
		$this->tag = new \Espo\Entities\Tag();
		$this->note = new \Espo\Entities\Note();		

	}
	
	protected function tearDown()
	{
		unset($this->pdo, $this->db, $this->post, $this->comment);
	}
	
	protected function mockQuery($query, $return, $any = false)
	{
		if ($any) {
			$expects = $this->any();
		} else {
			$expects = $this->once();
		}
		
		$this->pdo->expects($expects)						
		          ->method('query')
		          ->with($query)
		          ->will($this->returnValue($return));
	}
	
	public function testSelectById()
	{
		$query = 
			"SELECT post.id AS id, post.name AS name, CONCAT(user_1.salutation_name, user_1.first_name, ' ', user_1.last_name) AS createdByName, post.created_by_id AS createdById, post.deleted AS deleted ".
			"FROM post ".
			"LEFT JOIN user AS user_1 ON post.created_by_id = user_1.id " .
			"WHERE post.id = '1' AND post.deleted = '0'";
		$return = new MockDBResult(array(
			array(
				'id' => '1',
				'name' => 'test',
				'deleted' => 0,
			),
		));
		$this->mockQuery($query, $return);
					
		$this->db->selectById($this->post, '1');
		$this->assertEquals($this->post->id, '1');	
	}
	
	public function testSelect()
	{
		$query = 
			"SELECT post.id AS id, post.name AS name, CONCAT(user_1.salutation_name, user_1.first_name, ' ', user_1.last_name) AS createdByName, post.created_by_id AS createdById, post.deleted AS deleted ".
			"FROM post ".
			"LEFT JOIN user AS user_1 ON post.created_by_id = user_1.id " .
			"JOIN post_tag ON post.id = post_tag.post_id AND post_tag.deleted = '0' ".
			"JOIN tag ON tag.id = post_tag.tag_id AND tag.deleted = '0' ".
			"JOIN comment ON post.id = comment.post_id AND comment.deleted = '0' ".
			"WHERE post.name = 'test_1' AND (post.id = '100' OR post.name LIKE 'test_%') AND tag.name = 'yoTag' AND post.deleted = '0' ".
			"ORDER BY post.name DESC ".
			"LIMIT 0, 10";
		$return = new MockDBResult(array(
			array(
				'id' => '2',
				'name' => 'test_2',
				'deleted' => 0,
			),
			array(
				'id' => '1',
				'name' => 'test_1',
				'deleted' => 0,
			),
		));
		$this->mockQuery($query, $return);
		
		$selectParams = array(
			'whereClause' => array(
				'name' => 'test_1',
				'OR' => array(
					'id' => '100',
					'name*' => 'test_%',					
				),
				'Tag.name' => 'yoTag',
			),
			'order' => 'DESC',
			'orderBy' => 'name',
			'limit' => 10,
			'joins' => array(
				'tags',
				'comments',
			),
		);
		$list = $this->db->select($this->post, $selectParams);
		
		
		$this->assertTrue($list[0] instanceof Post);
		$this->assertTrue(isset($list[0]->id));	
		$this->assertEquals($list[0]->id, '2');	
	}
	
	public function testJoin()
	{
		$query = 
			"SELECT comment.id AS id, comment.post_id AS postId, post_1.name AS postName, comment.name AS name, comment.deleted AS deleted ".
			"FROM comment ".
			"LEFT JOIN post AS post_1 ON comment.post_id = post_1.id ".
			"WHERE comment.deleted = '0'";
		$return = new MockDBResult(array(
			array(
				'id' => '11',
				'postId' => '1',
				'postName' => 'test',
				'name' => 'test_comment',
				'deleted' => 0,
			),
		));
		$this->mockQuery($query, $return);
		
		$list = $this->db->select($this->comment);

		$this->assertTrue($list[0] instanceof Comment);
		$this->assertTrue($list[0]->has('postName'));
		$this->assertEquals($list[0]->get('postName'), 'test');
	}
	
	public function testSelectRelatedManyMany()
	{
		$query = 
			"SELECT tag.id AS id, tag.name AS name, tag.deleted AS deleted ".
			"FROM tag ".
			"JOIN post_tag ON tag.id = post_tag.tag_id AND post_tag.post_id = '1' AND post_tag.deleted = '0' ".
			"WHERE tag.deleted = '0'";
		$return = new MockDBResult(array(
			array(
				'id' => '1',
				'name' => 'test',
				'deleted' => 0,
			),
		));
		$this->mockQuery($query, $return);
		$this->post->id = '1';
		$list = $this->db->selectRelated($this->post, 'tags');
		
		$this->assertTrue($list[0] instanceof Tag);
		$this->assertTrue($list[0]->has('name'));
		$this->assertEquals($list[0]->get('name'), 'test');
	}
	
	public function testSelectRelatedHasChildren()
	{
		$query = 
			"SELECT note.id AS id, note.name AS name, note.parent_id AS parentId, note.parent_type AS parentType, note.deleted AS deleted ".
			"FROM note ".
			"WHERE note.deleted = '0' AND note.parent_id = '1' AND note.parent_type = 'Post'";
		$return = new MockDBResult(array(
			array(
				'id' => '1',
				'name' => 'test',
				'deleted' => 0,
			),
		));
		$this->mockQuery($query, $return);
		$this->post->id = '1';
		$list = $this->db->selectRelated($this->post, 'notes');
		
		$this->assertTrue($list[0] instanceof Note);
		$this->assertTrue($list[0]->has('name'));
		$this->assertEquals($list[0]->get('name'), 'test');
	}
	
	public function testSelectRelatedBelongsTo()
	{
		$query = 
			"SELECT post.id AS id, post.name AS name, CONCAT(user_1.salutation_name, user_1.first_name, ' ', user_1.last_name) AS createdByName, post.created_by_id AS createdById, post.deleted AS deleted ".
			"FROM post ".
			"LEFT JOIN user AS user_1 ON post.created_by_id = user_1.id " .
			"WHERE post.deleted = '0' AND post.id = '1' ".
			"LIMIT 0, 1";
		$return = new MockDBResult(array(
			array(
				'id' => '1',
				'name' => 'test',
				'deleted' => 0,
			),
		));
		$this->mockQuery($query, $return);
		
		$this->comment->id = '11';
		$this->comment->set('postId', '1');
		$post = $this->db->selectRelated($this->comment, 'post');		
		
		$this->assertTrue($post instanceof Post);
		$this->assertTrue(($post->has('name')));
		$this->assertEquals($post->get('name'), 'test');
	}
	
	
	public function testCountRelated()
	{
		$query = 
			"SELECT COUNT(tag.id) AS AggregateValue ".
			"FROM tag ".
			"JOIN post_tag ON tag.id = post_tag.tag_id AND post_tag.post_id = '1' AND post_tag.deleted = '0' ".
			"WHERE tag.deleted = '0'";
		$return = new MockDBResult(array(
			array(
				'AggregateValue' => 1,
			),
		));
		$this->mockQuery($query, $return);
		
		$this->post->id = '1';
		$count = $this->db->countRelated($this->post, 'tags');
		
		$this->assertEquals($count, 1);
	}
	
	public function testInsert()
	{	
		$query = "INSERT INTO post (id, name) VALUES ('1', 'test')";
		$return = true;
		$this->mockQuery($query, $return);
	
		$this->post->reset();		
		$this->post->id = '1';
		$this->post->set('name', 'test');
		$this->post->set('privateField', 'dontStoreThis');
		
		$this->db->insert($this->post);	
	}
	
	public function testUpdate()
	{	
		$query = "UPDATE post SET name = 'test' WHERE post.id = '1' AND post.deleted = '0'";
		$return = true;
		$this->mockQuery($query, $return);
	
		$this->post->reset();		
		$this->post->id = '1';
		$this->post->set('name', 'test');
		
		$this->db->update($this->post);	
	}
	
	public function testRemoveRelationHasMany()
	{
		$query = "UPDATE comment SET post_id = NULL WHERE comment.deleted = '0' AND comment.id = '100'";
		$return = true;
		$this->mockQuery($query, $return);
		
		$this->post->id = '1';
		$this->db->removeRelation($this->post, 'comments', '100');
	}
	
	public function testRemoveAllHasMany()
	{
		$query = "UPDATE comment SET post_id = NULL WHERE comment.deleted = '0' AND comment.post_id = '1'";
		$return = true;
		$this->mockQuery($query, $return);
		
		$this->post->id = '1';
		$this->db->removeAllRelations($this->post, 'comments');
	}
	
	public function testRemoveRelationManyMany()
	{
		$query = "UPDATE post_tag SET deleted = 1 WHERE post_id = '1' AND tag_id = '100'";
		$return = true;
		$this->mockQuery($query, $return);
		
		$this->post->id = '1';
		$this->db->removeRelation($this->post, 'tags', '100');	
	}
	
	public function testRemoveAllManyMany()
	{
		$query = "UPDATE post_tag SET deleted = 1 WHERE post_id = '1'";
		$return = true;
		$this->mockQuery($query, $return);
		
		$this->post->id = '1';
		$this->db->removeAllRelations($this->post, 'tags');	
	}
	
	public function testUnrelate()
	{
		$query = "UPDATE post_tag SET deleted = 1 WHERE post_id = '1' AND tag_id = '100'";
		$return = true;
		$this->mockQuery($query, $return);
		
		$this->post->id = '1';
		$this->tag->id = '100';
		$this->db->unrelate($this->post, 'tags', $this->tag);
	}	
		
	public function testAddRelation()
	{
		// @todo 
	}
	
	public function testMax()
	{
		$query = "SELECT MAX(post.id) AS AggregateValue FROM post LEFT JOIN user AS user_1 ON post.created_by_id = user_1.id";
		$return = new MockDBResult(array(
			array (
				'AggregateValue' => 10,
			)
		));
		$this->mockQuery($query, $return);
		
		$value = $this->db->max($this->post, array(), 'id', true);
		
		$this->assertEquals($value, 10);
	}
}


