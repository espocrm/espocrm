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

use Espo\ORM\{
    QueryBuilder,
    QueryParams\Select,
    QueryParams\Insert,
    QueryParams\Update,
    QueryParams\Delete,
};

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
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

    public function testUpdate1()
    {
        $update = $this->queryBuilder
            ->update()
            ->from('Test')
            ->set(['col1' => '2'])
            ->where(['col1' => '1'])
            ->limit(1)
            ->build();

        $this->assertInstanceOf(Update::class, $update);
    }

    public function testUpdateNoSet()
    {
        $this->expectException(\RuntimeException::class);

        $update = $this->queryBuilder
            ->update()
            ->from('Test')
            ->where(['col1' => '1'])
            ->limit(1)
            ->build();
    }
}
