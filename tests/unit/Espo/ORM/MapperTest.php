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

/** @noinspection PhpVariableIsUsedOnlyInClosureInspection */

namespace tests\unit\Espo\ORM;

use Espo\ORM\CollectionFactory;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Executor\DefaultQueryExecutor;
use Espo\ORM\Mapper\BaseMapper;
use Espo\ORM\Metadata;
use Espo\ORM\MetadataDataProvider;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\QueryComposer\MysqlQueryComposer as QueryComposer;
use Espo\ORM\QueryComposer\QueryComposerWrapper;
use Espo\ORM\Executor\SqlExecutor;
use Espo\ORM\SthCollection;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tests\unit\testData\DB\Comment;
use tests\unit\testData\DB\Note;
use tests\unit\testData\DB\Post;
use tests\unit\testData\DB\Tag;

use PDO;
use PDOStatement;

require_once 'tests/unit/testData/DB/Entities.php';

class MapperTest extends TestCase
{
    protected $db;
    protected $pdo;
    protected $post;
    protected $note;
    protected $comment;
    protected $entityFactory;
    protected $team;
    protected $account;
    protected $contact;
    protected $postData;
    protected $tag;
    protected $query;
    protected $sqlExecutor;
    protected $metadata;

    private ?CollectionFactory $collectionFactory = null;

    protected function setUp() : void
    {
        $this->pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->pdo
            ->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function() {
                $args = func_get_args();

                return "'" . $args[0] . "'";
            });

        $ormMetadata = include('tests/unit/testData/DB/ormMetadata.php');

        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($ormMetadata);

        $this->metadata = new Metadata($metadataDataProvider);

        $this->sqlExecutor = $this->createMock(SqlExecutor::class);
        $entityManager = $this->createMock(EntityManager::class);

        $entityManager
            ->method('getMetadata')
            ->willReturn($this->metadata);

        $this->entityFactory = $this->createMock(EntityFactory::class);

