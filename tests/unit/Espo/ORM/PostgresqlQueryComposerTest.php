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

use Espo\ORM\EntityFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Metadata;
use Espo\ORM\MetadataDataProvider;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Query\InsertBuilder;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Join;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\UpdateBuilder;
use Espo\ORM\QueryBuilder;
use Espo\ORM\QueryComposer\PostgresqlQueryComposer as QueryComposer;
use PHPUnit\Framework\TestCase;

require_once 'tests/unit/testData/DB/Entities.php';
require_once 'tests/unit/testData/DB/MockPDO.php';
require_once 'tests/unit/testData/DB/MockDBResult.php';

class PostgresqlQueryComposerTest extends TestCase
{
    private ?QueryComposer $queryComposer;
    private ?EntityManager $entityManager;

    private $queryBuilder;

    protected function setUp(): void
    {
        $ormMetadata = include('tests/unit/testData/DB/ormMetadata.php');

        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($ormMetadata);

        $metadata = new Metadata($metadataDataProvider);

        $this->queryBuilder = new QueryBuilder();

        $pdo = $this->createMock('MockPDO');
        $pdo
            ->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function() {
                $args = func_get_args();

                return "'" . $args[0] . "'";
            });

        $this->entityManager = $this->createMock(EntityManager::class);
        $entityFactory = $this->createMock(EntityFactory::class);

