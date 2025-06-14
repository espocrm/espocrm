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

namespace tests\unit\Espo\ORM\Query\Part;

use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Join;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\SelectBuilder;

class JoinTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate1(): void
    {
        $conditions = Expr::isNull(
            Expr::column('test')
        );

        $join = Join::createWithRelationTarget('test')
            ->withAlias('testAlias')
            ->withConditions($conditions);

        $this->assertEquals('test', $join->getTarget());
        $this->assertEquals('testAlias', $join->getAlias());
        $this->assertEquals($conditions, $join->getConditions());

        $this->assertTrue($join->isRelation());
        $this->assertFalse($join->isTable());

        $this->assertEquals(Join::MODE_RELATION, $join->getMode());
    }

    public function testCreate2(): void
    {
        $conditions = Expr::isNull(
            Expr::column('test')
        );

        $join = Join::createWithTableTarget('Test')
            ->withAlias('testAlias')
            ->withConditions($conditions);

        $this->assertEquals('Test', $join->getTarget());
        $this->assertEquals('testAlias', $join->getAlias());
        $this->assertEquals($conditions, $join->getConditions());

        $this->assertTrue($join->isTable());
        $this->assertFalse($join->isRelation());

        $this->assertEquals(Join::MODE_TABLE, $join->getMode());
    }

    public function testCreate3(): void
    {
        $join = Join::create('Test', 'testAlias');

        $this->assertEquals('Test', $join->getTarget());
        $this->assertEquals('testAlias', $join->getAlias());
    }

    public function testCreate4(): void
    {
        $join = Join::createWithSubQuery(
            SelectBuilder::create()
                ->select(Expression::value(true))
                ->build()
            ,
            'a'
        );

        $this->assertTrue($join->isSubQuery());
        $this->assertEquals(Join::MODE_SUB_QUERY, $join->getMode());
    }
}
