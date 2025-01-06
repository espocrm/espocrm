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

namespace tests\unit\Espo\Core\Acl;

use Espo\Core\Acl\ScopeData;
use Espo\Core\Acl\Table;
use PHPUnit\Framework\TestCase;

class ScopeDataResolverTest extends TestCase
{
    public function testResolve1(): void
    {
        $table = $this->createMock(Table::class);
        $resolver = new Table\ScopeDataResolver($table);

        $table->expects($this->once())
            ->method('getScopeData')
            ->with('Test')
            ->willReturn(ScopeData::fromRaw(true));

        $this->assertTrue($resolver->resolve('Test')->isTrue());
    }

    public function testResolve2(): void
    {
        $table = $this->createMock(Table::class);
        $resolver = new Table\ScopeDataResolver($table);

        $table->expects($this->once())
            ->method('getScopeData')
            ->willReturnMap([
                ['Test', ScopeData::fromRaw(false)]
            ]);

        $this->assertTrue($resolver->resolve('Test')->isFalse());
    }

    public function testResolve3(): void
    {
        $table = $this->createMock(Table::class);
        $resolver = new Table\ScopeDataResolver($table);

        $table->expects($this->once())
            ->method('getScopeData')
            ->with('Test')
            ->willReturn(ScopeData::fromRaw((object) ['create' => 'yes', 'edit' => 'no']));

        $result = $resolver->resolve('Test');

        $this->assertEquals('yes', $result->getCreate());
        $this->assertEquals('no', $result->getEdit());
    }

    public function testResolve4(): void
    {
        $table = $this->createMock(Table::class);
        $resolver = new Table\ScopeDataResolver($table);

        $table->expects($this->once())
            ->method('getScopeData')
            ->with('Test')
            ->willReturn(ScopeData::fromRaw((object) ['create' => 'yes', 'edit' => 'no']));

        $this->assertTrue($resolver->resolve('boolean:Test')->isTrue());
    }

    public function testResolve5(): void
    {
        $table = $this->createMock(Table::class);
        $resolver = new Table\ScopeDataResolver($table);

        $table->expects($this->once())
            ->method('getScopeData')
            ->with('Test')
            ->willReturn(ScopeData::fromRaw((object) ['create' => 'no', 'edit' => 'no']));

        $this->assertTrue($resolver->resolve('boolean:Test')->isTrue());
    }
}
