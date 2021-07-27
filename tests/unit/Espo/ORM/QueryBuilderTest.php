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
    QueryBuilder,
    Query\Select,
    Query\Insert,
    Query\Update,
    Query\Delete,
    Query\Union,
    Query\Part\Selection,
};

use RuntimeException;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    protected function setUp() : void
    {
        $this->queryBuilder = new QueryBuilder();
    }

    public function testClone1()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->build();

        $clonedSelect = $this->queryBuilder
            ->clone($select)
            ->distinct()
            ->build();

        $this->assertNotSame($clonedSelect, $select);
        $this->assertInstanceOf(Select::class, $clonedSelect);
    }

    public function testSelectNoFrom1()
    {
        $select = $this->queryBuilder
            ->select()
            ->select(['col1'])
            ->build();

        $this->assertNull($select->getFrom());
    }

    public function testSelectNoFrom2()
    {
        $this->expectException(RuntimeException::class);

        $this->queryBuilder
            ->select()
            ->select(['col1'])
            ->join('test')
            ->build();
    }

    public function testSelectFrom1()
    {
        $select = $this->queryBuilder
            ->select(['col1', 'col2'])
            ->from('Test')
            ->build();

        $this->assertEquals(
            [
                Selection::fromString('col1'),
                Selection::fromString('col2'),
            ],
            $select->getSelect()
        );
    }

    public function testSelectFrom2()
    {
        $select = $this->queryBuilder
            ->select('col1', 'alias1')
            ->from('Test')
            ->build();

        $this->assertEquals(
            [
                Selection::fromString('col1')->withAlias('alias1')
            ],
            $select->getSelect()
        );
    }
    public function testInsert1()
    {
        $insert = $this->queryBuilder
            ->insert()
            ->into('Test')
            ->columns(['col1'])
            ->values(['col1' => '1'])
            ->build();

        $this->assertInstanceOf(Insert::class, $insert);
    }

    public function testInsert2()
    {
        $select = $this->queryBuilder
            ->select()
            ->from('Test')
            ->build();

        $insert = $this->queryBuilder
            ->insert()
            ->into('Test')
            ->columns(['col1'])
            ->valuesQuery($select)
            ->build();

        $this->assertInstanceOf(Insert::class, $insert);
    }

    public function testInsertNoInto()
    {
        $this->expectException(RuntimeException::class);

        $this->queryBuilder
            ->insert()
            ->columns(['col1'])
            ->values(['col1' => '1'])
            ->build();
    }

    public function testDelete1()
    {
        $delete = $this->queryBuilder
            ->delete()
            ->from('Test')
            ->where(['col1' => '1'])
            ->limit(1)
            ->build();

        $this->assertInstanceOf(Delete::class, $delete);
    }

    public function testDeleteNoFrom()
    {
        $this->expectException(RuntimeException::class);

        $this->queryBuilder
            ->delete()
            ->where(['col1' => '1'])
            ->build();
    }

    public function testUpdateNoIn()
    {
        $this->expectException(RuntimeException::class);

        $this->queryBuilder
            ->update()
            ->set(['col1' => '2'])
            ->where(['col1' => '1'])
            ->build();
    }

    public function testUpdate1()
    {
        $update = $this->queryBuilder
            ->update()
            ->in('Test')
            ->set(['col1' => '2'])
            ->where(['col1' => '1'])
            ->limit(1)
            ->build();

        $this->assertInstanceOf(Update::class, $update);
    }

    public function testUpdateNoSet()
    {
        $this->expectException(RuntimeException::class);

        $this->queryBuilder
            ->update()
            ->in('Test')
            ->where(['col1' => '1'])
            ->limit(1)
            ->build();
    }

    public function testUnion1()
    {
        $q1 = $this->queryBuilder
            ->select()
            ->select(['col1'])
            ->build();

        $q2 = $this->queryBuilder
            ->select()
            ->select(['col1'])
            ->build();

        $union = $this->queryBuilder
            ->union()
            ->query($q1)
            ->query($q2)
            ->all()
            ->limit(0, 5)
            ->order(1, 'DESC')
            ->build();

        $this->assertInstanceOf(Union::class, $union);
    }

    public function testUnionNoQuery()
    {
        $this->expectException(RuntimeException::class);

        $this->queryBuilder
            ->union()
            ->build();
    }
}