        $entityFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () use ($metadata) {
                    $args = func_get_args();
                    $className = "tests\\unit\\testData\\DB\\" . $args[0];
                    $defs = $metadata->get($args[0]) ?? [];

                    return new $className($args[0], $defs, $this->entityManager);
                }
            );

        $this->queryComposer = new QueryComposer($pdo, $entityFactory, $metadata);
    }

    public function testUpdate1(): void
    {
        $query = UpdateBuilder::create()
            ->in('Comment')
            ->set(['name' => '1'])
            ->where(['name' => 'post.name'])
            ->join('post')
            ->limit(1)
            ->order('name')
            ->build();

        $sql = $this->queryComposer->composeUpdate($query);

        $expectedSql =
            'UPDATE "comment" SET "name" = \'1\' WHERE "comment"."id" IN ' .
            '(SELECT "comment"."id" AS "id" FROM "comment" ' .
            'JOIN "post" AS "post" ON "comment"."post_id" = "post"."id" AND "post"."deleted" = false ' .
            'WHERE "comment"."name" = \'post.name\' AND "comment"."deleted" = false ' .
            'ORDER BY "comment"."name" ASC LIMIT 1 OFFSET 0 FOR UPDATE)';

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelect1()
    {
        $query = SelectBuilder::create()
            ->from('Comment')
            ->select(['name', 'postId', 'postName'])
            ->build();

        $sql = $this->queryComposer->composeSelect($query);

        $expectedSql =
            'SELECT "comment"."name" AS "name", "comment"."post_id" AS "postId", "post"."name" AS "postName" ' .
            'FROM "comment" LEFT JOIN "post" AS "post" ON "comment"."post_id" = "post"."id" ' .
            'AND "post"."deleted" = false ' .
            'WHERE "comment"."deleted" = false';

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectForUpdate1()
    {
        $query = SelectBuilder::create()
            ->from('Comment')
            ->select(['name', 'postId', 'postName'])
            ->forUpdate()
            ->build();

        $sql = $this->queryComposer->composeSelect($query);

        $expectedSql =
            'SELECT "comment"."name" AS "name", "comment"."post_id" AS "postId" ' .
            'FROM "comment" WHERE "comment"."deleted" = false FOR UPDATE';

        $this->assertEquals($expectedSql, $sql);
    }

    public function testDelete1(): void
    {
        $query = DeleteBuilder::create()
            ->from('Account')
            ->where(['name' => 'test'])
            ->build();

        $sql = $this->queryComposer->composeDelete($query);

        $expectedSql =
            "DELETE FROM \"account\" " .
            "WHERE \"account\".\"name\" = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testDelete2(): void
    {
        $query = DeleteBuilder::create()
            ->from('Comment')
            ->join('post')
            ->where(['name' => 'post.name'])
            ->limit(1)
            ->order('name')
            ->build();

        $sql = $this->queryComposer->composeDelete($query);

        $expectedSql =
            'DELETE FROM "comment" WHERE "comment"."id" IN ' .
            '(SELECT "comment"."id" AS "id" FROM "comment" ' .
            'JOIN "post" AS "post" ON "comment"."post_id" = "post"."id" AND "post"."deleted" = false ' .
            'WHERE "comment"."name" = \'post.name\' AND "comment"."deleted" = false ' .
            'ORDER BY "comment"."name" ASC LIMIT 1 OFFSET 0)';

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInsertUpdate1(): void
    {
        $query = InsertBuilder::create()
            ->into('PostTag')
            ->columns(['id', 'postId', 'tagId'])
            ->values([
                'id' => '1',
                'postId' => 'post-id',
                'tagId' => 'tag-id',
            ])
            ->updateSet([
                'deleted' => 0
            ])
            ->build();

        $sql = $this->queryComposer->composeInsert($query);

        $expectedSql =
            "INSERT INTO \"post_tag\" (\"id\", \"post_id\", \"tag_id\") VALUES ('1', 'post-id', 'tag-id') " .
            "ON CONFLICT(\"post_id\", \"tag_id\") DO UPDATE SET \"deleted\" = 0";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testInsertUpdate2(): void
    {
        $query = InsertBuilder::create()
            ->into('Account')
            ->columns(['id', 'name'])
            ->values([
                'id' => '1',
                'name' => 'name',
            ])
            ->updateSet([
                'deleted' => 0
            ])
            ->build();

        $sql = $this->queryComposer->composeInsert($query);

        $expectedSql =
            "INSERT INTO \"account\" (\"id\", \"name\") VALUES ('1', 'name') " .
            "ON CONFLICT(\"id\") DO UPDATE SET \"deleted\" = 0";

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
            "SELECT \"post\".\"id\" AS \"id\" FROM \"post\" LEFT JOIN " .
            "(SELECT \"test\".\"id\" AS \"someId\" FROM \"test\") AS \"sqAlias\" ON \"sqAlias\".\"someId\" = \"post\".\"id\"";

        $sql = $this->queryComposer->composeSelect($query);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectFunctionTz1()
    {
        $query = SelectBuilder::create()
            ->from('Comment')
            ->select(
                Expression::convertTimezone(
                    Expression::column('createdAt'),
                    5.5
                ),
                'createdAt'
            )
            ->withDeleted()
            ->build();

        $sql = $this->queryComposer->composeSelect($query);

        $expectedSql =
            'SELECT "comment"."created_at" + INTERVAL \'330 MINUTE\' AS "createdAt" ' .
            'FROM "comment"';

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectFunctionTz2()
    {
        $query = SelectBuilder::create()
            ->from('Comment')
            ->select(
                Expression::convertTimezone(
                    Expression::column('createdAt'),
                    -5.5
                ),
                'createdAt'
            )
            ->withDeleted()
            ->build();

        $sql = $this->queryComposer->composeSelect($query);

        $expectedSql =
            'SELECT "comment"."created_at" + INTERVAL \'-330 MINUTE\' AS "createdAt" ' .
            'FROM "comment"';

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectFunctionTz3()
    {
        $query = SelectBuilder::create()
            ->from('Comment')
            ->select(
                Expression::convertTimezone(
                    Expression::column('createdAt'),
                    5
                ),
                'createdAt'
            )
            ->withDeleted()
            ->build();

        $sql = $this->queryComposer->composeSelect($query);

        $expectedSql =
            'SELECT "comment"."created_at" + INTERVAL \'5 HOUR\' AS "createdAt" ' .
            'FROM "comment"';

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

        $sql = $this->queryComposer->compose($query);

        $expectedSql =
            "DELETE FROM \"account\" AS \"a\" " .
            "WHERE \"a\".\"name\" = 'test'";

        $this->assertEquals($expectedSql, $sql);
    }
}
