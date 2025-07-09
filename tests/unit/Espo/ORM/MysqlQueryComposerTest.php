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

namespace tests\unit\Espo\ORM;

use Espo\ORM\Query\Part\Join;
use Espo\ORM\Query\Part\Where\Comparison;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\QueryComposer\Part\FunctionConverterFactory;
use Espo\ORM\QueryComposer\Part\FunctionConverter;

use Espo\ORM\EntityFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Metadata;
use Espo\ORM\MetadataDataProvider;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\QueryBuilder;
use Espo\ORM\QueryComposer\MysqlQueryComposer as QueryComposer;

use Espo\ORM\Query\Delete;
use Espo\ORM\Query\Insert;
use Espo\ORM\Query\LockTableBuilder;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Update;

use Espo\ORM\QueryComposer\Util;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once 'tests/unit/testData/DB/Entities.php';
require_once 'tests/unit/testData/DB/MockPDO.php';
require_once 'tests/unit/testData/DB/MockDBResult.php';

class MysqlQueryComposerTest extends TestCase
{
    protected ?QueryComposer $query = null;
    protected $pdo = null;
    protected ?EntityFactory $entityFactory = null;

    private $queryBuilder;
    private $metadata;
    private $entityManager;

    protected function setUp(): void
    {
        $this->queryBuilder = new QueryBuilder();

        $this->pdo = $this->createMock('MockPDO');
        $this->pdo
            ->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function() {
                $args = func_get_args();

                $s = $args[0];

                $s = str_replace("'", "\'", $s);

                return "'" . $s . "'";
            });

        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->entityFactory = $this->getMockBuilder(EntityFactory::class)->disableOriginalConstructor()->getMock();

        $ormMetadata = include('tests/unit/testData/DB/ormMetadata.php');

        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($ormMetadata);

        $this->metadata = new Metadata($metadataDataProvider);

