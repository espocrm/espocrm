<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

require_once 'tests/unit/testData/DB/Entities.php';

use Espo\ORM\{
    DB\MysqlMapper,
    DB\Query\Mysql as Query,
    Repositories\RDB as Repository,
    EntityCollection,
};

use Espo\Core\ORM\{
    EntityManager,
    EntityFactory,
};

use tests\unit\testData\Entities\Test;

class RDBRepositoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $entityManager = $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityFactory = $this->getMockBuilder(EntityFactory::class)->disableOriginalConstructor()->getMock();

        $this->mapper = $this->getMockBuilder(MysqlMapper::class)->disableOriginalConstructor()->getMock();

        $entityManager
            ->method('getMapper')
            ->will($this->returnValue($this->mapper));

        $entity = $this->seed = $this->createEntity('Test', Test::class);

        $this->collection = $this->getMockBuilder(EntityCollection::class)->disableOriginalConstructor()->getMock();

        $entityFactory
            ->method('create')
            ->will($this->returnValue($entity));

        $this->repository = new Repository('Test', $entityManager, $entityFactory);

        $entityManager
            ->method('getRepository')
            ->will($this->returnValue($this->repository));
    }

    protected function createEntity(string $entityType, string $className)
    {
        return new $className($entityType, [], $this->entityManager);
    }

    public function testFind()
    {
        $params = [
            'whereClause' => [
                'name' => 'test',
            ],
        ];

        $paramsExpected = [
            'whereClause' => [
                'name' => 'test',
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->find($params);
    }

    public function testFindOne()
    {
        $params = [
            'whereClause' => [
                'name' => 'test',
            ],
        ];

        $paramsExpected = [
            'whereClause' => [
                'name' => 'test',
            ],
            'offset' => 0,
            'limit' => 1,
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->findOne($params);
    }

    public function testWhere1()
    {
        $paramsExpected = [
            'whereClause' => [
                'name' => 'test',
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->where(['name' => 'test'])->find();
    }

    public function testWhere2()
    {
        $paramsExpected = [
            'whereClause' => [
                ['name' => 'test'],
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->where('name', 'test')->find();
    }

    public function testWhereFineOne()
    {
        $paramsExpected = [
            'whereClause' => [
                ['name' => 'test'],
            ],
            'offset' => 0,
            'limit' => 1,
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->where('name', 'test')->findOne();
    }

    public function testJoin1()
    {
        $paramsExpected = [
            'joins' => [
                'Test',
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->join('Test')->find();
    }

    public function testJoin2()
    {
        $paramsExpected = [
            'joins' => [
                'Test1',
                'Test2',
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->join(['Test1', 'Test2'])->find();
    }

    public function testJoin3()
    {
        $paramsExpected = [
            'joins' => [
                ['Test1', 'test1'],
                ['Test2', 'test2'],
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->join([['Test1', 'test1'], ['Test2', 'test2']])->find();
    }

    public function testJoin4()
    {
        $paramsExpected = [
            'joins' => [
                ['Test1', 'test1', ['k' => 'v']],
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->join('Test1', 'test1', ['k' => 'v'])->find();
    }

    public function testLeftJoin1()
    {
        $paramsExpected = [
            'leftJoins' => [
                'Test',
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->leftJoin('Test')->find();
    }

    public function testMultipleLeftJoins()
    {
        $paramsExpected = [
            'leftJoins' => [
                'Test1',
                ['Test2', 'test2'],
            ],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->leftJoin('Test1')->leftJoin('Test2', 'test2')->find();
    }

    public function testDistinct()
    {
        $paramsExpected = [
            'distinct' => true,
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->distinct()->find();
    }

    public function testSth()
    {
        $paramsExpected = [
            'returnSthCollection' => true,
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->sth()->find();
    }

    public function testOrder1()
    {
        $paramsExpected = [
            'orderBy' => 'name',
            'order' => 'ASC',
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->order('name')->find();
    }

    public function testSelect()
    {
        $paramsExpected = [
            'select' => ['name', 'date'],
        ];

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($this->collection))
            ->with($this->seed, $paramsExpected);

        $this->repository->select(['name', 'date'])->find();
    }
}
