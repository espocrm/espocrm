<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\integration\Espo\Core\Utils\Database\Fields;

use Doctrine\DBAL\Types\StringType;
use integration\Core\NoTransaction;

#[NoTransaction]
class VarcharFieldTest extends Base
{
    public function testColumn(): void
    {
        $column = $this->getColumn('Test', 'testVarchar');

        $this->assertInstanceOf(StringType::class, $column?->getType());
        $this->assertEquals(100, $column->getLength());
        $this->assertFalse($column->getNotnull());

        if ($this->getPlatform() === 'Mysql') {
            $this->assertEquals('utf8mb4_unicode_ci', $column->getCollation());
        }

        $this->runIncreaseColumnLength();
        $this->runReduceColumnLength1();
        $this->runReduceColumnLengthAndDefault();
    }

    private function runIncreaseColumnLength(): void
    {
        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 150,
        ]);

        $column = $this->getColumn('Test', 'testVarchar');

        $this->assertNotNull($column);
        $this->assertEquals(150, $column->getLength());
    }

    private function runReduceColumnLength1(): void
    {
        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 50,
        ]);

        $column = $this->getColumn('Test', 'testVarchar');

        $this->assertInstanceOf(StringType::class, $column?->getType());
        $this->assertEquals(150, $column->getLength());
    }

    private function runReduceColumnLengthAndDefault(): void
    {
        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 50,
            'default' => 'test-default',
        ]);

        $column = $this->getColumn('Test', 'testVarchar');

        $this->assertInstanceOf(StringType::class, $column?->getType());
        $this->assertEquals(150, $column->getLength());
        $this->assertEquals('test-default', $column->getDefault());
    }
}
