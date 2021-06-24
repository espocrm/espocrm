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

namespace tests\unit\Espo\ORM\Query;

use Espo\ORM\{
    Query\SelectBuilder,
    Query\Part\Condition as Cond,
    Query\Part\Expression as Expr,
};

class SelectBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SelectBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new SelectBuilder();
    }

    public function testFrom()
    {
        $params = $this->builder
            ->from('Test')
            ->build()
            ->getRaw();

        $this->assertEquals('Test', $params['from']);
    }

    public function testSelect1()
    {
        $select = $this->builder
            ->from('Test')
            ->select(['id', 'name'])
            ->select('test')
            ->build();

        $this->assertEquals(['id', 'name', 'test'], $select->getSelect());
    }

    public function testSelect2()
    {
        $select = $this->builder
            ->from('Test')
            ->select('test')
            ->select(['id', 'name'])
            ->build();

        $this->assertEquals(['id', 'name'], $select->getSelect());
    }

    public function testSelect3()
    {
        $select = $this->builder
            ->from('Test')
            ->select('test', 'hello')
            ->build();

        $this->assertEquals([['test', 'hello']], $select->getSelect());
    }

    public function testSelect4()
    {
        $select = $this->builder
            ->from('Test')
            ->select(Expr::create('test'), 'hello')
            ->build();

        $this->assertEquals([['test', 'hello']], $select->getSelect());
    }

    public function testSelect5()
    {
        $select = $this->builder
            ->from('Test')
            ->select('test')
            ->select([Expr::create('id'), Expr::create('name')])
            ->build();

        $this->assertEquals(['id', 'name'], $select->getSelect());
    }

    public function testSelect6()
    {
        $select = $this->builder
            ->from('Test')
            ->select('test')
            ->select([
                [Expr::create('id'), 'id'],
                [Expr::create('name'), 'name'],
            ])
            ->build();

        $this->assertEquals([['id', 'id'], ['name', 'name']], $select->getSelect());
    }

    public function testCloneNotSame()
    {
        $builder = new SelectBuilder();

        $select = $builder
            ->from('Test')
            ->build();

        $builder = new SelectBuilder();

        $selectCloned = $builder
            ->clone($select)
            ->build();

        $this->assertNotSame($selectCloned, $select);
    }

    public function testWhereNull1()
    {
        $select = $this->builder
            ->from('Test')
            ->where(['test' => null])
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals(['test' => null], $raw['whereClause']);
    }

    public function testWhereNull2()
    {
        $select = $this->builder
            ->from('Test')
            ->where('test', null)
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals(['test' => null], $raw['whereClause']);
    }

    public function testGroupBy1()
    {
        $select = $this->builder
            ->from('Test')
            ->having(['test' => null])
            ->groupBy(['test'])
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals(['test' => null], $raw['havingClause']);

        $this->assertEquals(['test'], $raw['groupBy']);
    }

    public function testGroupBy2()
    {
        $select = $this->builder
            ->from('Test')
            ->having(Cond::equal(Expr::column('test'), null))
            ->groupBy(Expr::create('test'))
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals(['test=' => null], $raw['havingClause']);

        $this->assertEquals(['test'], $raw['groupBy']);
    }

    public function testGroupBy3()
    {
        $select = $this->builder
            ->from('Test')
            ->having(Cond::equal(Expr::column('test'), null))
            ->groupBy([Expr::create('test')])
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals(['test=' => null], $raw['havingClause']);

        $this->assertEquals(['test'], $raw['groupBy']);
    }

    public function testOrder1()
    {
        $select = $this->builder
            ->from('Test')
            ->order(Expr::create('test'))
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals([['test', 'ASC']], $raw['orderBy']);
    }

    public function testOrder2()
    {
        $select = $this->builder
            ->from('Test')
            ->order(Expr::create('test'))
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals([['test', 'ASC']], $raw['orderBy']);
    }

    public function testOrder3()
    {
        $select = $this->builder
            ->from('Test')
            ->order([Expr::create('test')], 'DESC')
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals([['test', 'DESC']], $raw['orderBy']);
    }

    public function testOrder4()
    {
        $select = $this->builder
            ->from('Test')
            ->order([[Expr::create('test'), 'DESC']], 'ASC')
            ->build();

        $raw = $select->getRaw();

        $this->assertEquals([['test', 'DESC']], $raw['orderBy']);
    }

    public function testClone()
    {
        $builder = new SelectBuilder();

        $select = $builder
            ->from('Test')
            ->where('test1', '1')
            ->build();

        $builder = new SelectBuilder();

        $selectCloned = $builder
            ->clone($select)
            ->distinct()
            ->where('test2', '2')
            ->build();

        $params = $select->getRaw();
        $paramsCloned = $selectCloned->getRaw();

        $this->assertTrue($paramsCloned['distinct']);
        $this->assertFalse($params['distinct'] ?? false);

        $this->assertEquals(['test1' =>'1'], $params['whereClause']);
        $this->assertEquals(['test1' => '1', 'test2' => '2'], $paramsCloned['whereClause']);
    }

    public function testCloneException()
    {
        $builder = new SelectBuilder();

        $select = $builder
            ->from('Test')
            ->where('test1', '1')
            ->build();

        $builder = new SelectBuilder();

        $this->expectException(\RuntimeException::class);

        $builder
            ->from('Test')
            ->clone($select);
    }

    public function testWhereSameKeys1()
    {
        $builder = new SelectBuilder();

        $select = $builder
            ->from('Test')
            ->where(['test' => '1'])
            ->where(['test' => '2'])
            ->build();

        $raw = $select->getRaw();

        $expected = [
            'test' => '1',
            ['test' => '2'],
        ];

        $this->assertEquals($expected, $raw['whereClause']);
    }

    public function testWhereSameKeys2()
    {
        $builder = new SelectBuilder();

        $select = $builder
            ->from('Test')
            ->where([
                'OR' => [
                    'test' => '1'
                ],
            ])
            ->where([
                'OR' => [
                    'test' => '2'
                ],
            ])
            ->build();

        $raw = $select->getRaw();

        $expected = [
            'OR' => [
                'test' => '1'
            ],
            [
                'OR' => [
                    'test' => '2'
                ],
            ]
        ];

        $this->assertEquals($expected, $raw['whereClause']);
    }

    public function testLeftJoin()
    {
        $params = $this->builder
            ->from('Test')
            ->leftJoin('link1')
            ->leftJoin('link1')
            ->leftJoin('link2')
            ->build()
            ->getRaw();

        $this->assertEquals(['link1', 'link2'], $params['leftJoins']);
    }

    public function testJoin()
    {
        $params = $this->builder
            ->from('Test')
            ->join('link1')
            ->join('link1')
            ->join('link2')
            ->build()
            ->getRaw();

        $this->assertEquals(['link1', 'link2'], $params['joins']);
    }

    public function testWhereItemUsage1()
    {
        $query = $this->builder
            ->from('Test')
            ->where(
                Cond::or(
                    Cond::equal(Expr::column('test'), '1'),
                    Cond::equal(Expr::column('test'), '2')
                )
            )
            ->join(
                'Table1',
                'table1',
                Cond::equal(Expr::column('table1.testId'), Expr::column('id'))
            )
            ->leftJoin(
                'Table2',
                'table2',
                Cond::equal(Expr::column('table2.testId'), Expr::column('id'))
            )
            ->build();

        $raw = $query->getRaw();

        $expectedWhere = [
            'OR' => [
                ['test=' => '1'],
                ['test=' => '2'],
            ]
        ];

        $expectedJoins = [
            [
                'Table1',
                'table1',
                [
                    'table1.testId=:' => 'id'
                ]
            ]
        ];

        $expectedLeftJoins = [
            [
                'Table2',
                'table2',
                [
                    'table2.testId=:' => 'id'
                ]
            ]
        ];

        $this->assertEquals($expectedWhere, $raw['whereClause']);
        $this->assertEquals($expectedJoins, $raw['joins']);
        $this->assertEquals($expectedLeftJoins, $raw['leftJoins']);
    }
}