        $this->entityFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                    function () {
                        $args = func_get_args();

                        $className = "tests\\unit\\testData\\DB\\" . $args[0];

                        $defs = $this->metadata->get($args[0]) ?? [];

                        return new $className($args[0], $defs, $this->entityManager);
                    }
            );

        $this->query = new QueryComposer($this->pdo, $this->entityFactory, $this->metadata);
    }

    public function testDelete1()
    {
        $sql = $this->query->compose(Delete::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
        ]));

        $expectedSql =
            "DELETE FROM `account` " .
            "WHERE account.name = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testDeleteWithLimit1()
    {
        $sql = $this->query->compose(Delete::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
            'orderBy' => 'name',
            'limit' => 1,
        ]));

        $expectedSql =
            "DELETE FROM `account` " .
            "WHERE account.name = 'test' " .
            "ORDER BY account.name ASC " .
            "LIMIT 1";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testDeleteWithLimit0()
    {
        $sql = $this->query->compose(Delete::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
            'orderBy' => 'name',
            'limit' => 0,
        ]));

        $expectedSql =
            "DELETE FROM `account` " .
            "WHERE account.name = 'test' " .
            "ORDER BY account.name ASC " .
            "LIMIT 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testDeleteWithAlias()
    {
        $query = $this->queryBuilder
            ->delete()
            ->from('Account', 'a')
            ->where([
                'a.name' => 'test',
            ])
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "DELETE `a` FROM `account` AS `a` " .
            "WHERE a.name = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testDeleteWithDeleted()
    {
        $sql = $this->query->compose(Delete::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
                'deleted' => true,
            ],
        ]));

        $expectedSql =
            "DELETE FROM `account` " .
            "WHERE account.name = 'test' AND account.deleted = 1";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUpdateQuery0()
    {
        $sql = $this->query->compose(Update::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
            'set' => [
                'deleted' => false,
                'name' => 'hello',
            ],
            'limit' => 0,
        ]));

        $expectedSql =
            "UPDATE `account` " .
            "SET account.deleted = 0, account.name = 'hello' ".
            "WHERE account.name = 'test' LIMIT 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUpdateQuery1()
    {
        $sql = $this->query->compose(Update::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
            'set' => [
                'deleted' => false,
                'name' => 'hello',
            ],
        ]));

        $expectedSql =
            "UPDATE `account` " .
            "SET account.deleted = 0, account.name = 'hello' ".
            "WHERE account.name = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUpdateQueryWithJoin()
    {
        $sql = $this->query->compose(Update::fromRaw([
            'from' => 'Comment',
            'whereClause' => [
                'name' => 'test',
            ],
            'joins' => ['post'],
            'set' => [
                'name:' => 'post.name',
            ],
        ]));

        $expectedSql =
            "UPDATE `comment` " .
            "JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 ".
            "SET comment.name = post.name ".
            "WHERE comment.name = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUpdateQueryWithOrder()
    {
        $sql = $this->query->compose(Update::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
            'orderBy' => 'name',
            'set' => [
                'deleted' => false,
                'name' => 'hello',
            ]
        ]));

        $expectedSql =
            "UPDATE `account` " .
            "SET account.deleted = 0, account.name = 'hello' ".
            "WHERE account.name = 'test' " .
            "ORDER BY account.name ASC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUpdateQueryWithLimit()
    {
        $sql = $this->query->compose(Update::fromRaw([
            'from' => 'Account',
            'whereClause' => [
                'name' => 'test',
            ],
            'orderBy' => 'name',
            'limit' => 1,
            'set' => [
                'deleted' => false,
                'name' => 'hello',
            ],
        ]));

        $expectedSql =
            "UPDATE `account` " .
            "SET account.deleted = 0, account.name = 'hello' ".
            "WHERE account.name = 'test' " .
            "ORDER BY account.name ASC " .
            "LIMIT 1";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUpdateQueryWithSetExpression()
    {
        $query = $this->queryBuilder
            ->update()
            ->in('Comment')
            ->join('post')
            ->set([
                'name' => Expression::column('post.name'),
            ])
            ->build();

        $sql = $this->query->composeUpdate($query);

        $expectedSql =
            "UPDATE `comment` " .
            "JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 ".
            "SET comment.name = post.name";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInsertQuery1()
    {
        $sql = $this->query->compose(Insert::fromRaw([
            'into' => 'Account',
            'columns' => ['id', 'name'],
            'values' => [
                'id' => '1',
                'name' => 'hello',
            ],
        ]));

        $expectedSql =
            "INSERT INTO `account` (`id`, `name`) VALUES ('1', 'hello')";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInsertQuery2()
    {
        $sql = $this->query->compose(Insert::fromRaw([
            'into' => 'Account',
            'columns' => ['id', 'name'],
            'values' => [
                [
                    'id' => '1',
                    'name' => 'hello',
                ],
                [
                    'id' => '2',
                    'name' => 'test',
                ],
            ],
        ]));

        $expectedSql =
            "INSERT INTO `account` (`id`, `name`) VALUES ('1', 'hello'), ('2', 'test')";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInsertValuesQuery1()
    {
        $selectQuery = $this->queryBuilder
            ->select()
            ->from('Account')
            ->select('id')
            ->withDeleted()
            ->build();

        $query = $this->queryBuilder
            ->insert()
            ->into('Account')
            ->columns(['id'])
            ->valuesQuery($selectQuery)
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "INSERT INTO `account` (`id`) SELECT account.id AS `id` FROM `account`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInsertUpdate()
    {
        $sql = $this->query->compose(Insert::fromRaw([
            'into' => 'Account',
            'columns' => ['id', 'name'],
            'values' => [
                'id' => '1',
                'name' => 'hello',
            ],
            'updateSet' => [
                'deleted' => false,
                'name' => 'test'
            ],
        ]));

        $expectedSql =
            "INSERT INTO `account` (`id`, `name`) VALUES ('1', 'hello') ".
            "ON DUPLICATE KEY UPDATE `deleted` = 0, `name` = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectLimit1()
    {
        $select = $this->queryBuilder
            ->select(['id'])
            ->from('Account')
            ->limit(1, 0)
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT account.id AS `id` FROM `account` " .
            "WHERE account.deleted = 0 LIMIT 1, 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectLimit2()
    {
        $select = $this->queryBuilder
            ->select(['id'])
            ->from('Account')
            ->limit(null, 0)
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT account.id AS `id` FROM `account` " .
            "WHERE account.deleted = 0 LIMIT 0, 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectAllColumns1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Account')
            ->order('name', 'ASC')
            ->where([
                'deleted' => false,
            ])
            ->limit(10, 20)
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT account.id AS `id`, account.name AS `name`, account.deleted AS `deleted` FROM `account` " .
            "WHERE account.deleted = 0 ORDER BY account.name ASC LIMIT 10, 20";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectAllColumns2()
    {
        $expectedSql =
            "SELECT account.id AS `id`, account.name AS `name`, account.deleted AS `deleted` FROM `account` " .
            "WHERE account.deleted = 0";

        $select = $this->queryBuilder
            ->select()
            ->from('Account')
            ->select(['*'])
            ->where([
                'deleted' => false,
            ])
            ->build();

        $sql = $this->query->compose($select);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectAllColumnsWithExtra()
    {
        $expectedSql =
            "SELECT account.id AS `id`, account.name AS `name`, account.deleted AS `deleted`, ".
            "LOWER(account.name) AS `lowerName` " .
            "FROM `account` " .
            "WHERE account.deleted = 0";

        $select = $this->queryBuilder
            ->select()
            ->from('Account')
            ->select(['*', ['LOWER:name', 'lowerName']])
            ->where([
                'deleted' => false,
            ])
            ->build();

        $sql = $this->query->compose($select);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectSkipTextColumns()
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Article',
                'orderBy' => 'name',
                'order' => 'ASC',
                'offset' => 10,
                'limit' => 20,
                'skipTextColumns' => true,
            ])
        );

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name`, article.deleted AS `deleted` FROM `article` " .
            "WHERE article.deleted = 0 ORDER BY article.name ASC LIMIT 10, 20";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithBelongsToJoin()
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Comment',
            ])
        );

        $expectedSql =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `postName`, ".
            "comment.name AS `name`, comment.deleted AS `deleted` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSpecifiedColumns()
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Comment',
                'select' => ['id', 'name']
            ])
        );

        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name` FROM `comment` " .
            "WHERE comment.deleted = 0";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', 'name', 'postName'],
        ]));

        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name`, post.name AS `postName` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', 'name', 'postName'],
            'leftJoins' => ['post'], // bc
        ]));

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', 'name'],
            'joins' => ['post']
        ]));

        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name` FROM `comment` " .
            "JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectDependee1()
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Dependee',
                'select' => ['name'],
            ])
        );

        $expectedSql =
            "SELECT dependee.name AS `name`, dependee.test AS `test` FROM `dependee`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectDependee2()
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Dependee',
                'select' => ['name', 'test'],
            ])
        );

        $expectedSql =
            "SELECT dependee.name AS `name`, dependee.test AS `test` FROM `dependee`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectDependee3()
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Dependee',
            ])
        );

        $expectedSql =
            "SELECT dependee.id AS `id`, dependee.name AS `name`, dependee.test AS `test` FROM `dependee`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectUseIndex()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Account')
            ->select(['id', 'name'])
            ->useIndex('name')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT account.id AS `id`, account.name AS `name` FROM `account` USE INDEX (`IDX_NAME`) " .
            "WHERE account.deleted = 0";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Account',
            'select' => ['id', 'name'],
            'useIndex' => ['name'],
        ]));

        $expectedSql =
            "SELECT account.id AS `id`, account.name AS `name` FROM `account` USE INDEX (`IDX_NAME`) " .
            "WHERE account.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWithSpecifiedFunction()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', 'postId', 'post.name', 'COUNT:id'],
            'joins' => ['post'],
            'groupBy' => ['postId', 'post.name']
        ]));

        $expectedSql =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `post.name`, ".
            "COUNT(comment.id) AS `COUNT:id` FROM `comment` " .
            "JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY comment.post_id, post.name";

        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', 'COUNT:id', 'MONTH:post.createdAt'],
            'joins' => ['post'],
            'groupBy' => ['MONTH:post.createdAt']
        ]));

        $expectedSql =
            "SELECT comment.id AS `id`, COUNT(comment.id) AS `COUNT:id`, ".
            "DATE_FORMAT(post.created_at, '%Y-%m') AS `MONTH:post.createdAt` FROM `comment` " .
            "JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY DATE_FORMAT(post.created_at, '%Y-%m')";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithJoinChildren()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'leftJoins' => [['notes', 'notesLeft']]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note` AS `notesLeft` ON post.id = notesLeft.parent_id AND notesLeft.parent_type = 'Post' ".
            "AND notesLeft.deleted = 0 " .
            "WHERE post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'leftJoins' => [['notes', 'notesLeft', ['notesLeft.name!=' => null]]]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note` AS `notesLeft` ON post.id = notesLeft.parent_id AND notesLeft.parent_type = 'Post' ".
            "AND notesLeft.deleted = 0 " .
            "AND notesLeft.name IS NOT NULL " .
            "WHERE post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions2()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'leftJoins' => [['notes', 'notesLeft', ['notesLeft.name=:' => 'post.name']]]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note` AS `notesLeft` ON post.id = notesLeft.parent_id AND notesLeft.parent_type = 'Post' ".
            "AND notesLeft.deleted = 0 ".
            "AND notesLeft.name = post.name " .
            "WHERE post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions3()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Note',
            'select' => ['id'],
            'leftJoins' => [['post', 'post', [
                'OR' => [
                    ['name' => 'test'],
                    ['post.name' => null],
                ]
            ]]],
            'withDeleted' => true,
        ]));

        $expectedSql = "SELECT note.id AS `id` FROM `note` LEFT JOIN `post` AS `post` ".
            "ON (post.name = 'test' OR post.name IS NULL)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions4()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Note',
            'select' => ['id'],
            'leftJoins' => [['post', 'post', [
                'name' => null,
                'OR' => [
                    ['name' => 'test'],
                    ['post.name' => null],
                ]
            ]]],
            'withDeleted' => true,
        ]));

        $expectedSql = "SELECT note.id AS `id` FROM `note` LEFT JOIN `post` AS `post` ON post.name IS NULL ".
            "AND (post.name = 'test' OR post.name IS NULL)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions5(): void
    {
        $query = $this->queryBuilder
            ->select('id')
            ->from('Note')
            ->leftJoin(
                'Post',
                'post',
                Condition::notEqual(
                    Expression::value('TEST'),
                    Expression::column('post.id')
                )
            )
            ->withDeleted()
            ->build();

        $sql = $this->query->composeSelect($query);

        $expectedSql = "SELECT note.id AS `id` FROM `note` LEFT JOIN `post` AS `post` ON 'TEST' <> post.id";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions6(): void
    {
        $query = $this->queryBuilder
            ->select('id')
            ->from('Note')
            ->leftJoin(
                'Post',
                'post',
                Condition::equal(
                    Expression::column('id'),
                    Expression::column('post.id')
                )
            )
            ->withDeleted()
            ->build();

        $sql = $this->query->composeSelect($query);

        $expectedSql = "SELECT note.id AS `id` FROM `note` LEFT JOIN `post` AS `post` ON note.id = post.id";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions7(): void
    {
        $query = $this->queryBuilder
            ->select('id')
            ->from('Note')
            ->leftJoin(
                'Post',
                'post',
                Condition::or(
                    Condition::equal(
                        Expression::column('id'),
                        Expression::column('post.id')
                    ),
                    Condition::equal(
                        Expression::column('post.id'),
                        Expression::column('id')
                    )
                )
            )
            ->withDeleted()
            ->build();

        $sql = $this->query->composeSelect($query);

        $expectedSql =
            "SELECT note.id AS `id` FROM `note` ".
            "LEFT JOIN `post` AS `post` ON (note.id = post.id OR post.id = note.id)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinTable1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'leftJoins' => [[
                'NoteTable', 'note', [
                    'note.parentId' => Expression::column('post.id'),
                    'note.parentType' => 'Post',
                ]
            ]]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note_table` AS `note` ON note.parent_id = post.id AND note.parent_type = 'Post' " .
            "WHERE post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinTable2()
    {
        $query = $this->queryBuilder
            ->select()
            ->select(['id', 'n.id'])
            ->from('Post')
            ->join('NoteTable', 'n')
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "SELECT post.id AS `id`, n.id AS `n.id` FROM `post` " .
            "JOIN `note_table` AS `n` " .
            "WHERE post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinOnlyMiddle1()
    {
        $sql = $this->query->composeSelect(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id'],
            'leftJoins' => [['tags', null, null, ['onlyMiddle' => true]]]
        ]));

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` " .
            "LEFT JOIN `post_tag` AS `tags` ON post.id = tags.post_id AND tags.deleted = 0 " .
            "WHERE post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinOnlyMiddle2(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "JOIN `post_tag` AS `tags` ON post.id = tags.post_id AND tags.deleted = 0 AND tags.post_id = '1' " .
            "WHERE post.deleted = 0";

        $query = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                Join::create('tags')->withOnlyMiddle()
                    ->withConditions(WhereClause::fromRaw(['tags.postId' => '1']))
            )
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testJoinSubQuery1(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "JOIN (SELECT post.id AS `id` FROM `post` WHERE post.deleted = 0) AS `a` ON a.id = post.id " .
            "WHERE post.deleted = 0";

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                Join
                    ::createWithSubQuery(
                        SelectBuilder::create()
                            ->select('id')
                            ->from('Post')
                            ->build(),
                        'a'
                    )
                    ->withConditions(
                        WhereClause::create(
                            Condition::equal(
                                Expression::column('a.id'),
                                Expression::column('post.id')
                            )
                        )
                    )
            )
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($select)
        );
    }

    public function testJoinSubQuery2(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "JOIN (SELECT post.id AS `id` FROM `post` WHERE post.deleted = 0) AS `a` ON a.id = post.id " .
            "WHERE post.deleted = 0";

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                SelectBuilder::create()
                    ->select('id')
                    ->from('Post')
                    ->build(),
                'a',
                Condition::equal(
                    Expression::column('a.id'),
                    Expression::column('post.id')
                )
            )
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($select)
        );
    }

    public function testJoinSubQueryLateral(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "JOIN LATERAL (SELECT post.id AS `id` FROM `post` LIMIT 0, 1) AS `a` ON TRUE";

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                Join::createWithSubQuery(
                    SelectBuilder::create()
                        ->select('id')
                        ->from('Post')
                        ->limit(0, 1)
                        ->withDeleted()
                        ->build(),
                    'a',
                )
                ->withLateral()
                ->withConditions(
                    Expression::value(true)
                )
            )
            ->withDeleted()
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($select)
        );
    }

    public function testJoinOrder1(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "LEFT JOIN LATERAL (SELECT post.id AS `id` FROM `post` LIMIT 0, 1) AS `a` ON TRUE " .
            "JOIN LATERAL (SELECT post.id AS `id` FROM `post` LIMIT 0, 1) AS `b` ON TRUE";

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->leftJoin(
                Join::createWithSubQuery(
                    SelectBuilder::create()
                        ->select('id')
                        ->from('Post')
                        ->limit(0, 1)
                        ->withDeleted()
                        ->build(),
                    'a',
                )
                    ->withLateral()
                    ->withConditions(
                        Expression::value(true)
                    )
            )
            ->join(
                Join::createWithSubQuery(
                    SelectBuilder::create()
                        ->select('id')
                        ->from('Post')
                        ->limit(0, 1)
                        ->withDeleted()
                        ->build(),
                    'b',
                )
                    ->withLateral()
                    ->withConditions(
                        Expression::value(true)
                    )
            )
            ->withDeleted()
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($select)
        );
    }

    public function testJoinOrder2(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "JOIN LATERAL (SELECT post.id AS `id` FROM `post` LIMIT 0, 1) AS `a` ON TRUE " .
            "LEFT JOIN LATERAL (SELECT post.id AS `id` FROM `post` LIMIT 0, 1) AS `b` ON TRUE";

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                Join::createWithSubQuery(
                    SelectBuilder::create()
                        ->select('id')
                        ->from('Post')
                        ->limit(0, 1)
                        ->withDeleted()
                        ->build(),
                    'a',
                )
                    ->withLateral()
                    ->withConditions(
                        Expression::value(true)
                    )
            )
            ->leftJoin(
                Join::createWithSubQuery(
                    SelectBuilder::create()
                        ->select('id')
                        ->from('Post')
                        ->limit(0, 1)
                        ->withDeleted()
                        ->build(),
                    'b',
                )
                    ->withLateral()
                    ->withConditions(
                        Expression::value(true)
                    )
            )
            ->withDeleted()
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($select)
        );
    }

    public function testJoinSubQueryInOn(): void
    {
        $sql =
            "SELECT post.id AS `id` FROM `post` " .
            "JOIN `post` AS `p` ON p.id IN (SELECT p1.id AS `id` FROM `post` AS `p1` LIMIT 0, 1)";

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                Join::createWithTableTarget('Post', 'p')
                    ->withConditions(
                        Condition::in(
                            Expression::column('p.id'),
                            SelectBuilder::create()
                                ->select('id')
                                ->from('Post', 'p1')
                                ->limit(0, 1)
                                ->withDeleted()
                                ->build()
                        )
                    )
            )
            ->withDeleted()
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($select)
        );
    }

    public function testJoinSubQueryException1(): void
    {
        $this->expectException(LogicException::class);

        $select = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->join(
                SelectBuilder::create()
                    ->select('id')
                    ->from('Post')
                    ->build(),
            )
            ->build();

        $this->query->composeSelect($select);
    }

    public function testWhereNotValue1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'name!=:' => 'post.id'
            ]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "WHERE post.name <> post.id AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereNotValue2()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'name:' => null
            ],
            'withDeleted' => true
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "WHERE post.name";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereNotValue3()
    {
        $sql = $this->query->composeSelect(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'name!=' => Expression::column('post.id')
            ]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "WHERE post.name <> post.id AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'post.id=s' => [
                    'entityType' => 'Post',
                    'selectParams' => [
                        'select' => ['id'],
                        'whereClause' => [
                            'name' => 'test'
                        ]
                    ]
                ]
            ]
        ]));

        $expectedSql = "SELECT post.id AS `id`, post.name AS `name` FROM `post` ".
            "WHERE post.id IN (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'post.id!=s' => [
                    'entityType' => 'Post',
                    'selectParams' => [
                        'select' => ['id'],
                        'whereClause' => [
                            'name' => 'test'
                        ]
                    ]
                ]
            ]
        ]));

        $expectedSql = "SELECT post.id AS `id`, post.name AS `name` FROM `post` ".
            "WHERE post.id NOT IN (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery2()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'post.id=s' => [
                    'from' => 'Post',
                    'select' => ['id'],
                    'whereClause' => [
                        'name' => 'test'
                    ]
                ]
            ]
        ]));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` ".
            "FROM `post` ".
            "WHERE post.id IN ".
            "(SELECT post.id AS `id` FROM `post` WHERE post.name = 'test' AND post.deleted = 0) AND ".
            "post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery3()
    {
        $subQuery = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Post',
            'select' => ['id', 'name'],
            'whereClause' => [
                'post.id=s' => $subQuery
            ]
        ]));

        $expectedSql = "SELECT post.id AS `id`, post.name AS `name` FROM `post` ".
            "WHERE post.id IN (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery4()
    {
        $subQuery = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Post')
                ->select('id')
                ->where(
                    Comparison::in(Expression::value('1'), $subQuery)
                )
                ->build()
        );

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` ".
            "WHERE '1' IN (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery5()
    {
        $subQuery = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Post')
                ->select('id')
                ->where(
                    Comparison::equal(Expression::value('1'), $subQuery)
                )
                ->build()
        );

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` ".
            "WHERE '1' = (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery6()
    {
        $subQuery = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Post')
                ->select('id')
                ->where(
                    Comparison::greaterOrEqual(Expression::value('1'), $subQuery)
                )
                ->build()
        );

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` ".
            "WHERE '1' >= (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery7()
    {
        $subQuery = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Post')
                ->select('id')
                ->where(
                    Comparison::greaterOrEqualAny(Expression::value('1'), $subQuery)
                )
                ->build()
        );

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` ".
            "WHERE '1' >= ANY (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery8()
    {
        $subQuery = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Post')
                ->select('id')
                ->where(
                    Comparison::greaterOrEqualAll(Expression::value('1'), $subQuery)
                )
                ->build()
        );

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` ".
            "WHERE '1' >= ALL (SELECT post.id AS `id` FROM `post` ".
            "WHERE post.name = 'test' AND post.deleted = 0) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectExists1(): void
    {
        $expectedSql =
            "SELECT post.id AS `id` FROM `post` AS `post` " .
            "WHERE EXISTS (" .
            "SELECT sq.id AS `id` FROM `post` AS `sq` WHERE sq.id = post.id AND sq.deleted = 0" .
            ") AND post.deleted = 0";

        $query = (new QueryBuilder())
            ->select('id')
            ->from('Post', 'post')
            ->where([
                'EXISTS' => (new QueryBuilder())
                    ->select('id')
                    ->from('Post', 'sq')
                    ->where(['sq.id:' => 'post.id'])
                    ->build()
            ])
            ->build();

        $sql = $this->query->composeSelect($query);
        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectExists2(): void
    {
        $expectedSql =
            "SELECT post.id AS `id` FROM `post` AS `post` " .
            "WHERE EXISTS (" .
            "SELECT sq.id AS `id` FROM `post` AS `sq` WHERE sq.id = post.id AND sq.deleted = 0" .
            ") AND post.deleted = 0";

        $query = (new QueryBuilder())
            ->select('id')
            ->from('Post', 'post')
            ->where([
                'EXISTS' => (new QueryBuilder())
                    ->select('id')
                    ->from('Post', 'sq')
                    ->where(['sq.id:' => 'post.id'])
                    ->build()
                    ->getRaw()
            ])
            ->build();

        $sql = $this->query->composeSelect($query);
        $this->assertEquals($expectedSql, $sql);
    }

    public function testNot1(): void
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Post',
                'select' => ['id', 'name'],
                'whereClause' => [
                    'NOT' => [
                        'name' => 'test',
                        'post.createdById' => '1',
                    ]
                ]
            ])
        );

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` ".
            "WHERE NOT (post.name = 'test' AND post.created_by_id = '1') AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testNot2(): void
    {
        $sql = $this->query->compose(
            Select::fromRaw([
                'from' => 'Post',
                'select' => ['id', 'name'],
                'whereClause' => [
                    'NOT' => [
                        'OR' => [
                            ['name' => 'test1'],
                            ['name' => 'test2'],
                        ]
                    ]
                ]
            ])
        );

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` ".
            "WHERE NOT (((post.name = 'test1') OR (post.name = 'test2'))) AND post.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testGroupBy()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['COUNT:id', 'QUARTER:comment.createdAt'],
            'groupBy' => ['QUARTER:comment.createdAt']
        ]));
        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, CONCAT(YEAR(comment.created_at), '_', ".
            "QUARTER(comment.created_at)) AS `QUARTER:comment.createdAt` FROM `comment` " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY CONCAT(YEAR(comment.created_at), '_', QUARTER(comment.created_at))";
        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['COUNT:id', 'YEAR_5:comment.createdAt'],
            'groupBy' => ['YEAR_5:comment.createdAt']
        ]));

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, CASE WHEN MONTH(comment.created_at) >= 6 ".
            "THEN YEAR(comment.created_at) ELSE YEAR(comment.created_at) - 1 END AS `YEAR_5:comment.createdAt` ".
            "FROM `comment` " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY CASE WHEN MONTH(comment.created_at) >= 6 THEN YEAR(comment.created_at) ".
            "ELSE YEAR(comment.created_at) - 1 END";
        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['COUNT:id', 'QUARTER_4:comment.createdAt'],
            'groupBy' => ['QUARTER_4:comment.createdAt']
        ]));

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, CASE WHEN MONTH(comment.created_at) >= 5 ".
            "THEN CONCAT(YEAR(comment.created_at), '_', FLOOR((MONTH(comment.created_at) - 5) / 3) + 1) ".
            "ELSE CONCAT(YEAR(comment.created_at) - 1, '_', CEIL((MONTH(comment.created_at) + 8) / 3)) ".
            "END AS `QUARTER_4:comment.createdAt` FROM `comment` " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY CASE WHEN MONTH(comment.created_at) >= 5 THEN CONCAT(YEAR(comment.created_at), '_', ".
            "FLOOR((MONTH(comment.created_at) - 5) / 3) + 1) ELSE CONCAT(YEAR(comment.created_at) - 1, '_', ".
            "CEIL((MONTH(comment.created_at) + 8) / 3)) END";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderBy1()
    {
        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Comment')
                ->select(['COUNT:id', 'YEAR:post.createdAt'])
                ->leftJoin('post')
                ->group('YEAR:post.createdAt')
                ->order(2)
                ->build()
        );

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, YEAR(post.created_at) AS `YEAR:post.createdAt` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY YEAR(post.created_at) ".
            "ORDER BY 2 ASC";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Comment')
                ->select(['COUNT:id', 'post.name'])
                ->leftJoin('post')
                ->group('post.name')
                ->order(Order::createByPositionInList(Expression::column('post.name'), ['Test', 'Hello']))
                ->build()
        );

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, post.name AS `post.name` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY post.name ".
            "ORDER BY FIELD(post.name, 'Hello', 'Test') DESC";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Comment')
                ->select(['COUNT:id', 'YEAR:post.createdAt', 'post.name'])
                ->leftJoin('post')
                ->group('YEAR:post.createdAt')
                ->group('post.name')
                ->order(2, Order::DESC)
                ->order(Order::createByPositionInList(Expression::column('post.name'), ['Test', 'Hello']))
                ->build()
        );

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, YEAR(post.created_at) AS `YEAR:post.createdAt`, ".
            "post.name AS `post.name` ".
            "FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "GROUP BY YEAR(post.created_at), post.name ".
            "ORDER BY 2 DESC, FIELD(post.name, 'Hello', 'Test') DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderByList1()
    {
        $select = $this->queryBuilder
            ->select('id')
            ->from('Comment')
            ->leftJoin('post')
            ->distinct()
            ->order('LIST:post.name:Test,Hello')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT DISTINCT comment.id AS `id`, post.name AS `post.name` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "ORDER BY FIELD(post.name, 'Hello', 'Test') DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderByList2()
    {
        $select = $this->queryBuilder
            ->select('id')
            ->from('Comment')
            ->leftJoin('post')
            ->order(
                Order::createByPositionInList(
                    Expression::column('post.name'),
                    [
                        'Test',
                        'Hello',
                    ]
                )
            )
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE comment.deleted = 0 " .
            "ORDER BY FIELD(post.name, 'Hello', 'Test') DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderByExpression1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Article')
            ->distinct()
            ->select(['id', 'name'])
            ->order('MATCH_BOOLEAN:(name,description,\'test\')', 'DESC')
            ->build();

        $expectedSql =
            "SELECT DISTINCT article.id AS `id`, article.name AS `name`, article.description AS `description` " .
            "FROM `article` WHERE article.deleted = 0 ".
            "ORDER BY MATCH (article.name, article.description) AGAINST ('test' IN BOOLEAN MODE) DESC";

        $sql = $this->query->compose($select);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderByExpression2()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Article')
            ->distinct()
            ->select(['id', ['name', 'name']])
            ->order([['MATCH_BOOLEAN:(name,description,\'test\')', 'DESC']])
            ->build();

        $expectedSql =
            "SELECT DISTINCT article.id AS `id`, article.name AS `name`, article.description AS `description` " .
            "FROM `article` WHERE article.deleted = 0 ".
            "ORDER BY MATCH (article.name, article.description) AGAINST ('test' IN BOOLEAN MODE) DESC";

        $sql = $this->query->compose($select);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderByExpression3()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Article')
            ->distinct()
            ->select(['id'])
            ->order(Expression::create('1'), 'DESC')
            ->build();

        $expectedSql =
            "SELECT DISTINCT article.id AS `id` " .
            "FROM `article` WHERE article.deleted = 0 ".
            "ORDER BY 1 DESC";

        $sql = $this->query->compose($select);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderBy2()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Article')
            ->distinct()
            ->select(['id', 'name'])
            ->order(1)
            ->order(2, 'DESC')
            ->withDeleted()
            ->build();

        $expectedSql =
            "SELECT DISTINCT article.id AS `id`, article.name AS `name` " .
            "FROM `article` ".
            "ORDER BY 1 ASC, 2 DESC";

        $sql = $this->query->compose($select);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testForeign()
    {
        $sql = $this->query->composeSelect(
            SelectBuilder::create()->from('Comment')
                ->select(['COUNT:comment.id', 'postId', 'postName'])
                ->leftJoin('post')
                ->group('postId')
                ->where(['post.createdById' => 'id_1'])
                ->build()
        );

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:comment.id`, comment.post_id AS `postId`, post.name AS `postName` ".
            "FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE post.created_by_id = 'id_1' AND comment.deleted = 0 " .
            "GROUP BY comment.post_id";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testForeign1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => [],
        ]));

        $this->assertTrue(strpos($sql, 'LEFT JOIN `post`') !== false);
    }

    public function testForeignInWhere()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => [
                'postName' => 'test',
            ],
            'withDeleted' => true,
        ]));

        $expectedSql =  "SELECT comment.id AS `id` FROM `comment` WHERE post.name = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInArray()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => array(
                'id' => ['id_1']
            ),
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE comment.id IN ('id_1') AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => array(
                'id!=' => ['id_1']
            ),
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE comment.id NOT IN ('id_1') AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => array(
                'id' => []
            ),
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE 0 AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => array(
                'name' => 'Test',
                'id!=' => []
            ),
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE comment.name = 'Test' AND 1 AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => [
                'MONTH_NUMBER:comment.created_at' => 2
            ]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE MONTH(comment.created_at) = 2 AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction2()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => [
                'WEEK_NUMBER_1:createdAt' => 2
            ]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE WEEK(comment.created_at, 3) = 2 AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction3()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => [
                'MONTH_NUMBER:(comment.created_at)' => 2
            ]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE MONTH(comment.created_at) = 2 AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction4()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => [
                "CONCAT:(MONTH:comment.created_at,' ',CONCAT:(comment.name,'+'))" => 'Test Hello'
            ]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE CONCAT(DATE_FORMAT(comment.created_at, '%Y-%m'), ' ', CONCAT(comment.name, '+')) = 'Test Hello' ".
            "AND comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction5()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', ['FLOOR:3.5', 'FLOOR:3.5']],
            'whereClause' => [
            ]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, FLOOR(3.5) AS `FLOOR:3.5` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction6()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', ['ROUND:3.5,1', 'ROUND:3.5,1']],
            'whereClause' => []
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, ROUND(3.5, 1) AS `ROUND:3.5,1` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction7()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', 'ROUND:3.5,1'],
            'whereClause' => []
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, ROUND(3.5, 1) AS `ROUND:3.5,1` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction8()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', ["CONCAT:(',test',\"+\",'\"', \"'\")", 'value']]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, CONCAT(',test', '+', '\"', '\\'') AS `value` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction9()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', ["COALESCE:(name,FALSE,true,null)", 'value']]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, COALESCE(comment.name, FALSE, TRUE, NULL) AS `value` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction10()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', ["IF:(LIKE:(name,'%test%'),'1','0')", 'value']]
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, IF(comment.name LIKE '%test%', '1', '0') AS `value` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction11()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => [["IS_NULL:(name)", 'value1'], ["IS_NOT_NULL:(name)", 'value2']],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT comment.name IS NULL AS `value1`, comment.name IS NOT NULL AS `value2` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction12()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ["IF:(OR:('1','0'),'1',' ')"],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT IF(('1' OR '0'), '1', ' ') AS `IF:(OR:('1','0'),'1',' ')` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction13()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ["IN:(name,'1','0')"],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT comment.name IN ('1', '0') AS `IN:(name,'1','0')` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction14()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ["NOT:(name)"],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT NOT comment.name AS `NOT:(name)` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction15()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ["MUL:(2,2.5,SUB:(3,1))"],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT (2 * 2.5 * (3 - 1)) AS `MUL:(2,2.5,SUB:(3,1))` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction16()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ["NOW:()"],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT NOW() AS `NOW:()` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction17()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => [["TIMESTAMPDIFF_YEAR:('2016-10-10', '2018-10-10')", 'test']],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT TIMESTAMPDIFF(YEAR, '2016-10-10', '2018-10-10') AS `test` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction18()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => [["IFNULL:(name, '')", 'test']],
            'withDeleted' => true
        ]));
        $expectedSql =
            "SELECT IFNULL(comment.name, '') AS `test` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction19()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => [['MUL:(id, 10)', 'test']],
            'withDeleted' => true,
        ]));
        $expectedSql =
            "SELECT (comment.id * 10) AS `test` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunctionTZ1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', "MONTH_NUMBER:TZ:(comment.created_at,-3.5)"],
            'whereClause' => []
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, MONTH(CONVERT_TZ(comment.created_at, '+00:00', '-03:30')) ".
            "AS `MONTH_NUMBER:TZ:(comment.created_at,-3.5)` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunctionTZ2()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id', "MONTH_NUMBER:TZ:(comment.created_at,0)"],
            'whereClause' => []
        ]));
        $expectedSql =
            "SELECT comment.id AS `id`, MONTH(CONVERT_TZ(comment.created_at, '+00:00', '+00:00')) ".
            "AS `MONTH_NUMBER:TZ:(comment.created_at,0)` FROM `comment` " .
            "WHERE comment.deleted = 0";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testHaving()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['COUNT:comment.id', 'postId', 'postName'],
            'leftJoins' => ['post'],
            'groupBy' => ['postId'],
            'whereClause' => [
                'post.createdById' => 'id_1'
            ],
            'havingClause' => [
                'COUNT:comment.id>' => 1
            ]
        ]));

        $sql = $this->query->composeSelect(
            SelectBuilder::create()
                ->from('Comment')
                ->select(['COUNT:comment.id', 'postId', 'postName'])
                ->leftJoin('post')
                ->where(['post.createdById' => 'id_1'])
                ->having(['COUNT:comment.id>' => 1])
                ->group('postId')
                ->build()
        );

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:comment.id`, comment.post_id AS `postId`, post.name AS `postName` " .
            "FROM `comment` LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 " .
            "WHERE post.created_by_id = 'id_1' AND comment.deleted = 0 " .
            "GROUP BY comment.post_id " .
            "HAVING COUNT(comment.id) > 1";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhere1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => ['id'],
            'whereClause' => [
                'post.createdById<=' => '1'
            ],
            'withDeleted' => true
        ]));

        $expectedSql =
            "SELECT comment.id AS `id` " .
            "FROM `comment` " .
            "WHERE post.created_by_id <= '1'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch1()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Article',
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_BOOLEAN:(name,description,\'test +hello\')',
                'id!=' => null
            ]
        ]));

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.name, article.description) AGAINST " .
            "('test +hello' IN BOOLEAN MODE) AND article.id IS NOT NULL AND article.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch2()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Article',
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_NATURAL_LANGUAGE:(description,"test hello")'
            ]
        ]));

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test hello' IN NATURAL LANGUAGE MODE) AND article.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch3()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Article',
            'select' => ['id', 'MATCH_BOOLEAN:(description,\'test\')'],
            'whereClause' => [
                'MATCH_BOOLEAN:(description,\'test\')'
            ],
            'orderBy' => [
                [2, 'DESC']
            ]
        ]));

        $expectedSql =
            "SELECT article.id AS `id`, ".
            "MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AS `MATCH_BOOLEAN:(description,'test')` ".
            "FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AND article.deleted = 0 " .
            "ORDER BY 2 DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch4()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Article',
            'select' => ['id', ['MATCH_BOOLEAN:(description,\'test\')', 'relevance']],
            'whereClause' => [
                'MATCH_BOOLEAN:(description,\'test\')'
            ],
            'orderBy' => [
                [2, 'DESC']
            ]
        ]));

        $expectedSql =
            "SELECT article.id AS `id`, MATCH (article.description) AGAINST ".
            "('test' IN BOOLEAN MODE) AS `relevance` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AND article.deleted = 0 " .
            "ORDER BY 2 DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch5()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Article',
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_NATURAL_LANGUAGE:(description,\'test\')>' => 1
            ]
        ]));

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN NATURAL LANGUAGE MODE) > 1 AND article.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch6()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Article',
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_NATURAL_LANGUAGE:(description,\'test\')'
            ]
        ]));

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN NATURAL LANGUAGE MODE) AND article.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testGetAllAttributesFromComplexExpression()
    {
        $expression = "CONCAT:(MONTH:comment.created_at,' ',CONCAT:(comment.name,'+'))";

        $list = $this->query::getAllAttributesFromComplexExpression($expression);

        $this->assertTrue(in_array('comment.created_at', $list));
        $this->assertTrue(in_array('comment.name', $list));
    }

    public function testGetAllAttributesFromComplexExpression1()
    {
        $expression = "test";
        $list = $this->query::getAllAttributesFromComplexExpression($expression);
        $this->assertTrue(in_array('test', $list));
    }

    public function testGetAllAttributesFromComplexExpression2()
    {
        $expression = "comment.test";
        $list = $this->query::getAllAttributesFromComplexExpression($expression);
        $this->assertTrue(in_array('comment.test', $list));
    }

    public function testGetAllAttributesFromComplexExpression3()
    {
        $expression = "SUM:(test, #test, sq.#test)";

        $list = Util::getAllAttributesFromComplexExpression($expression);

        $this->assertEquals(['test'], $list);
    }

    public function testComplexExpressionString1(): void
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select('"test\\"test"', 'test1')
            ->select("'test\\'test'", 'test2')
            ->withDeleted()
            ->build();

        $sql = $this->query->composeSelect($select);

        $expectedSql =
            "SELECT 'test\"test' AS `test1`, 'test\\'test' AS `test2` ".
            "FROM `test_where`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomWhere1()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                'test1' => "hello' test",
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "LEFT JOIN `test` AS `t` ON t.id = test_where.id ".
            "WHERE (" .
                "((test_where.test = 'hello\\' test') OR (test_where.test = '1') OR (test_where.test = LOWER('hello\\' test')))" .
            ")";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomWhere2()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                'test2' => 1,
                'test' => 2,
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE (test_where.test = 1 AND test_where.id IS NOT NULL) AND test_where.test = 2";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomWhere3()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                'test1' => ['hello', 'test'],
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE (test_where.test IN ('hello','test'))";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomWhere4()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                'test1!=' => ['hello', 'test'],
            ])
            ->build();

        $sql = $this->query->composeSelect($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE (test_where.id NOT IN ".
            "(SELECT test_where.id AS `id` FROM `test_where` WHERE test_where.test NOT IN ('hello','test')))";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomWhereLeftJoin()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                'test3' => 1,
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "LEFT JOIN `test` AS `t` ON t.id = test_where.id ".
            "WHERE (test_where.test = 1)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomOrder1()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->order('test1', 'DESC')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "LEFT JOIN `test` AS `t` ON t.id = test_where.id ".
            "ORDER BY test_where.test DESC, t.id DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomOrder2()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestSelect')
            ->select(['test'])
            ->order('test')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT (test_select.id * 1) AS `test` " .
            "FROM `test_select` " .
            "ORDER BY (alias.id * 1) ASC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomSelect1()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['test1'])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT (t.id * test_where.test) AS `test1` ".
            "FROM `test_where` ".
            "JOIN `test` AS `t` ON t.id = test_where.id";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomSelect2()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestSelect')
            ->select(['test'])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT (test_select.id * 1) AS `test` ".
            "FROM `test_select`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomSelect3()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestSelectRight')
            ->select(['left.test'])
            ->join('left')
            ->order('left.test')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT (left.id * 1) AS `left.test` ".
            "FROM `test_select_right` ".
            "JOIN `test_select` AS `left` ON test_select_right.left_id = left.id ".
            "ORDER BY (left.id * 1) ASC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testCustomSelect4()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestSelect')
            ->select(['testAnother'])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT (test_select.id * 1) AS `testAnother` ".
            "FROM `test_select`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereExpression1()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                "'value'" => 'test',
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE 'value' = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereExpression2()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                "2:" => 'test',
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE 2 = test_where.test";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereExpression3()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                "TRUE:" => 'test',
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE TRUE = test_where.test";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereExpression4()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('TestWhere')
            ->select(['id'])
            ->where([
                "test:" => '4',
            ])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT test_where.id AS `id` ".
            "FROM `test_where` ".
            "WHERE test_where.test = 4";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereExpression5(): void
    {
        $expectedSql =
            "SELECT post.id AS `id` FROM `post` " .
            "WHERE ((1 OR 0) AND 1)";

        $query = SelectBuilder::create()
            ->from('Post')
            ->select('id')
            ->where(
                Expression::and(
                    Expression::or(
                        Expression::value(1),
                        Expression::value(0)
                    ),
                    Expression::value(1)
                )
            )
            ->withDeleted()
            ->build();

        $sql = $this->query->composeSelect($query);
        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectValue1()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('Test')
            ->select(['VALUE:Hello Man'])
            ->withDeleted()
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT 'Hello Man' AS `VALUE:Hello Man` FROM `test`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectValue2()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('Test')
            ->select([['VALUE:Hello Man', 'value']])
            ->withDeleted()
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT 'Hello Man' AS `value` FROM `test`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectValue3()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->from('Test')
            ->select([['\'Hello Man\'', 'value']])
            ->withDeleted()
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT 'Hello Man' AS `value` FROM `test`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectSubQuery1()
    {
        $queryBuilder = new QueryBuilder();

        $subQuery = $queryBuilder
            ->select()
            ->select('\'test\'', 'test')
            ->build();

        $select = $queryBuilder->select()
            ->fromQuery($subQuery, 'a')
            ->select('COUNT:(a.id)', 'value')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT COUNT(a.id) AS `value` FROM (SELECT 'test' AS `test`) AS `a`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectSubQuery2()
    {
        $queryBuilder = new QueryBuilder();

        $subQuery = $queryBuilder
            ->select()
            ->select('\'test\'', 'test')
            ->build();

        $select = $queryBuilder->select()
            ->fromQuery($subQuery, 'a')
            ->select('a.id', 'value')
            ->join('Account', 'j', ['j.id:' => 'a.id'])
            ->where([
                'a.id' => '1',
            ])
            ->order('a.id')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT a.id AS `value` FROM (SELECT 'test' AS `test`) AS `a` ".
            "JOIN `account` AS `j` ON j.id = a.id ".
            "WHERE a.id = '1' " .
            "ORDER BY a.id ASC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectSubQuery3()
    {
        $queryBuilder = new QueryBuilder();

        $subQuery2 = $queryBuilder
            ->select()
            ->select('\'test\'', 'test')
            ->build();

        $subQuery1 = $queryBuilder
            ->select()
            ->fromQuery($subQuery2, 't')
            ->select('t.test', 'value')
            ->build();

        $query = $queryBuilder
            ->select()
            ->from('Test')
            ->select('id')
            ->where([
                'id=s' => $subQuery1->getRaw(),
            ])
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "SELECT test.id AS `id` FROM `test` " .
            "WHERE test.id IN " .
            "(SELECT t.test AS `value` FROM (SELECT 'test' AS `test`) AS `t`) AND " .
            "test.deleted = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectNoFrom1()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->select([['\'Hello Man\'', 'value']])
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT 'Hello Man' AS `value`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectNoFrom2()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->select("CONCAT:('Test', ' ', 'Hello')", 'value')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT CONCAT('Test', ' ', 'Hello') AS `value`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectNoFrom3()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->select("CONCAT:('Test', ' ', 'Hello')", 'value')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT CONCAT('Test', ' ', 'Hello') AS `value`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectNoFrom4()
    {
        $queryBuilder = new QueryBuilder();

        $select = $queryBuilder->select()
            ->select("CONCAT:(test, ' ', 'Hello')", 'value')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "SELECT CONCAT(__stub.test, ' ', 'Hello') AS `value`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUnion1()
    {
        $queryBuilder = new QueryBuilder();

        $q1 = $queryBuilder->select()
            ->select("'test1'", 'value')
            ->build();

        $q2 = $queryBuilder->select()
            ->select("'test2'", 'value')
            ->build();

        $select = $queryBuilder
            ->union()
            ->query($q1)
            ->query($q2)
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "(SELECT 'test1' AS `value`) ".
            "UNION ".
            "(SELECT 'test2' AS `value`)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectPair()
    {
        $sql = $this->query->compose(Select::fromRaw([
            'from' => 'Comment',
            'select' => [
                ['id', 'alias']
            ],
            'withDeleted' => true,
        ]));

        $expectedSql =  "SELECT comment.id AS `alias` FROM `comment`";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUnion2()
    {
        $queryBuilder = new QueryBuilder();

        $q1 = $queryBuilder->select()
            ->select("'test1'", 'value')
            ->build();

        $q2 = $queryBuilder->select()
            ->select("'test2'", 'value')
            ->build();

        $select = $queryBuilder
            ->union()
            ->all()
            ->query($q1)
            ->query($q2)
            ->limit(0, 2)
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "(SELECT 'test1' AS `value`) ".
            "UNION ALL ".
            "(SELECT 'test2' AS `value`) ".
            "LIMIT 0, 2";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUnionOrder1()
    {
        $queryBuilder = new QueryBuilder();

        $q1 = $queryBuilder->select()
            ->select("'test1'", 'value')
            ->build();

        $q2 = $queryBuilder->select()
            ->select("'test2'", 'value')
            ->build();

        $select = $queryBuilder
            ->union()
            ->all()
            ->query($q1)
            ->query($q2)
            ->limit(0, 0)
            ->order(1, 'DESC')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "(SELECT 'test1' AS `value`) ".
            "UNION ALL ".
            "(SELECT 'test2' AS `value`) ".
            "ORDER BY 1 DESC ".
            "LIMIT 0, 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUnionOrder2()
    {
        $queryBuilder = new QueryBuilder();

        $q1 = $queryBuilder->select()
            ->select("'test1'", 'value')
            ->build();

        $q2 = $queryBuilder->select()
            ->select("'test2'", 'value')
            ->build();

        $select = $queryBuilder
            ->union()
            ->all()
            ->query($q1)
            ->query($q2)
            ->order('value', 'DESC')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "(SELECT 'test1' AS `value`) ".
            "UNION ALL ".
            "(SELECT 'test2' AS `value`) ".
            "ORDER BY `value` DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testUnionOrder3()
    {
        $queryBuilder = new QueryBuilder();

        $q1 = $queryBuilder->select()
            ->select("'test1'", 'value1')
            ->select("'test2'", 'value2')
            ->build();

        $q2 = $queryBuilder->select()
            ->select("'test1'", 'value1')
            ->select("'test2'", 'value2')
            ->build();

        $select = $queryBuilder
            ->union()
            ->all()
            ->query($q1)
            ->query($q2)
            ->order('value1', 'DESC')
            ->order('value2')
            ->build();

        $sql = $this->query->compose($select);

        $expectedSql =
            "(SELECT 'test1' AS `value1`, 'test2' AS `value2`) ".
            "UNION ALL ".
            "(SELECT 'test1' AS `value1`, 'test2' AS `value2`) ".
            "ORDER BY `value1` DESC, `value2` ASC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectAlias1()
    {
        $qb = new QueryBuilder();

        $query = $qb->select()
            ->select('id')
            ->select('CONCAT:(id,name)', 'c')
            ->from('Account', 'a')
            ->join('SomeTable', 's', ['s.id:' => 'a.id'])
            ->where([
                'a.name' => 'Test',
            ])
            ->order('a.name')
            ->group(['id'])
            ->withDeleted()
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "SELECT a.id AS `id`, CONCAT(a.id, a.name) AS `c` ".
            "FROM `account` AS `a` ".
            "JOIN `some_table` AS `s` ON s.id = a.id ".
            "WHERE a.name = 'Test' ".
            "GROUP BY a.id ".
            "ORDER BY a.name ASC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectForUpdate()
    {
        $qb = new QueryBuilder();

        $query = $qb
            ->select()
            ->select('id')
            ->from('Account')
            ->withDeleted()
            ->forUpdate()
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "SELECT account.id AS `id` ".
            "FROM `account` ".
            "FOR UPDATE";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectForShare()
    {
        $qb = new QueryBuilder();

        $query = $qb
            ->select()
            ->select('id')
            ->from('Account')
            ->withDeleted()
            ->forShare()
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql =
            "SELECT account.id AS `id` ".
            "FROM `account` ".
            "LOCK IN SHARE MODE";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testLockTableShare()
    {
        $builder = new LockTableBuilder();

        $query = $builder
            ->table('Account')
            ->inShareMode()
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql = "LOCK TABLES `account` READ";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testLockTableExclusive1()
    {
        $builder = new LockTableBuilder();

        $query = $builder
            ->table('Account')
            ->inExclusiveMode()
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql = "LOCK TABLES `account` WRITE";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testLockTableExclusive2()
    {
        $builder = new LockTableBuilder();

        $query = $builder
            ->table('EntityTeam')
            ->inExclusiveMode()
            ->build();

        $sql = $this->query->compose($query);

        $expectedSql = "LOCK TABLES `entity_team` WRITE, `entity_team` AS `entityTeam` WRITE";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunctionConverter1(): void
    {
        $functionConverter = $this->createMock(FunctionConverter::class);

        $functionConverter
            ->expects($this->once())
            ->method('convert')
            ->with('1', '2')
            ->willReturn('TEST(1, 2)');

        $functionConverterFactory = $this->createMock(FunctionConverterFactory::class);

        $functionConverterFactory
            ->expects($this->once())
            ->method('isCreatable')
            ->with('TEST_HELLO')
            ->willReturn(true);

        $functionConverterFactory
            ->expects($this->once())
            ->method('create')
            ->with('TEST_HELLO')
            ->willReturn($functionConverter);

        $query = (new QueryBuilder())
            ->select('id')
            ->from('Account')
            ->where([
                'TEST_HELLO:(1, 2)' => 'test'
            ])
            ->withDeleted()
            ->build();

        $composer = new QueryComposer($this->pdo, $this->entityFactory, $this->metadata, $functionConverterFactory);

        $sql = $composer->compose($query);

        $expectedSql =
            "SELECT account.id AS `id` ".
            "FROM `account` ".
            "WHERE TEST(1, 2) = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunctionConverterNoFunction(): void
    {
        $functionConverterFactory = $this->createMock(FunctionConverterFactory::class);

        $functionConverterFactory
            ->expects($this->once())
            ->method('isCreatable')
            ->with('TEST_HELLO')
            ->willReturn(false);
        $query = (new QueryBuilder())
            ->select('id')
            ->from('Account')
            ->where([
                'TEST_HELLO:(1, 2)' => 'test'
            ])
            ->withDeleted()
            ->build();

        $composer = new QueryComposer($this->pdo, $this->entityFactory, $this->metadata, $functionConverterFactory);

        $this->expectException(RuntimeException::class);

        $composer->compose($query);
    }

    public function testSelect1(): void
    {
        $sql =
            "SELECT post.id AS `id`, post.name AS `name`, " .
                "NULLIF(TRIM(CONCAT(COALESCE(createdBy.salutation_name, ''), " .
            "COALESCE(createdBy.first_name, ''), ' ', COALESCE(createdBy.last_name, ''))), '') AS `createdByName`, " .
            "post.created_by_id AS `createdById`, post.deleted AS `deleted` ".
            "FROM `post` AS `post` ".
            "LEFT JOIN `user` AS `createdBy` ON post.created_by_id = createdBy.id " .
            "JOIN `post_tag` AS `tagsMiddle` ON post.id = tagsMiddle.post_id AND tagsMiddle.deleted = 0 ".
            "JOIN `tag` AS `tags` ON tags.id = tagsMiddle.tag_id AND tags.deleted = 0 ".
            "JOIN `comment` AS `comments` ON post.id = comments.post_id AND comments.deleted = 0 ".
            "WHERE post.name = 'test_1' AND (post.id = '100' OR post.name LIKE 'test_%') AND " .
                "tags.name = 'yoTag' AND post.deleted = 0 ".
            "ORDER BY post.name DESC ".
            "LIMIT 0, 10";

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

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testSelect2(): void
    {
        $sql =
            "SELECT contact.id AS `id`, TRIM(CONCAT(contact.first_name, ' ', contact.last_name)) AS `name`, " .
            "contact.first_name AS `firstName`, contact.last_name AS `lastName`, contact.deleted AS `deleted` ".
            "FROM `contact` AS `contact` ".
            "WHERE " .
            "(contact.first_name LIKE 'test%' OR contact.last_name LIKE 'test%' OR ".
            "CONCAT(contact.first_name, ' ', contact.last_name) LIKE 'test%') ".
            "AND contact.deleted = 0 ".
            "ORDER BY contact.first_name DESC, contact.last_name DESC ".
            "LIMIT 0, 10";

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

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testSelect3(): void
    {
        $sql =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, " .
                "post.name AS `postName`, comment.name AS `name`, " .
            "comment.deleted AS `deleted` ".
            "FROM `comment` AS `comment` ".
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id AND post.deleted = 0 ".
            "WHERE comment.deleted = 0";

        $query = Select::fromRaw([
            'from' => 'Comment',
            'fromAlias' => 'comment',
        ]);

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testSelectJoinConditions1(): void
    {
        $sql =
            "SELECT team.id AS `id`, team.name AS `name`, team.deleted AS `deleted`, entityTeam.team_id AS `stub` " .
            "FROM `team` AS `team` ".
            "JOIN `entity_team` AS `entityTeam` ON entityTeam.team_id = team.id AND entityTeam.entity_id = '1' AND " .
            "entityTeam.deleted = 0 AND entityTeam.entity_type = 'Account' WHERE team.deleted = 0";

        $query = SelectBuilder::create()
            ->from('Team', 'team')
            ->select([
                '*',
                ['entityTeam.teamId', 'stub'],
            ])
            ->join('EntityTeam', 'entityTeam', [
                'teamId:' => 'id',
                'entityId' => '1',
                'deleted' => false,
                'entityType' => 'Account',
            ])
            ->where([])
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testSwitch1(): void
    {
        $sql = "SELECT CASE WHEN 1 THEN '1' WHEN 0 THEN '0' ELSE NULL END AS `value`";

        $query = SelectBuilder::create()
            ->select('SWITCH:(1, "1", 0, "0", null)', 'value')
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testSwitch2(): void
    {
        $sql = "SELECT CASE WHEN 1 THEN '1' WHEN 0 THEN '0' END AS `value`";

        $query = SelectBuilder::create()
            ->select('SWITCH:(1, "1", 0, "0")', 'value')
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testMap1(): void
    {
        $sql =
            "SELECT CASE post.number WHEN 1 THEN '1' WHEN 0 THEN '0' ELSE NULL END AS `value` " .
            "FROM `post` WHERE post.deleted = 0";

        $query = SelectBuilder::create()
            ->select('MAP:(post.number, 1, "1", 0, "0", null)', 'value')
            ->from('Post')
            ->build();

        $this->assertEquals(
            $sql,
            $this->query->composeSelect($query)
        );
    }

    public function testSelections1(): void
    {
        $query1 = SelectBuilder::create()
            ->select(['number'])
            ->from('Post')
            ->build();

        $query2 = SelectBuilder::create()
            ->select($query1->getSelect())
            ->from('Post')
            ->build();

        $this->assertEquals(
            $this->query->composeSelect($query1),
            $this->query->composeSelect($query2)
        );
    }

    public function testStringWithColon(): void
    {
        $query = SelectBuilder::create()
            ->select(["'2020-10-10 20:20:20'"])
            ->from('Post')
            ->build();

        $sql = $this->query->composeSelect($query);

        $this->assertIsString($sql);
    }

    public function testAlias1(): void
    {
        $query = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->where(['subQuery.#someId' => '1'])
            ->withDeleted()
            ->build();

        $expectedSql = "SELECT post.id AS `id` FROM `post` WHERE subQuery.someId = '1'";

        $sql = $this->query->composeSelect($query);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testAlias2(): void
    {
        $query = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->where(['#someId' => '1'])
            ->withDeleted()
            ->build();

        $expectedSql = "SELECT post.id AS `id` FROM `post` WHERE someId = '1'";

        $sql = $this->query->composeSelect($query);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testAlias3(): void
    {
        $query = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->leftJoin(
                'Test',
                'test',
                ['test.#testId' => '1']
            )
            ->withDeleted()
            ->build();

        $expectedSql = "SELECT post.id AS `id` FROM `post` LEFT JOIN `test` AS `test` ON test.testId = '1'";

        $sql = $this->query->composeSelect($query);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testAlias4(): void
    {
        $subQuery =
            SelectBuilder::create()
                ->select('id', 'someId')
                ->from('Test')
                ->withDeleted()
                ->build();

        $query = SelectBuilder::create()
            ->select('id')
            ->from('Post')
            ->leftJoin(
                Join::createWithSubQuery($subQuery, 'sqAlias')
                    ->withConditions(
                        Condition::equal(
                            Expression::alias('sqAlias.someId'),
                            Expression::column('id')
                        )
                    )
            )
            ->withDeleted()
            ->build();

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` LEFT JOIN " .
            "(SELECT test.id AS `someId` FROM `test`) AS `sqAlias` ON sqAlias.someId = post.id";

        $sql = $this->query->composeSelect($query);

        $this->assertEquals($expectedSql, $sql);
    }
}
