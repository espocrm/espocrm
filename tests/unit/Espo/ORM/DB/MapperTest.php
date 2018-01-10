<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

use Espo\ORM\DB\MysqlMapper;
use Espo\ORM\DB\Query\Mysql as Query;
use Espo\ORM\EntityFactory;

use Espo\Entities\Post;
use Espo\Entities\Comment;
use Espo\Entities\Tag;
use Espo\Entities\Note;

require_once 'tests/unit/testData/DB/Entities.php';
require_once 'tests/unit/testData/DB/MockPDO.php';
require_once 'tests/unit/testData/DB/MockDBResult.php';

class DBMapperTest extends \PHPUnit\Framework\TestCase
{
    protected $db;
    protected $pdo;
    protected $post;
    protected $note;
    protected $comment;
    protected $entityFactory;

    protected function setUp()
    {
        $this->pdo = $this->createMock('MockPDO');
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

        $this->db = new MysqlMapper($this->pdo, $this->entityFactory, $this->query);
        $this->db->setReturnCollection(true);

        $this->post = new \Espo\Entities\Post();
        $this->comment = new \Espo\Entities\Comment();
        $this->tag = new \Espo\Entities\Tag();
        $this->note = new \Espo\Entities\Note();

        $this->contact = new \Espo\Entities\Contact();
        $this->account = new \Espo\Entities\Account();

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
            "SELECT post.id AS `id`, post.name AS `name`, TRIM(CONCAT(createdBy.salutation_name, createdBy.first_name, ' ', createdBy.last_name)) AS `createdByName`, post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
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
            "SELECT post.id AS `id`, post.name AS `name`, TRIM(CONCAT(createdBy.salutation_name, createdBy.first_name, ' ', createdBy.last_name)) AS `createdByName`, post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
            "JOIN `post_tag` AS `tagsMiddle` ON post.id = tagsMiddle.post_id AND tagsMiddle.deleted = '0' ".
            "JOIN `tag` AS `tags` ON tags.id = tagsMiddle.tag_id AND tags.deleted = '0' ".
            "JOIN `comment` AS `comments` ON post.id = comments.post_id AND comments.deleted = '0' ".
            "WHERE post.name = 'test_1' AND (post.id = '100' OR post.name LIKE 'test_%') AND tags.name = 'yoTag' AND post.deleted = '0' ".
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
                'tags.name' => 'yoTag',
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

    public function testSelectWithSpecifiedParams()
    {
        $query =
            "SELECT contact.id AS `id`, TRIM(CONCAT(contact.first_name, ' ', contact.last_name)) AS `name`, contact.first_name AS `firstName`, contact.last_name AS `lastName`, contact.deleted AS `deleted` ".
            "FROM `contact` ".
            "WHERE (contact.first_name LIKE 'test%' OR contact.last_name LIKE 'test%' OR CONCAT(contact.first_name, ' ', contact.last_name) LIKE 'test%') AND contact.deleted = '0' ".
            "ORDER BY contact.first_name DESC, contact.last_name DESC ".
            "LIMIT 0, 10";

        $return = new MockDBResult(array(
            array(
                'id' => '1',
                'name' => 'test',
                'deleted' => 0,
            ),
        ));
        $this->mockQuery($query, $return);

        $selectParams = array(
            'whereClause' => array(
                'name*' => 'test%',
            ),
            'order' => 'DESC',
            'orderBy' => 'name',
            'limit' => 10
        );
        $list = $this->db->select($this->contact, $selectParams);
    }

    public function testJoin()
    {
        $query =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `postName`, comment.name AS `name`, comment.deleted AS `deleted` ".
            "FROM `comment` ".
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id ".
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
            "SELECT tag.id AS `id`, tag.name AS `name`, tag.deleted AS `deleted` ".
            "FROM `tag` ".
            "JOIN `post_tag` ON tag.id = post_tag.tag_id AND post_tag.post_id = '1' AND post_tag.deleted = '0' ".
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
            "SELECT note.id AS `id`, note.name AS `name`, note.parent_id AS `parentId`, note.parent_type AS `parentType`, note.deleted AS `deleted` ".
            "FROM `note` ".
            "WHERE note.parent_id = '1' AND note.parent_type = 'Post' AND note.deleted = '0'";
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
            "SELECT post.id AS `id`, post.name AS `name`, TRIM(CONCAT(createdBy.salutation_name, createdBy.first_name, ' ', createdBy.last_name)) AS `createdByName`, post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
            "WHERE post.id = '1' AND post.deleted = '0' ".
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
            "FROM `tag` ".
            "JOIN `post_tag` ON tag.id = post_tag.tag_id AND post_tag.post_id = '1' AND post_tag.deleted = '0' ".
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
        $query = "INSERT INTO `post` (`id`, `name`) VALUES ('1', 'test')";
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
        $query = "UPDATE `post` SET `name` = 'test' WHERE post.id = '1' AND post.deleted = '0'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');

        $this->db->update($this->post);
    }

    public function testRemoveRelationHasMany()
    {
        $query = "UPDATE `comment` SET post_id = NULL WHERE comment.deleted = '0' AND comment.id = '100'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';
        $this->db->removeRelation($this->post, 'comments', '100');
    }

    public function testRemoveAllHasMany()
    {
        $query = "UPDATE `comment` SET post_id = NULL WHERE comment.deleted = '0' AND comment.post_id = '1'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';
        $this->db->removeAllRelations($this->post, 'comments');
    }

    public function testRemoveRelationManyMany()
    {
        $query = "UPDATE `post_tag` SET deleted = 1 WHERE post_id = '1' AND tag_id = '100'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';
        $this->db->removeRelation($this->post, 'tags', '100');
    }

    public function testRemoveAllManyMany()
    {
        $query = "UPDATE `post_tag` SET deleted = 1 WHERE post_id = '1'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';
        $this->db->removeAllRelations($this->post, 'tags');
    }

    public function testRemoveRelationManyManyWithCondition()
    {
        $query = "UPDATE `entity_team` SET deleted = 1 WHERE entity_id = '1' AND team_id = '100' AND entity_type = 'Account'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->account->id = '1';
        $this->db->removeRelation($this->account, 'teams', '100');
    }

    public function testRemoveAllManyManyWithCondition()
    {
        $query = "UPDATE `entity_team` SET deleted = 1 WHERE entity_id = '1' AND entity_type = 'Account'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->account->id = '1';
        $this->db->removeAllRelations($this->account, 'teams');
    }

    public function testUnrelate()
    {
        $query = "UPDATE `post_tag` SET deleted = 1 WHERE post_id = '1' AND tag_id = '100'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';
        $this->tag->id = '100';
        $this->db->unrelate($this->post, 'tags', $this->tag);
    }

    public function testMax()
    {
        $query = "SELECT MAX(post.id) AS AggregateValue FROM `post` LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id";
        $return = new MockDBResult(array(
            array (
                'AggregateValue' => 10,
            )
        ));
        $this->mockQuery($query, $return);

        $value = $this->db->max($this->post, array(), 'id', true);

        $this->assertEquals($value, 10);
    }

    public function testMassRelate()
    {
        $query = "INSERT INTO `post_tag` (post_id, tag_id) (SELECT '1' AS `1`, tag.id AS `id` FROM `tag` WHERE tag.name = 'test' AND tag.deleted = '0') ON DUPLICATE KEY UPDATE deleted = '0'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';

        $this->db->massRelate($this->post, 'tags', array(
            'whereClause' => array(
                'name' => 'test'
            )
        ));
    }
}