        $this->entityFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () use ($entityManager) {
                    $args = func_get_args();
                    $className = "tests\\unit\\testData\\DB\\" . $args[0];
                    $defs = $this->metadata->get($args[0]) ?? [];

                    return new $className($args[0], $defs, $entityManager);
                }
            );

        $entityManager
            ->method('getEntityFactory')
            ->willReturn($this->entityFactory);

        $entityFactory = $this->entityFactory;

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->query = new QueryComposer($this->pdo, $this->entityFactory, $this->metadata);

        $queryExecutor = new DefaultQueryExecutor($this->sqlExecutor, new QueryComposerWrapper($this->query));

        $this->db = new BaseMapper(
            $this->pdo,
            $this->entityFactory,
            $this->collectionFactory,
            $this->metadata,
            $queryExecutor,
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

            foreach ($data as $item) {
                $values[] = $item;
            }

            $sth->expects($this->exactly(count($values)))
                ->method('fetch')
                ->willReturnOnConsecutiveCalls(...$values);
        }

        $sth->expects($this->any())
            ->method('fetchAll')
            ->willReturn($data);

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
        } else if (is_array($return)) {
            $return = $this->createSthMock($return, $noIteration);
        }

        $this->sqlExecutor->expects($expects)
            ->method('execute')
            ->with($sql)
            ->willReturn($return);
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

        $generator = (function () use ($itemList) {
            foreach ($itemList as $item) {
                yield $item;
            }
        })();

        $collection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(
                $generator
            );

        return $collection;
    }

    public function testSelectOne()
    {
        $query =
            "SELECT post.id AS `id`, post.name AS `name`, NULLIF(TRIM(CONCAT(COALESCE(createdBy.salutation_name, ''), " .
            "COALESCE(createdBy.first_name, ''), ' ', COALESCE(createdBy.last_name, ''))), '') AS `createdByName`, ".
             "post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` AS `post` ".
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

        $this->assertEquals('1', $post->id);
    }

    public function testSelect1(): void
    {
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

        $query = Select::fromRaw([
            'from' => 'Post',
            'fromAlias' => 'post',
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
        ]);

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $list = $this->db->select($query);

        $entity = null;
        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Post);
        $this->assertTrue(isset($entity->id));
        $this->assertEquals('2', $entity->id);
    }

    public function testSelectWithSpecifiedParams(): void
    {
        $contact = $this->entityFactory->create('Post');
        $contact->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);

        $collection = $this->createCollectionMock([$contact]);

        $query = Select::fromRaw([
            'from' => 'Contact',
            'fromAlias' => 'contact',
            'whereClause' => [
                'name*' => 'test%',
            ],
            'order' => 'DESC',
            'orderBy' => 'name',
            'limit' => 10,
        ]);

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $this->db->select($query);
    }

    public function testJoin(): void
    {
        $comment = $this->entityFactory->create('Comment');
        $comment->set([
            'id' => '11',
            'postId' => '1',
            'postName' => 'test',
            'name' => 'test_comment',
            'deleted' => false,
        ]);

        $collection = $this->createCollectionMock([$comment]);

        $query = Select::fromRaw([
            'from' => 'Comment',
            'fromAlias' => 'comment',
        ]);

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $list = $this->db->select($query);

        $entity = null;
        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Comment);
        $this->assertTrue($entity->has('postName'));
        $this->assertEquals('test', $entity->get('postName'));
    }

    public function testSelectRelatedManyMany1()
    {
        $tag = $this->entityFactory->create('Tag');
        $tag->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);
        $collection = $this->createCollectionMock([$tag]);

        $this->post->set('id', '1');

        $query = SelectBuilder::create()
            ->from('Tag', 'tag')
            ->select([
                ['*'],
                ['postTag.role', 'postRole']
            ])
            ->join('PostTag', 'postTag', [
                'tagId:' => 'id',
                'postId' => '1',
                'deleted' => false,
            ])
            ->build();

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $list = $this->db->selectRelated($this->post, 'tags');

        $entity = null;
        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Tag);
        $this->assertTrue($entity->has('name'));
        $this->assertEquals('test', $entity->get('name'));
    }

    public function testSelectRelatedManyMany2()
    {
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

        $this->post->set('id', '1');

        $query = SelectBuilder::create()
            ->from('Tag', 'tag')
            ->select([
                ['id'],
                ['postTag.role', 'postRole'],
            ])
            ->join('PostTag', 'postTag', [
                'tagId:' => 'id',
                'postId' => '1',
                'deleted' => false,
            ])
            ->build();

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $this->db->selectRelated($this->post, 'tags', $select);
    }

    public function testSelectRelatedManyManyWithConditions(): void
    {
        $team = $this->entityFactory->create('Team');
        $team->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);
        $collection = $this->createCollectionMock([$team]);

        $this->account->set('id', '1');

        $query = SelectBuilder::create()
            ->from('Team', 'team')
            ->select([
                ['*'],
                ['entityTeam.teamId', 'stub'],
            ])
            ->join('EntityTeam', 'entityTeam', [
                'teamId:' => 'id',
                'entityId' => '1',
                'entityType' => 'Account',
                'deleted' => false,
            ])
            ->build();

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $select = Select::fromRaw([
            'from' => 'Team',
            'select' => [
                '*',
                ['entityTeam.teamId', 'stub'],
            ],
        ]);

        $this->db->selectRelated($this->account, 'teams', $select);
    }

    public function testSelectRelatedHasChildren()
    {
        $note = $this->entityFactory->create('Note');

        $note->set([
            'id' => '1',
            'name' => 'test',
            'deleted' => false,
        ]);

        $collection = $this->createCollectionMock([$note]);

        $query = SelectBuilder::create()
            ->from('Note', 'note')
            ->where([
                'parentId' => '1',
                'parentType' => 'Post',
            ])
            ->build();

        $this->collectionFactory
            ->expects($this->once())
            ->method('createFromQuery')
            ->with($query)
            ->willReturn($collection);

        $this->post->id = '1';
        $list = $this->db->selectRelated($this->post, 'notes');

        $entity = null;

        foreach ($list as $item) {
            $entity = $item;
            break;
        }

        $this->assertTrue($entity instanceof Note);
        $this->assertTrue($entity->has('name'));
        $this->assertEquals('test', $entity->get('name'));
    }

    public function testSelectRelatedBelongsTo1(): void
    {
        $query =
            "SELECT ".
            "post.id AS `id`, post.name AS `name`, NULLIF(TRIM(CONCAT(COALESCE(createdBy.salutation_name, ''), ".
            "COALESCE(createdBy.first_name, ''), ' ', COALESCE(createdBy.last_name, ''))), '') AS `createdByName`, ".
            "post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` AS `post` ".
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

        $this->comment->set('id', '11');
        $this->comment->set('postId', '1');
        $post = $this->db->selectRelated($this->comment, 'post');

        $this->assertTrue($post instanceof Post);
        $this->assertTrue($post->has('name'));
        $this->assertEquals('test', $post->get('name'));
    }

    public function testSelectRelatedBelongsToWithQuery(): void
    {
        $query =
            "SELECT ".
            "p.id AS `id`, p.name AS `name`, NULLIF(TRIM(CONCAT(COALESCE(createdBy.salutation_name, ''), ".
            "COALESCE(createdBy.first_name, ''), ' ', COALESCE(createdBy.last_name, ''))), '') AS `createdByName`, ".
            "p.created_by_id AS `createdById`, p.deleted AS `deleted` ".
            "FROM `post` AS `p` ".
            "LEFT JOIN `user` AS `createdBy` ON p.created_by_id = createdBy.id " .
            "WHERE p.id = '1' AND p.deleted = 0 ".
            "LIMIT 0, 1";

        $return = [
            [
                'id' => '1',
                'name' => 'test',
                'deleted' => false,
            ],
        ];

        $this->mockQuery($query, $return);

        $select = SelectBuilder::create()
            ->from('Post', 'p')
            ->build();

        $this->comment->set('id', '11');
        $this->comment->set('postId', '1');

        $this->db->selectRelated($this->comment, 'post', $select);
    }

    public function testCountRelated()
    {
        $query =
            "SELECT COUNT(tag.id) AS `value` ".
            "FROM `tag` AS `tag` ".
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

        $this->assertEquals(1, $count);
    }

    public function testCount1(): void
    {
        $sql = "SELECT COUNT(post.id) AS `value` FROM `post` AS `post` WHERE post.deleted = 0";

        $select = SelectBuilder::create()
            ->from('Post')
            ->build();

        $this->mockQuery($sql, ['value' => 1]);

        $this->db->count($select);
    }

    public function testCountWithDistinct(): void
    {
        $sql = "SELECT COUNT(asq.id) AS `value` FROM (" .
            "SELECT DISTINCT post.id AS `id` FROM `post` AS `post` WHERE post.deleted = 0" .
            ") AS `asq`";

        $select = SelectBuilder::create()
            ->from('Post')
            ->distinct()
            ->order('id')
            ->build();

        $this->mockQuery($sql, ['value' => 1]);

        $this->db->count($select);
    }

    public function testInsert()
    {
        $query = "INSERT INTO `post` (`id`, `name`) VALUES ('1', 'test')";

        $this->mockQuery($query);

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

        $this->mockQuery($query);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');
        $this->post->set('deleted', false);

        $this->db->insertOnDuplicateUpdate($this->post, ['name', 'deleted']);
    }

    public function testMassInsert()
    {
        $query = "INSERT INTO `post` (`id`, `name`) VALUES ('1', 'test1'), ('2', 'test2')";

        $this->mockQuery($query);

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
        $this->mockQuery($query);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');

        $this->db->update($this->post);
    }

    public function testUpdate2()
    {
        $query = "UPDATE `post` SET post.name = 'test', post.deleted = 0 WHERE post.id = '1' AND post.deleted = 0";
        $this->mockQuery($query);

        $this->post->reset();
        $this->post->id = '1';
        $this->post->set('name', 'test');
        $this->post->set('deleted', false);

        $this->db->update($this->post);
    }

    public function testUpdateArray1()
    {
        $query = "UPDATE `job` SET job.array = '[\"2\",\"1\"]' WHERE job.id = '1' AND job.deleted = 0";

        $this->mockQuery($query);

        $job = $this->entityFactory->create('Job');
        $job->id = '1';
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['2', '1']);

        $this->db->update($job);
    }

    public function testUpdateArray2()
    {
        $query = "UPDATE `job` SET job.array = NULL WHERE job.id = '1' AND job.deleted = 0";

        $this->mockQuery($query);

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

        $this->mockQuery($query);

        $this->note->id = 'noteId';
        $this->post->id = 'postId';

        $this->db->unrelate($this->note, 'parent', $this->post);
    }

    public function testRemoveAllChildrenToParent()
    {
        $query =
            "UPDATE `note` SET note.parent_id = NULL, note.parent_type = NULL " .
            "WHERE note.id = 'noteId' AND note.deleted = 0";

        $this->mockQuery($query);

        $this->note->id = 'noteId';
        $this->db->unrelateAll($this->note, 'parent');
    }

    public function testRemoveOneToMany()
    {
        $query =
            "UPDATE `comment` SET comment.post_id = NULL " .
            "WHERE comment.id = 'commentId' AND comment.post_id = 'postId' AND comment.deleted = 0";

        $this->mockQuery($query);

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

    public function testRemovedParentToChildren()
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
        $this->mockQuery($query);

        $this->post->id = '1';

        $this->db->unrelateById($this->post, 'tags', '100');
    }

    public function testRemoveAllManyMany()
    {
        $query = "UPDATE `post_tag` SET post_tag.deleted = 1 WHERE post_tag.post_id = '1'";

        $this->mockQuery($query);

        $this->post->id = '1';

        $this->db->unrelateAll($this->post, 'tags');
    }

    public function testRemoveRelationManyManyWithCondition()
    {
        $query =
            "UPDATE `entity_team` SET entity_team.deleted = 1 ".
            "WHERE entity_team.entity_id = '1' AND entity_team.team_id = '100' AND entity_team.entity_type = 'Account'";

        $this->mockQuery($query);

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
            "SELECT COUNT(comment.id) AS `value` FROM `comment` AS `comment` " .
            "WHERE comment.id = 'c' AND comment.deleted = 0";

        $query2 =
            "UPDATE `comment` SET comment.post_id = 'p' WHERE comment.id = 'c' AND comment.deleted = 0";

        $invokedCount = $this->exactly(2);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->post, 'comments', $this->comment);
    }

    public function testRelateParentToChildren()
    {
        $this->post->id = 'p';
        $this->note->id = 'n';

        $query1 =
            "SELECT COUNT(note.id) AS `value` FROM `note` AS `note` " .
            "WHERE note.id = 'n' AND note.deleted = 0";

        $query2 =
            "UPDATE `note` SET note.parent_id = 'p', note.parent_type = 'Post' WHERE note.id = 'n' AND note.deleted = 0";

        $invokedCount = $this->exactly(2);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->post, 'notes', $this->note);
    }

    public function testRelateManyToOne()
    {
        $this->comment->id = 'c';
        $this->post->id = 'p';

        $query1 =
            "UPDATE `comment` SET comment.post_id = 'p' WHERE comment.id = 'c' AND comment.deleted = 0";


        $invokedCount = $this->exactly(1);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->comment, 'post', $this->post);
    }

    public function testRelateChildrenToParent()
    {
        $this->note->id = 'n';
        $this->post->id = 'p';

        $query1 =
            "UPDATE `note` SET note.parent_id = 'p', note.parent_type = 'Post' WHERE note.id = 'n' AND note.deleted = 0";

        $invokedCount = $this->exactly(1);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

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

        $invokedCount = $this->exactly(2);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->postData, 'post', $this->post);
    }

    public function testRelateOneToOne2()
    {
        $this->post->id = 'p';
        $this->postData->id = 'd';

        $query1 =
            "SELECT COUNT(postData.id) AS `value` FROM `post_data` AS `postData` " .
            "WHERE postData.id = 'd' AND postData.deleted = 0";

        $query2 =
            "UPDATE `post_data` SET post_data.post_id = NULL WHERE post_data.post_id = 'p' AND post_data.deleted = 0";

        $query3 =
            "UPDATE `post_data` SET post_data.post_id = 'p' WHERE post_data.id = 'd' AND post_data.deleted = 0";

        $invokedCount = $this->exactly(3);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2, $query3) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $this->createSthMock([]);
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals($query3, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->post, 'postData', $this->postData);
    }

    public function testRelateManyToMany1Insert()
    {
        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $query1 =
            "SELECT COUNT(tag.id) AS `value` FROM `tag` AS `tag` " .
            "WHERE tag.id = 'tagId' AND tag.deleted = 0";

        $query2 =
            "SELECT post_tag.id AS `id` FROM `post_tag` " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $query3 =
            "INSERT INTO `post_tag` (`post_id`, `tag_id`, `role`) VALUES ('postId', 'tagId', 'Test') " .
            "ON DUPLICATE KEY UPDATE `deleted` = 0, `role` = 'Test'";

        $ps = $this->createMock(PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(0);


        $invokedCount = $this->exactly(3);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2, $query3, $ps) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $ps;
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals($query3, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->post, 'tags', $this->tag, ['role' => 'Test']);
    }

    public function testRelateManyToMany1Update()
    {
        $this->post->id = 'postId';
        $this->tag->id = 'tagId';

        $query1 =
            "SELECT COUNT(tag.id) AS `value` FROM `tag` AS `tag` " .
            "WHERE tag.id = 'tagId' AND tag.deleted = 0";

        $query2 =
            "SELECT post_tag.id AS `id` FROM `post_tag` " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $query3 =
            "UPDATE `post_tag` SET post_tag.deleted = 0, post_tag.role = 'Test' " .
            "WHERE post_tag.post_id = 'postId' AND post_tag.tag_id = 'tagId'";

        $ps = $this->createMock(PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(1);

        $invokedCount = $this->exactly(3);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2, $query3, $ps) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $ps;
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals($query3, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->post, 'tags', $this->tag, ['role' => 'Test']);
    }

    public function testRelateManyToMany2Insert()
    {
        $this->account->id = 'accountId';
        $this->team->id = 'teamId';

        $query1 =
            "SELECT COUNT(team.id) AS `value` FROM `team` AS `team` " .
            "WHERE team.id = 'teamId' AND team.deleted = 0";

        $query2 =
            "SELECT entity_team.id AS `id` FROM `entity_team` " .
            "WHERE entity_team.entity_id = 'accountId' AND entity_team.team_id = 'teamId' AND entity_team.entity_type = 'Account'";

        $query3 =
            "INSERT INTO `entity_team` (`entity_id`, `team_id`, `entity_type`) VALUES ('accountId', 'teamId', 'Account') " .
            "ON DUPLICATE KEY UPDATE `deleted` = 0";

        $ps = $this->createMock(PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(0);

        $invokedCount = $this->exactly(3);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2, $query3, $ps) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $ps;
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals($query3, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

        $this->db->relate($this->account, 'teams', $this->team);
    }

    public function testRelateManyToMany2Update()
    {
        $this->account->id = 'accountId';
        $this->team->id = 'teamId';

        $query1 =
            "SELECT COUNT(team.id) AS `value` FROM `team` AS `team` " .
            "WHERE team.id = 'teamId' AND team.deleted = 0";

        $query2 =
            "SELECT entity_team.id AS `id` FROM `entity_team` " .
            "WHERE entity_team.entity_id = 'accountId' AND entity_team.team_id = 'teamId' AND entity_team.entity_type = 'Account'";

        $query3 =
            "UPDATE `entity_team` SET entity_team.deleted = 0 " .
            "WHERE entity_team.entity_id = 'accountId' AND entity_team.team_id = 'teamId' AND entity_team.entity_type = 'Account'";

        $ps = $this->createMock(PDOStatement::class);
        $ps->expects($this->exactly(1))
            ->method('rowCount')
            ->willReturn(1);

        $invokedCount = $this->exactly(3);

        $this->sqlExecutor
            ->expects($invokedCount)
            ->method('execute')
            ->willReturnCallback(function ($sql) use ($invokedCount, $query1, $query2, $query3, $ps) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals($query1, $sql);

                    return $this->createSthMock([['value' => 1]]);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals($query2, $sql);

                    return $ps;
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals($query3, $sql);

                    return $this->createSthMock([]);
                }

                throw new RuntimeException();
            });

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
        $query = "SELECT MAX(post.id) AS `value` FROM `post` AS `post` WHERE post.deleted = 0";

        $return = [
            [
                'value' => 10,
            ]
        ];

        $this->mockQuery($query, $return);

        $value = $this->db->max(Select::fromRaw(['from' => 'Post']), 'id');

        $this->assertEquals(10, $value);
    }

    public function testMassRelate()
    {
        $query =
            "INSERT INTO `post_tag` (`post_id`, `tag_id`) ".
            "SELECT '1' AS `v0`, tag.id AS `id` FROM `tag` WHERE tag.name = 'test' AND tag.deleted = 0 ".
            "ON DUPLICATE KEY UPDATE `deleted` = 0";

        $this->mockQuery($query);

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
