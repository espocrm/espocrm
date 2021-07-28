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

namespace tests\unit\Espo\ORM\Repository;

require_once 'tests/unit/testData/DB/Entities.php';

use Espo\ORM\{
    Mapper\MysqlMapper,
    Repository\RDBRepository as Repository,
    Repository\RDBRelation,
    Repository\RDBRelationSelectBuilder,
    EntityCollection,
    Query\Select,
    QueryBuilder,
    Entity,
    EntityManager,
    EntityFactory,
    SthCollection,
    CollectionFactory,
    Metadata,
    MetadataDataProvider,
    Query\Part\Condition as Cond,
    Query\Part\Expression as Expr,
    Query\Part\Order as OrderExpr,
};

use RuntimeException;

use tests\unit\testData\Entities\Test;

use tests\unit\testData\DB as Entities;

class RDBRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Repository
     */
    private $repository;

    protected function setUp(): void
    {
        $entityManager = $this->entityManager =
            $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $entityFactory = $this->entityFactory =

        $this->getMockBuilder(EntityFactory::class)->disableOriginalConstructor()->getMock();

        $this->collectionFactory = new CollectionFactory($this->entityManager);

        $this->mapper = $this->getMockBuilder(MysqlMapper::class)->disableOriginalConstructor()->getMock();

        $entityManager
            ->method('getMapper')
            ->will($this->returnValue($this->mapper));

        $this->queryBuilder = new QueryBuilder();

        $entityManager
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $entityManager
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $entityManager
            ->method('getCollectionFactory')
            ->will($this->returnValue($this->collectionFactory));

        $entityManager
            ->method('getEntityFactory')
            ->will($this->returnValue($this->entityFactory));

        $ormMetadata = include('tests/unit/testData/DB/ormMetadata.php');

        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($ormMetadata);

        $this->metadata = new Metadata($metadataDataProvider);

        $this->seed = $this->createEntity('Test', Test::class);

        $this->account = $this->createEntity('Account', Entities\Account::class);

        $this->team = $this->createEntity('Team', Entities\Team::class);

        $this->collection = $this->createCollectionMock();

        $entityFactory
            ->method('create')
            ->will(
                $this->returnCallback(
                    function (string $entityType) {
                        $className = 'tests\\unit\\testData\\DB\\' . ucfirst($entityType);

                        return $this->createEntity($entityType, $className);
                    }
                )
            );

        $this->repository = $this->createRepository('Test');
    }

    protected function createCollectionMock(?array $itemList = null) : SthCollection
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

    protected function createRepository(string $entityType)
    {
        $repository = new Repository($entityType, $this->entityManager, $this->entityFactory);

        $this->entityManager
            ->method('getRepository')
            ->will($this->returnValue($repository));

        return $repository;
    }

    protected function createEntity(string $entityType, string $className)
    {
        $defs = $this->metadata->get($entityType);

        return new $className($entityType, $defs, $this->entityManager);
    }

    /**
     * @deprecated
     */
    public function testFind()
    {
        $params = [
            'whereClause' => [
                'name' => 'test',
            ],
        ];

        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name' => 'test',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->find($params);
    }

    public function testFindOne1()
    {
        $params = [
            'whereClause' => [
                'name' => 'test',
            ],
        ];

        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name' => 'test',
            ],
            'offset' => 0,
            'limit' => 1,
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->findOne($params);
    }

    public function testFindOne2()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->where(['name' => 'test'])
            ->limit(0, 1)
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($select);

        $this->repository->where(['name' => 'test'])->findOne();
    }

    public function testFindOne3()
    {
        $select = $this->queryBuilder
            ->select()
            ->distinct()
            ->from('Test')
            ->where(['name' => 'test'])
            ->limit(0, 1)
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($select);

        $this->repository->distinct()->findOne([
            'whereClause' => ['name' => 'test'],
        ]);
    }

    /**
     * @deprecated
     */
    public function testCount1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->where(['name' => 'test'])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1))
            ->with($select);

        $this->repository->count([
            'whereClause' => ['name' => 'test'],
        ]);
    }

    public function testCount2()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->where(['name' => 'test'])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1))
            ->with($select);

        $this->repository->where(['name' => 'test'])->count();
    }

    public function testCount3()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1))
            ->with($select);

        $this->repository->count();
    }

    public function testMax1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('max')
            ->will($this->returnValue(1))
            ->with($select, 'test');

        $this->repository->max('test');
    }

    public function testWhere1()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name' => 'test',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->where(['name' => 'test'])->find();
    }

    public function testWhere2()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name' => 'test',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->where('name', 'test')->find();
    }

    public function testWhere3()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name=' => 'test',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->where(Cond::equal(Expr::column('name'), 'test'))
            ->find();
    }

    public function testWhereMerge()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name2' => 'test2',
                ['name1' => 'test1'],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->where(['name1' => 'test1'])
            ->find([
                'whereClause' => ['name2' => 'test2'],
            ]);
    }

    public function testWhereFineOne()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'whereClause' => [
                'name' => 'test',
            ],
            'offset' => 0,
            'limit' => 1,
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->where('name', 'test')->findOne();
    }

    public function testJoin1()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'joins' => [
                'Test',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->join('Test')->find();
    }

    public function testJoin2()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'joins' => [
                'Test1',
                'Test2',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->join(['Test1', 'Test2'])->find();
    }

    public function testJoin3()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'joins' => [
                ['Test1', 'test1'],
                ['Test2', 'test2'],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->join([['Test1', 'test1'], ['Test2', 'test2']])->find();
    }

    public function testJoin4()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'joins' => [
                ['Test1', 'test1', ['k' => 'v']],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->join('Test1', 'test1', ['k' => 'v'])->find();
    }

    public function testJoin5()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'joins' => [
                ['Test1', 'test1', ['k=' => 'v']],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->join('Test1', 'test1', Cond::equal(Expr::column('k'), 'v'))
            ->find();
    }

    public function testLeftJoin1()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'leftJoins' => [
                'Test',
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->leftJoin('Test')->find();
    }

    public function testLeftJoin2()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'leftJoins' => [
                ['Test1', 'test1', ['k=' => 'v']],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->leftJoin('Test1', 'test1', Cond::equal(Expr::column('k'), 'v'))
            ->find();
    }

    public function testMultipleLeftJoins()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'leftJoins' => [
                'Test1',
                ['Test2', 'test2'],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->leftJoin('Test1')->leftJoin('Test2', 'test2')->find();
    }

    public function testDistinct()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'distinct' => true,
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->distinct()->find();
    }

    public function testForUpdate()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'forUpdate' => true,
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->forUpdate()->find();
    }

    public function testSth()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            //'returnSthCollection' => true,
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->sth()->find();
    }

    public function testOrder1()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'orderBy' => [['name', 'ASC']],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->order('name')
            ->find();
    }

    public function testOrder2()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'orderBy' => [['name', 'ASC']],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->order(Expr::create('name'))
            ->find();
    }

    public function testOrder3()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'orderBy' => [['name', 'ASC']],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->order([
                [Expr::create('name'), 'ASC']
            ])
            ->find();
    }

    public function testOrder4()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'orderBy' => [
                ['name', 'ASC'],
                ['hello', 'DESC'],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->order([
                OrderExpr::fromString('name'),
                OrderExpr::fromString('hello')->withDesc(),
            ])
            ->find();
    }

    public function testOrder5()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'orderBy' => [
                ['name', 'ASC'],
                ['hello', 'DESC'],
            ],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->order(OrderExpr::fromString('name'))
            ->order(OrderExpr::fromString('hello')->withDesc())
            ->find();
    }

    public function testGroupBy1()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'groupBy' => ['id'],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->group('id')->find();
    }

    public function testGroupBy2()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'groupBy' => ['id', 'name'],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->group('id')
            ->group('name')
            ->find();
    }

    public function testGroupBy3()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'groupBy' => ['id', 'name'],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->group('id')
            ->group(['id', 'name'])
            ->find();
    }

    public function testGroupBy4()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'groupBy' => ['id', 'name'],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->group(Expr::create('id'))
            ->group(Expr::create('name'))
            ->find();
    }

    public function testGroupBy5()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'groupBy' => ['id', 'name'],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository
            ->group([Expr::create('id'), Expr::create('name')])
            ->find();
    }

    public function testSelect1()
    {
        $paramsExpected = Select::fromRaw([
            'from' => 'Test',
            'select' => ['name', 'date'],
        ]);

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($paramsExpected);

        $this->repository->select(['name', 'date'])->find();
    }

    public function testSelect2()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->select(['name'])
            ->select('date')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($select);

        $this->repository
            ->select(['name'])
            ->select('date')
            ->find();
    }

    public function testSelect3()
    {
        $select = $this->queryBuilder
            ->select(Expr::create('name'))
            ->from('Test')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($select);

        $this->repository
            ->select(['name'])
            ->find();
    }

    public function testSelect4()
    {
        $select = $this->queryBuilder
            ->select([
                'name1',
                ['name2', 'alias'],
                ['name3'],
            ])
            ->from('Test')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($select);

        $this->repository
            ->select([
                Expr::create('name1'),
                [Expr::create('name2'), 'alias'],
                [Expr::create('name3')],
            ])
            ->find();
    }

    public function testFindRelated1()
    {
        $select = Select::fromRaw([
            'from' => 'Team',
        ]);

        $this->account->id = 'accountId';

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue(new EntityCollection()))
            ->with($this->account, 'teams', $select);

        $this->createRepository('Account')->findRelated($this->account, 'teams');
    }

    public function testCountRelated1()
    {
        $select = Select::fromRaw([
            'from' => 'Team',
        ]);

        $this->account->id = 'accountId';

        $this->mapper
            ->expects($this->once())
            ->method('countRelated')
            ->will($this->returnValue(1))
            ->with($this->account, 'teams', $select);

        $this->createRepository('Account')->countRelated($this->account, 'teams');
    }

    public function testAdditionalColumns()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->select(['*', ['entityTeam.deleted', 'teamDeleted']])
            ->build();

        $this->account->id = 'accountId';

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue(new EntityCollection()))
            ->with($this->account, 'teams', $select);

        $this->createRepository('Account')->findRelated($this->account, 'teams', [
            'additionalColumns' => [
                'deleted' => 'teamDeleted',
            ],
        ]);
    }

    public function testAdditionalColumnsConditions()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where([
                'entityTeam.teamId' => 'testId',
            ])
            ->build();

        $this->account->id = 'accountId';

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue(new EntityCollection()))
            ->with($this->account, 'teams', $select);

        $this->createRepository('Account')->findRelated($this->account, 'teams', [
            'additionalColumnsConditions' => [
                'teamId' => 'testId',
            ],
        ]);
    }

    public function testClone1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->build();

        $selectExpected = $this->queryBuilder
            ->select()
            ->from('Test')
            ->select('id')
            ->build();

        $collection = $this->createCollectionMock();

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($collection))
            ->with($selectExpected);

        $this->repository
            ->clone($select)
            ->select('id')
            ->find();
    }

    public function testGetById1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->where(['id' => '1'])
            ->build();

        $entity = $this->getMockBuilder(Entity::class)->getMock();

        $this->mapper
            ->expects($this->once())
            ->method('selectOne')
            ->will($this->returnValue($entity))
            ->with($select);

        $this->repository->getById('1');
    }

    public function testRelationInstance()
    {
        $repository = $this->createRepository('Account');

        $account = $this->entityFactory->create('Account');
        $account->id = 'accountId';

        $relation = $repository->getRelation($account, 'teams');

        $this->assertInstanceOf(RDBRelation::class, $relation);
    }

    public function testRelationCloneInstance()
    {
        $repository = $this->createRepository('Account');

        $account = $this->entityFactory->create('Account');
        $account->id = 'accountId';

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->build();

        $relationSelectBuilder = $repository->getRelation($account, 'teams')->clone($select);

        $this->assertInstanceOf(RDBRelationSelectBuilder::class, $relationSelectBuilder);
    }

    public function testRelationCloneBelongsToParentException()
    {
        $repository = $this->createRepository('Note');

        $note = $this->entityFactory->create('Note');
        $note->id = 'noteId';

        $select = $this->queryBuilder
            ->select()
            ->from('Post')
            ->build();

        $this->expectException(RuntimeException::class);

        $relationSelectBuilder = $repository->getRelation($note, 'parent')->clone($select);
    }

    public function testRelationCount()
    {
        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';

        $select = $this->queryBuilder
            ->select()
            ->from('Comment')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('countRelated')
            ->will($this->returnValue(1))
            ->with($post, 'comments', $select);

        $this->createRepository('Post')->getRelation($post, 'comments')->count();
    }

    public function testRelationFindHasMany()
    {
        $repository = $this->createRepository('Post');

        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Comment')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($post, 'comments', $select);

        $repository->getRelation($post, 'comments')->find();
    }

    public function testRelationFindBelongsTo()
    {
        $comment = $this->entityFactory->create('Comment');
        $comment->id = 'commentId';

        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';

        $select = $this->queryBuilder
            ->select()
            ->from('Post')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($post))
            ->with($comment, 'post', $select);

        $result = $this->createRepository('Comment')
            ->getRelation($comment, 'post')
            ->find();

        $this->assertEquals(1, count($result));

        $this->assertEquals($post, $result[0]);
    }

    public function testRelationFindBelongsToParent()
    {
        $note = $this->entityFactory->create('Note');
        $note->id = 'noteId';

        $post = $this->entityFactory->create('Post');
        $post->id = 'noteId';

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($post))
            ->with($note, 'parent');

        $result = $this->createRepository('Note')->getRelation($note, 'parent')->find();

        $this->assertEquals(1, count($result));

        $this->assertEquals($post, $result[0]);
    }

    public function testRelationFindOneBelongsToParent()
    {
        $note = $this->entityFactory->create('Note');
        $note->id = 'noteId';

        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($post))
            ->with($note, 'parent');

        $result = $this->createRepository('Note')->getRelation($note, 'parent')->findOne();

        $this->assertEquals($post, $result);
    }

    public function testRelationIsRelated1()
    {
        $note = $this->entityFactory->create('Note');
        $note->set('id', 'noteId');

        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $note->set('parentId', $post->id);
        $note->set('parentType', 'Post');

        $result = $this->createRepository('Note')->getRelation($note, 'parent')->isRelated($post);

        $this->assertTrue($result);

        $note->set('parentId', 'anotherId');
        $note->set('parentType', 'Post');

        $result = $this->createRepository('Note')->getRelation($note, 'parent')->isRelated($post);

        $this->assertFalse($result);
    }

    public function testRelationIsRelated2()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $note = $this->entityFactory->create('Note');
        $note->set('id', 'noteId');

        $collection = $this->createCollectionMock([$note]);

        $select = $this->queryBuilder
            ->select()
            ->from('Note')
            ->select(['id'])
            ->where(['id' => $note->id])
            ->limit(0, 1)
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($post, 'notes', $select);

        $result = $this->createRepository('Post')->getRelation($post, 'notes')->isRelated($note);

        $this->assertTrue($result);
    }

    public function testRelationIsRelated3()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $note = $this->entityFactory->create('Note');
        $note->set('id', 'noteId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Note')
            ->select(['id'])
            ->where(['id' => $note->id])
            ->limit(0, 1)
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($post, 'notes', $select);

        $result = $this->createRepository('Post')->getRelation($post, 'notes')->isRelated($note);

        $this->assertFalse($result);
    }

    public function testRelate1()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $note = $this->entityFactory->create('Note');
        $note->set('id', 'noteId');

        $this->mapper
            ->expects($this->once())
            ->method('relate')
            ->with($post, 'notes', $note);

        $this->createRepository('Post')->getRelation($post, 'notes')->relate($note);
    }

    public function testUnrelate1()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $note = $this->entityFactory->create('Note');
        $note->set('id', 'noteId');

        $this->mapper
            ->expects($this->once())
            ->method('unrelate')
            ->with($post, 'notes', $note);

        $this->createRepository('Post')->getRelation($post, 'notes')->unrelate($note);
    }

    public function testRelateById1()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $this->mapper
            ->expects($this->once())
            ->method('relate')
            ->with($post, 'notes', $this->isInstanceOf(Entities\Note::class));

        $this->createRepository('Post')->getRelation($post, 'notes')->relateById('noteId');
    }

    public function testUnrelateById1()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $this->mapper
            ->expects($this->once())
            ->method('unrelate')
            ->with($post, 'notes', $this->isInstanceOf(Entities\Note::class));

        $this->createRepository('Post')->getRelation($post, 'notes')->unrelateById('noteId');
    }

    public function testMassRelate()
    {
        $post = $this->entityFactory->create('Post');
        $post->set('id', 'postId');

        $select = $this->queryBuilder
            ->select()
            ->from('Note')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('massRelate')
            ->with($post, 'notes', $select);

        $this->createRepository('Post')->getRelation($post, 'notes')->massRelate($select);
    }

    public function testGetColumn()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $team = $this->entityFactory->create('Team');
        $team->set('id', 'teamId');

        $this->mapper
            ->expects($this->once())
            ->method('getRelationColumn')
            ->with($account, 'teams', $team->id, 'test');

        $this->createRepository('Post')->getRelation($account, 'teams')->getColumn($team, 'test');
    }

    public function testUpdateColumns()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $team = $this->entityFactory->create('Team');
        $team->set('id', 'teamId');

        $columns = [
            'column' => 'test',
        ];

        $this->mapper
            ->expects($this->once())
            ->method('updateRelationColumns')
            ->with($account, 'teams', $team->id, $columns);

        $this->createRepository('Post')->getRelation($account, 'teams')->updateColumns($team, $columns);
    }

    public function testRelationSelectBuilderFind1()
    {
        $repository = $this->createRepository('Post');

        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Comment')
            ->select(['id'])
            ->distinct()
            ->where(['name' => 'test'])
            ->join('Test', 'test', ['id:' => 'id'])
            ->order('id', 'DESC')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($post, 'comments', $select);

        $repository->getRelation($post, 'comments')
            ->select(['id'])
            ->distinct()
            ->where(['name' => 'test'])
            ->join('Test', 'test', ['id:' => 'id'])
            ->order('id', 'DESC')
            ->find();
    }

    public function testRelationSelectBuilderFind2()
    {
        $repository = $this->createRepository('Post');

        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Comment')
            ->select(['id'])
            ->distinct()
            ->where(Cond::equal(Expr::column('name'), 'test'))
            ->join('Test', 'test', ['id=:' => 'id'])
            ->order('id', 'DESC')
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($post, 'comments', $select);

        $repository->getRelation($post, 'comments')
            ->select(['id'])
            ->distinct()
            ->where(Cond::equal(Expr::column('name'), 'test'))
            ->join(
                'Test',
                'test',
                Cond::equal(Expr::column('id'), Expr::column('id'))
            )
            ->order('id', 'DESC')
            ->find();
    }

    public function testRelationSelectBuilderFindOne()
    {
        $repository = $this->createRepository('Post');

        $post = $this->entityFactory->create('Post');
        $post->id = 'postId';



        $comment = $this->entityFactory->create('Comment');
        $comment->set('id', 'commentId');

        $collection = $this->createCollectionMock([$comment]);

        $select = $this->queryBuilder
            ->select()
            ->from('Comment')
            ->select(['id'])
            ->distinct()
            ->where(['name' => 'test'])
            ->join('Test', 'test', ['id:' => 'id'])
            ->order('id', 'DESC')
            ->limit(0, 1)
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($post, 'comments', $select);

        $result = $repository->getRelation($post, 'comments')
            ->select(['id'])
            ->distinct()
            ->where(['name' => 'test'])
            ->join('Test', 'test', ['id:' => 'id'])
            ->order('id', 'DESC')
            ->findOne();

        $this->assertEquals($comment, $result);
    }

    public function testRelationSelectBuilderColumnsWhere1()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where(['entityTeam.deleted' => false])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($account, 'teams', $select);


        $this->createRepository('Account')->getRelation($account, 'teams')
            ->columnsWhere(['deleted' => false])
            ->find();
    }

    public function testRelationSelectBuilderColumnsWhere2()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where([
                'OR' => [
                    ['entityTeam.deleted' => false],
                    ['entityTeam.deleted' => null],
                ]
            ])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($account, 'teams', $select);


        $this->createRepository('Account')->getRelation($account, 'teams')
            ->columnsWhere([
                'OR' => [
                    ['deleted' => false],
                    ['deleted' => null],
                ]
            ])
            ->find();
    }

    public function testRelationSelectBuilderRelationWhere1()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where(['entityTeam.deleted' => false])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($account, 'teams', $select);

        $this->createRepository('Account')
            ->getRelation($account, 'teams')
            ->where(['@relation.deleted' => false])
            ->find();
    }

    public function testRelationSelectBuilderRelationWhere2()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where([
                'OR' => [
                    ['entityTeam.deleted' => false],
                    ['entityTeam.deleted' => null],
                ],
                'deleted' => false,
            ])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($account, 'teams', $select);

        $this->createRepository('Account')
            ->getRelation($account, 'teams')
            ->where([
                'OR' => [
                    ['@relation.deleted' => false],
                    ['@relation.deleted' => null],
                ],
                'deleted' => false,
            ])
            ->find();
    }

    public function testRelationSelectBuilderRelationWhere3()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where(['entityTeam.deleted' => false])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($account, 'teams', $select);

        $this->createRepository('Account')
            ->getRelation($account, 'teams')
            ->where('@relation.deleted', false)
            ->find();
    }

    public function testRelationSelectBuilderRelationWhere4()
    {
        $account = $this->entityFactory->create('Account');
        $account->set('id', 'accountId');

        $collection = $this->createCollectionMock();

        $select = $this->queryBuilder
            ->select()
            ->from('Team')
            ->where(['entityTeam.deleted=' => false])
            ->build();

        $this->mapper
            ->expects($this->once())
            ->method('selectRelated')
            ->will($this->returnValue($collection))
            ->with($account, 'teams', $select);

        $this->createRepository('Account')
            ->getRelation($account, 'teams')
            ->where(
                Cond::equal(Expr::column('@relation.deleted'), false)
            )
            ->find();
    }
}
