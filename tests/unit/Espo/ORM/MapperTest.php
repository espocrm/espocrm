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

namespace tests\unit\Espo\ORM;

use Espo\ORM\{
    Metadata,
    EntityManager,
    EntityFactory,
    CollectionFactory,
    EntityCollection,
    SthCollection,
    SqlExecutor,
    QueryComposer\MysqlQueryComposer as QueryComposer,
    Mapper\MysqlMapper,
    Query\Select,
    MetadataDataProvider,
};

use tests\unit\testData\DB\{
    Post,
    Comment,
    Tag,
    Note,
};

use PDO;
use PDOStatement;

require_once 'tests/unit/testData/DB/Entities.php';

class MapperTest extends \PHPUnit\Framework\TestCase
{
    protected $db;
    protected $pdo;
    protected $post;
    protected $note;
    protected $comment;
    protected $entityFactory;

    protected function setUp() : void
    {
        $this->pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->pdo
            ->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function() {
                $args = func_get_args();

                return "'" . $args[0] . "'";
            }));

        $ormMetadata = include('tests/unit/testData/DB/ormMetadata.php');

        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($ormMetadata);

        $this->metadata = new Metadata($metadataDataProvider);

        $this->sqlExecutor = $this->getMockBuilder(SqlExecutor::class)->disableOriginalConstructor()->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $entityManager
            ->method('getMetadata')
            ->will($this->returnValue($this->metadata));

        $this->entityFactory = $this->getMockBuilder(EntityFactory::class)->disableOriginalConstructor()->getMock();

        $this->entityFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(
                function () use ($entityManager) {
                    $args = func_get_args();

                    $className = "tests\\unit\\testData\\DB\\" . $args[0];

                    $defs = $this->metadata->get($args[0]) ?? [];

                    return new $className($args[0], $defs, $entityManager);
                }
            ));

        $entityManager
            ->method('getEntityFactory')
            ->will($this->returnValue($this->entityFactory));

        $entityFactory = $this->entityFactory;

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->query = new QueryComposer($this->pdo, $this->entityFactory, $this->metadata);

        $this->db = new MysqlMapper(
            $this->pdo, $this->entityFactory, $this->collectionFactory,
            $this->query, $this->metadata, $this->sqlExecutor
        );

        $this->post = $entityFactory->create('Post');
        $this->comment = $entityFactory->create('Comment');
        $this->tag = $entityFactory->create('Tag');
        $this->note = $entityFactory->create('Note');
        $this->postData = $entityFactory->create('PostData');

        $this->contact = $entityFactory->create('Contact');
        $this->account = $entityFactory->create('Account');
        $this->team = $entityFactory->create('Team');
    }

    protected function tearDown() : void
    {
        unset($this->pdo, $this->db, $this->post, $this->comment);
    }

    protected function createSthMock(array $data, bool $noIteration = false)
    {
        $sth = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();

        if (!$noIteration) {
            $values = [];

            foreach ($data as $i => $item) {
                $values[] = $item;
            }

            $sth->expects($this->exactly(count($values)))
                ->method('fetch')
                ->willReturnOnConsecutiveCalls(...$values);
        }

        $sth->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($data));

        return $sth;
    }

    protected function mockQuery(string $sql, $return = true, $any = false, bool $noIteration = false)
    {
        if ($any) {
            $expects = $this->any();
        } else {
            $expects = $this->once();
        }

        if ($return === true) {
            $return = $this->createSthMock([]);
        } else
        if (is_array($return)) {
            $return = $this->createSthMock($return, $noIteration);
        }

        $this->sqlExecutor->expects($expects)
                  ->method('execute')
                  ->with($sql)
                  ->will($this->returnValue($return));
    }

    protected function mockSqlCollection(string $entityType, string $sql, SthCollection $collection)
    {
        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromSql')
            ->with($entityType, $sql)
            ->willReturn($collection);
    }

    protected function createCollectionMock(array $itemList) : SthCollection
    {
        $collection = $this->getMockBuilder(SthCollection::class)->disableOriginalConstructor()->getMock();

        $itemList = $itemList ?? [];

        $generator = (function () use ($itemList) {
            foreach ($itemList as $item) {
                yield $item;
            }
        })();

        $collection
            ->expects($this->any())
            ->method('getIterator')
            ->will(
                $this->returnValue($generator)
            );

        return $collection;
    }

    public function testSelectOne()
    {
        $query =
            "SELECT post.id AS `id`, post.name AS `name`, NULLIF(TRIM(CONCAT(IFNULL(createdBy.salutation_name, ''), " .
            "IFNULL(createdBy.first_name, ''), ' ', IFNULL(createdBy.last_name, ''))), '') AS `createdByName`, ".
             "post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
            "WHERE post.id = '1' AND post.deleted = 0";

        $return = [
            [
                'id' => '1',
                'name' => 'test',
                'deleted' => false,
            ],
        ];

        $this->mockQuery($query, $return);

        $select = Select::fromRaw([
            'from' => 'Post',
            'whereClause' => [
                'id' => '1',
            ],
        ]);

        $post = $this->db->selectOne($select);

        $this->assertEquals($post->id, '1');
    }

    public function testSelect1()
    {
        $sql =
            "SELECT post.id AS `id`, post.name AS `name`, NULLIF(TRIM(CONCAT(IFNULL(createdBy.salutation_name, ''), " .
            "IFNULL(createdBy.first_name, ''), ' ', IFNULL(createdBy.last_name, ''))), '') AS `createdByName`, " .
            "post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
            "JOIN `post_tag` AS `tagsMiddle` ON post.id = tagsMiddle.post_id AND tagsMiddle.deleted = 0 ".
            "JOIN `tag` AS `tags` ON tags.id = tagsMiddle.tag_id AND tags.deleted = 0 ".
            "JOIN `comment` AS `comments` ON post.id = comments.post_id AND comments.deleted = 0 ".
            "WHERE post.name = 'test_1' AND (post.id = '100' OR post.name LIKE 'test_%') AND tags.name = 'yoTag' AND post.deleted = 0 ".
            "ORDER BY post.name DESC ".
            "LIMIT 0, 10";

        $post1 = $this->entityFactory->create('Post');
        $post1->set([
            'id' => '2',
            'name' => 'test_2',
            'deleted' => false,
        ]);

        $post2 = $this->entityFactory->create('Post');
        $post2->set([
            'id' => '1',
            'name' => 'test_1',
            'deleted' => false,
        ]);

        $collection = $this->createCollectionMock([$post1, $post2]);
        $this->mockSqlCollection('Post', $sql, $collection);

        $selectParams = [
            'from' => 'Post',
            'whereClause' => [
                'name' => 'test_1',
                'OR' => [
                    'id' => '100',
                    'name*' => 'test_%',
                ],
                'tags.name' => 'yoTag',
            ],
            'order' => 'DESC',
            'orderBy' => 'name',
            'limit' => 10,
            'joins' => [
                'tags',
                'comments',
            ],
        ];

        $list = $this->db->select(Select::fromRaw($selectParams));

        $entity = null;
        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Post);
        $this->assertTrue(isset($entity->id));
        $this->assertEquals($entity->id, '2');
    }

    public function testSelectWithSpecifiedParams()
    {
        $sql =
            "SELECT contact.id AS `id`, TRIM(CONCAT(contact.first_name, ' ', contact.last_name)) AS `name`, " .
            "contact.first_name AS `firstName`, contact.last_name AS `lastName`, contact.deleted AS `deleted` ".
            "FROM `contact` ".
            "WHERE " .
            "(contact.first_name LIKE 'test%' OR contact.last_name LIKE 'test%' OR ".
            "CONCAT(contact.first_name, ' ', contact.last_name) LIKE 'test%') ".
            "AND contact.deleted = 0 ".
            "ORDER BY contact.first_name DESC, contact.last_name DESC ".
            "LIMIT 0, 10";


        $contact = $this->entityFactory->create('Post');
        $contact->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);

        $collection = $this->createCollectionMock([$contact]);
        $this->mockSqlCollection('Contact', $sql, $collection);

        $selectParams = [
            'from' => 'Contact',
            'whereClause' => [
                'name*' => 'test%',
            ],
            'order' => 'DESC',
            'orderBy' => 'name',
            'limit' => 10,
        ];

        $list = $this->db->select(Select::fromRaw($selectParams));
    }

    public function testJoin()
    {
        $sql =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `postName`, comment.name AS `name`, " .
            "comment.deleted AS `deleted` ".
            "FROM `comment` ".
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id ".
            "WHERE comment.deleted = 0";

        $comment = $this->entityFactory->create('Comment');
        $comment->set([
            'id' => '11',
            'postId' => '1',
            'postName' => 'test',
            'name' => 'test_comment',
            'deleted' => false,
        ]);
        $collection = $this->createCollectionMock([$comment]);
        $this->mockSqlCollection('Comment', $sql, $collection);

        $selectParams = [
            'from' => 'Comment',
        ];

        $list = $this->db->select(Select::fromRaw($selectParams));

        $entity = null;
        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Comment);
        $this->assertTrue($entity->has('postName'));
        $this->assertEquals($entity->get('postName'), 'test');
    }

    public function testSelectRelatedManyMany1()
    {
        $sql =
            "SELECT tag.id AS `id`, tag.name AS `name`, tag.deleted AS `deleted`, postTag.role AS `postRole` ".
            "FROM `tag` ".
            "JOIN `post_tag` AS `postTag` ON postTag.tag_id = tag.id AND postTag.post_id = '1' AND postTag.deleted = 0 ".
            "WHERE tag.deleted = 0";

        $tag = $this->entityFactory->create('Tag');
        $tag->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);
        $collection = $this->createCollectionMock([$tag]);
        $this->mockSqlCollection('Tag', $sql, $collection);

        $this->post->id = '1';

        $list = $this->db->selectRelated($this->post, 'tags');

        $entity = null;
        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Tag);
        $this->assertTrue($entity->has('name'));
        $this->assertEquals($entity->get('name'), 'test');
    }

    public function testSelectRelatedManyMany2()
    {
        $sql =
            "SELECT tag.id AS `id`, postTag.role AS `postRole` ".
            "FROM `tag` ".
            "JOIN `post_tag` AS `postTag` ON postTag.tag_id = tag.id AND postTag.post_id = '1' AND postTag.deleted = 0 ".
            "WHERE tag.deleted = 0";

        $select = Select::fromRaw([
            'select' => ['id', 'postRole'],
            'from' => 'Tag',
        ]);

        $tag = $this->entityFactory->create('Tag');
        $tag->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);
        $collection = $this->createCollectionMock([$tag]);
        $this->mockSqlCollection('Tag', $sql, $collection);

        $this->post->id = '1';

        $list = $this->db->selectRelated($this->post, 'tags', $select);
    }

    public function testSelectRelatedManyManyWithConditions()
    {
        $sql =
            "SELECT team.id AS `id`, team.name AS `name`, team.deleted AS `deleted`, entityTeam.team_id AS `stub` FROM `team` ".
            "JOIN `entity_team` AS `entityTeam` ON entityTeam.team_id = team.id AND entityTeam.entity_id = '1' AND " .
            "entityTeam.deleted = 0 AND entityTeam.entity_type = 'Account' WHERE team.deleted = 0";

        $team = $this->entityFactory->create('Team');
        $team->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);
        $collection = $this->createCollectionMock([$team]);
        $this->mockSqlCollection('Team', $sql, $collection);

        $this->account->id = '1';

        $select = Select::fromRaw([
            'from' => 'Team',
            'select' => [
                '*',
                ['entityTeam.teamId', 'stub'],
            ],
        ]);

        $list = $this->db->selectRelated($this->account, 'teams', $select);
    }

    public function testSelectRelatedHasChildren()
    {
        $sql =
            "SELECT ".
            "note.id AS `id`, note.name AS `name`, note.parent_id AS `parentId`, note.parent_type AS `parentType`, note.deleted AS `deleted` ".
            "FROM `note` ".
            "WHERE note.parent_id = '1' AND note.parent_type = 'Post' AND note.deleted = 0";

        $note = $this->entityFactory->create('Note');

        $note->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);

        $collection = $this->createCollectionMock([$note]);

        $this->mockSqlCollection('Note', $sql, $collection);

        $this->post->id = '1';
        $list = $this->db->selectRelated($this->post, 'notes');

        $entity = null;

        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Note);
        $this->assertTrue($entity->has('name'));
        $this->assertEquals($entity->get('name'), 'test');
    }

    public function testSelectRelatedBelongsTo()
    {
        $query =
            "SELECT ".
            "post.id AS `id`, post.name AS `name`, NULLIF(TRIM(CONCAT(IFNULL(createdBy.salutation_name, ''), ".
            "IFNULL(createdBy.first_name, ''), ' ', IFNULL(createdBy.last_name, ''))), '') AS `createdByName`, ".
            "post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
            "WHERE post.id = '1' AND post.deleted = 0 ".
            "LIMIT 0, 1";
        $return = [
            [
                'id' => '1',
                'name' => 'test',
                'deleted' => false,
            ],
        ];
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
            "SELECT COUNT(tag.id) AS `value` ".
            "FROM `tag` ".
            "JOIN `post_tag` AS `postTag` ON postTag.tag_id = tag.id AND postTag.post_id = '1' AND postTag.deleted = 0 ".
            "WHERE tag.deleted = 0";

        $return = [
            [
                'value' => 1,
            ],
        ];

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

    public function testInsertUpdate()
    {
        $query =
            "INSERT INTO `post` (`id`, `name`, `deleted`) VALUES ('1', 'test', 0) " .
            "ON DUPLICATE KEY UPDATE `name` = 'test', `deleted` = 0";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');
        $this->post->set('deleted', false);

        $this->db->insertOnDuplicateUpdate($this->post, ['name', 'deleted']);
    }

    public function testMassInsert()
    {
        $query = "INSERT INTO `post` (`id`, `name`) VALUES ('1', 'test1'), ('2', 'test2')";
        $return = true;
        $this->mockQuery($query, $return);

        $post1 = $this->entityFactory->create('Post');
        $post2 = $this->entityFactory->create('Post');

        $post1->id = '1';
        $post1->set('name', 'test1');

        $post2->id = '2';
        $post2->set('name', 'test2');

        $collection = new EntityCollection([
            $post1,
            $post2,
        ]);

        $this->db->massInsert($collection);
    }

    public function testUpdate1()
    {
        $query = "UPDATE `post` SET post.name = 'test' WHERE post.id = '1' AND post.deleted = 0";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');

        $this->db->update($this->post);
    }

    public function testUpdate2()
    {
        $query = "UPDATE `post` SET post.name = 'test', post.deleted = 0 WHERE post.id = '1' AND post.deleted = 0";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');
        $this->post->set('deleted', false);

        $this->db->update($this->post);
    }

    public function testUpdateArray1()
    {
        $query = "UPDATE `job` SET job.array = '[\"2\",\"1\"]' WHERE job.id = '1' AND job.deleted = 0";

        $this->mockQuery($query, true);

        $job = $this->entityFactory->create('Job');
        $job->id = '1';
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['2', '1']);

        $this->db->update($job);
    }

    public function testUpdateArray2()
    {
        $query = "UPDATE `job` SET job.array = NULL WHERE job.id = '1' AND job.deleted = 0";

        $this->mockQuery($query, true);

        $job = $this->entityFactory->create('Job');
        $job->id = '1';
        $job->setFetched('array', ['1', '2']);
        $job->set('array', null);

        $this->db->update($job);
    }

    public function testRemoveManyToOne()
    {
        $query =
            "UPDATE `comment` SET comment.post_id = NULL " .
            "WHERE comment.id = 'commentId' AND comment.post_id = 'postId' AND comment.deleted = 0";

        $this->mockQuery($query, true);

        $this->post->id = 'postId';
        $this->comment->id = 'commentId';

        $this->db->unrelate($this->comment, 'post', $this->post);
    }

    public function testRemoveAllManyToOne()
    {
        $query =
            "UPDATE `comment` SET comment.post_id = NULL " .
            "WHERE comment.id = 'commentId' AND comment.deleted = 0";

        $this->mockQuery($query, true);

        $this->comment->id = 'commentId';

        $this->db->unrelateAll($this->comment, 'post');
    }

    public function testRemoveChildrenToParent()
    {
        $query =
            "UPDATE `note` SET note.parent_id = NULL, note.parent_type = NULL " .
            "WHERE note.id = 'noteId' AND note.parent_id = 'postId' AND note.parent_type = 'Post' AND note.deleted = 0";

        $this->mockQuery($query, true);

        $this->note->id = 'noteId';
        $this->post->id = 'postId';

        $this->db->unrelate($this->note, 'parent', $this->post);
    }

    public function testRemoveAllChildrenToParent()
    {
        $query =
            "UPDATE `note` SET note.parent_id = NULL, note.parent_type = NULL " .
            "WHERE note.id = 'noteId' AND note.deleted = 0";

        $this->mockQuery($query, true);

        $this->note->id = 'noteId';
        $this->db->unrelateAll($this->note, 'parent');
    }

    public function testRemoveOneToMany()
    {
        $query =
            "UPDATE `comment` SET comment.post_id = NULL " .
            "WHERE comment.id = 'commentId' AND comment.post_id = 'postId' AND comment.deleted = 0";

        $this->mockQuery($query, true);

        $this->post->id = 'postId';

        $this->db->unrelateById($this->post, 'comments', 'commentId');
    }

    public function testRemoveAllOneToMany()
    {
        $query =
            "UPDATE `comment` SET comment.post_id = NULL " .
            "WHERE comment.post_id = 'postId' AND comment.deleted = 0";

        $this->mockQuery($query, true);

        $this->post->id = 'postId';

        $this->db->unrelateAll($this->post, 'comments');
    }

    public function testRemoveOneToOne1()
    {
        $this->post->id = 'postId';
        $this->postData->id = 'dataId';

        $query =
            "UPDATE `post_data` SET post_data.post_id = NULL " .
            "WHERE post_data.post_id = 'postId' AND post_data.deleted = 0";

        $this->mockQuery($query, true);

        $this->db->unrelateById($this->post, 'postData', 'dataId');
    }

    public function testRemovenParentToChildren()
    {
        $query =
            "UPDATE `note` SET note.parent_id = NULL, note.parent_type = NULL " .
            "WHERE note.id = 'noteId' AND note.parent_id = 'postId' AND note.parent_type = 'Post' AND note.deleted = 0";

        $this->mockQuery($query, true);

        $this->post->id = 'postId';

        $this->db->unrelateById($this->post, 'notes', 'noteId');
    }

    public function testRemoveAllParentToChildren()
    {
        $query =
            "UPDATE `note` SET note.parent_id = NULL, note.parent_type = NULL " .
            "WHERE note.parent_id = 'postId' AND note.parent_type = 'Post' AND note.deleted = 0";

        $this->mockQuery($query, true);

        $this->post->id = 'postId';

        $this->db->unrelateAll($this->post, 'notes');
    }

    public function testRemoveManyMany()
    {
        $query = "UPDATE `post_tag` SET post_tag.deleted = 1 WHERE post_tag.post_id = '1' AND post_tag.tag_id = '100'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';

        $this->db->unrelateById($this->post, 'tags', '100');
    }

    public function testRemoveAllManyMany()
    {
        $query = "UPDATE `post_tag` SET post_tag.deleted = 1 WHERE post_tag.post_id = '1'";
        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';

        $this->db->unrelateAll($this->post, 'tags');
    }

    public function testRemoveRelationManyManyWithCondition()
    {
        $query =
            "UPDATE `entity_team` SET entity_team.deleted = 1 ".
            "WHERE entity_team.entity_id = '1' AND entity_team.team_id = '100' AND entity_team.entity_type = 'Account'";

        $this->mockQuery($query, true);

        $this->account->id = '1';

        $this->db->unrelateById($this->account, 'teams', '100');
    }

    public function testRemoveAllManyManyWithCondition()
    {
        $query =
            "UPDATE `entity_team` SET entity_team.deleted = 1 ".
            "WHERE entity_team.entity_id = '1' AND entity_team.entity_type = 'Account'";

        $this->mockQuery($query, true);

        $this->account->id = '1';
        $this->db->unrelateAll($this->account, 'teams');
    }

    public function testUnrelateManyToMany()
    {
        $query = "UPDATE `post_tag` SET post_tag.deleted = 1 WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $this->mockQuery($query, true);

        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $this->db->unrelate($this->post, 'tags', $this->tag);
    }

    public function testRelateOneToMany()
    {
        $this->post->id = 'p';
        $this->comment->id = 'c';

        $query1 =
            "SELECT COUNT(comment.id) AS `value` FROM `comment` " .
            "WHERE comment.id = 'c' AND comment.deleted = 0";

        $query2 =
            "UPDATE `comment` SET comment.post_id = 'p' WHERE comment.id = 'c' AND comment.deleted = 0";

        $this->sqlExecutor->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$query1], [$query2])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $this->createSthMock([])
            );

        $this->db->relate($this->post, 'comments', $this->comment);
    }

    public function testRelateParentToChildren()
    {
        $this->post->id = 'p';
        $this->note->id = 'n';

        $query1 =
            "SELECT COUNT(note.id) AS `value` FROM `note` " .
            "WHERE note.id = 'n' AND note.deleted = 0";

        $query2 =
            "UPDATE `note` SET note.parent_id = 'p', note.parent_type = 'Post' WHERE note.id = 'n' AND note.deleted = 0";

        $this->sqlExecutor->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$query1], [$query2])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $this->createSthMock([])
            );

        $this->db->relate($this->post, 'notes', $this->note);
    }

    public function testRelateManyToOne()
    {
        $this->comment->id = 'c';
        $this->post->id = 'p';

        $query1 =
            "UPDATE `comment` SET comment.post_id = 'p' WHERE comment.id = 'c' AND comment.deleted = 0";

        $this->sqlExecutor->expects($this->exactly(1))
            ->method('execute')
            ->withConsecutive([$query1])
            ->willReturnOnConsecutiveCalls($this->createSthMock([]));

        $this->db->relate($this->comment, 'post', $this->post);
    }

    public function testRelateChildrenToParent()
    {
        $this->note->id = 'n';
        $this->post->id = 'p';

        $query1 =
            "UPDATE `note` SET note.parent_id = 'p', note.parent_type = 'Post' WHERE note.id = 'n' AND note.deleted = 0";

        $this->sqlExecutor->expects($this->exactly(1))
            ->method('execute')
            ->withConsecutive([$query1])
            ->willReturnOnConsecutiveCalls($this->createSthMock([]));

        $this->db->relate($this->note, 'parent', $this->post);
    }

    public function testRelateOneToOne1()
    {
        $this->post->id = 'p';
        $this->postData->id = 'd';

        $query1 =
            "UPDATE `post_data` SET post_data.post_id = NULL WHERE post_data.id <> 'd' AND post_data.post_id = 'p' AND post_data.deleted = 0";

        $query2 =
            "UPDATE `post_data` SET post_data.post_id = 'p' WHERE post_data.id = 'd' AND post_data.deleted = 0";

        $this->sqlExecutor->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$query1], [$query2])
            ->willReturnOnConsecutiveCalls($this->createSthMock([]), $this->createSthMock([]));

        $this->db->relate($this->postData, 'post', $this->post);
    }

    public function testRelateOneToOne2()
    {
        $this->post->id = 'p';
        $this->postData->id = 'd';

        $query1 =
            "SELECT COUNT(post_data.id) AS `value` FROM `post_data` " .
            "WHERE post_data.id = 'd' AND post_data.deleted = 0";

        $query2 =
            "UPDATE `post_data` SET post_data.post_id = NULL WHERE post_data.post_id = 'p' AND post_data.deleted = 0";

        $query3 =
            "UPDATE `post_data` SET post_data.post_id = 'p' WHERE post_data.id = 'd' AND post_data.deleted = 0";

        $this->sqlExecutor->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive([$query1], [$query2], [$query3])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $this->createSthMock([]),
                $this->createSthMock([])
            );

        $this->db->relate($this->post, 'postData', $this->postData);
    }

    public function testRelateManyToMany1Insert()
    {
        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $query1 =
            "SELECT COUNT(tag.id) AS `value` FROM `tag` " .
            "WHERE tag.id = 'tagId' AND tag.deleted = 0";

        $query2 =
            "SELECT post_tag.id AS `id` FROM `post_tag` " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $query3 =
            "INSERT INTO `post_tag` (`post_id`, `tag_id`, `role`) VALUES ('postId', 'tagId', 'Test') " .
            "ON DUPLICATE KEY UPDATE `deleted` = 0, `role` = 'Test'";

        $ps = $this->createMock(\PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(0);

        $this->sqlExecutor
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive([$query1], [$query2], [$query3])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $ps,
                $this->createSthMock([])
            );

        $this->db->relate($this->post, 'tags', $this->tag, ['role' => 'Test']);
    }

    public function testRelateManyToMany1Update()
    {
        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $query1 =
            "SELECT COUNT(tag.id) AS `value` FROM `tag` " .
            "WHERE tag.id = 'tagId' AND tag.deleted = 0";

        $query2 =
            "SELECT post_tag.id AS `id` FROM `post_tag` " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $query3 =
            "UPDATE `post_tag` SET post_tag.deleted = 0, post_tag.role = 'Test' " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $ps = $this->createMock(\PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(1);

        $this->sqlExecutor
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive([$query1], [$query2], [$query3])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $ps,
                $this->createSthMock([])
            );

        $this->db->relate($this->post, 'tags', $this->tag, ['role' => 'Test']);
    }

    public function testRelateManyToMany2Insert()
    {
        $this->account->id = 'accountId';
        $this->team->id = 'teamId';

        $query1 =
            "SELECT COUNT(team.id) AS `value` FROM `team` " .
            "WHERE team.id = 'teamId' AND team.deleted = 0";

        $query2 =
            "SELECT entity_team.id AS `id` FROM `entity_team` " .
            "WHERE entity_team.entity_id = 'accountId' AND entity_team.team_id = 'teamId' AND entity_team.entity_type = 'Account'";

        $query3 =
            "INSERT INTO `entity_team` (`entity_id`, `team_id`, `entity_type`) VALUES ('accountId', 'teamId', 'Account') " .
            "ON DUPLICATE KEY UPDATE `deleted` = 0";

        $ps = $this->createMock(\PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(0);

        $this->sqlExecutor
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive([$query1], [$query2], [$query3])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $ps,
                $this->createSthMock([])
            );

        $this->db->relate($this->account, 'teams', $this->team);
    }

    public function testRelateManyToMany2Update()
    {
        $this->account->id = 'accountId';
        $this->team->id = 'teamId';

        $query1 =
            "SELECT COUNT(team.id) AS `value` FROM `team` " .
            "WHERE team.id = 'teamId' AND team.deleted = 0";

        $query2 =
            "SELECT entity_team.id AS `id` FROM `entity_team` " .
            "WHERE entity_team.entity_id = 'accountId' AND entity_team.team_id = 'teamId' AND entity_team.entity_type = 'Account'";

        $query3 =
            "UPDATE `entity_team` SET entity_team.deleted = 0 " .
            "WHERE entity_team.entity_id = 'accountId' AND entity_team.team_id = 'teamId' AND entity_team.entity_type = 'Account'";

        $ps = $this->createMock(\PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(1);

        $this->sqlExecutor
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive([$query1], [$query2], [$query3])
            ->willReturnOnConsecutiveCalls(
                $this->createSthMock([['value' => 1]]),
                $ps,
                $this->createSthMock([])
            );

        $this->db->relate($this->account, 'teams', $this->team);
    }

    public function testGetRelationColumn()
    {
        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $query =
            "SELECT post_tag.role AS `value` FROM `post_tag` " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId' AND post_tag.deleted = 0";

        $this->mockQuery($query, [['value' => 'test']]);

        $result = $this->db->getRelationColumn($this->post, 'tags', 'tagId', 'role');

        $this->assertEquals('test', $result);
    }

    public function testUpdateRelationColumns()
    {
        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $query =
            "UPDATE `post_tag` SET post_tag.role = 'test' " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId' AND post_tag.deleted = 0";

        $this->mockQuery($query);

        $this->db->updateRelationColumns($this->post, 'tags', 'tagId', [
            'role' => 'test'
        ]);
    }

    public function testMax()
    {
        $query = "SELECT MAX(post.id) AS `value` FROM `post` WHERE post.deleted = 0";
        $return =[
            [
                'value' => 10,
            ]
        ];
        $this->mockQuery($query, $return);

        $value = $this->db->max(Select::fromRaw(['from' => 'Post']), 'id');

        $this->assertEquals($value, 10);
    }

    public function testMassRelate()
    {
        $query =
            "INSERT INTO `post_tag` (`post_id`, `tag_id`) ".
            "SELECT '1' AS `v0`, tag.id AS `id` FROM `tag` WHERE tag.name = 'test' AND tag.deleted = 0 ".
            "ON DUPLICATE KEY UPDATE `deleted` = 0";

        $return = true;
        $this->mockQuery($query, $return);

        $this->post->id = '1';

        $select = Select::fromRaw([
            'from' => 'Tag',
            'whereClause' => [
                'name' => 'test',
            ],
        ]);

        $this->db->massRelate($this->post, 'tags', $select);
    }

    public function testDeleteFromDb1()
    {
        $query = "DELETE FROM `comment` WHERE comment.id = '1'";
        $this->mockQuery($query);

        $this->db->deleteFromDb('Comment', '1');
    }

    public function testDeleteFromDb2()
    {
        $query = "DELETE FROM `comment` WHERE comment.id = '1' AND comment.deleted = 1";
        $this->mockQuery($query);

        $this->db->deleteFromDb('Comment', '1', true);
    }

    public function testRestoreDeleted()
    {
        $query = "UPDATE `comment` SET comment.deleted = 0 WHERE comment.id = '1'";
        $this->mockQuery($query);

        $this->db->restoreDeleted('Comment', '1');
    }
}
