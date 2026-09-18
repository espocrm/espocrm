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

use Doctrine\DBAL\Types\IntegerType;
use integration\Core\NoTransaction;

#[NoTransaction]
class AutoIncrementFieldTest extends Base
{
    public function testColumnNumber(): void
    {
        $column = $this->getColumn('Case', 'number');

        $this->assertNotNull($column);
        $this->assertInstanceOf(IntegerType::class, $column->getType());
        $this->assertTrue($column->getNotnull());
        $this->assertTrue($column->getAutoincrement());

        $this->runColumnOnExistingTable();
        $this->runDeleteColumnOnExistingTable();
        $this->testDeleteCreateColumnOnExistingTable();
    }

    private function runColumnOnExistingTable(): void
    {
        $this->updateDefs('Test', 'testAutoIncrement', [
            'type' => 'autoincrement',
        ]);

        $column = $this->getColumn('Test', 'testAutoIncrement');

        $this->assertNotNull($column);
        $this->assertInstanceOf(IntegerType::class, $column->getType());
        $this->assertTrue($column->getNotnull());
        $this->assertTrue($column->getAutoincrement());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function runDeleteColumnOnExistingTable(): void
    {
        // Create "testAutoIncrement" field.
        $this->runColumnOnExistingTable();

        // Delete "testAutoIncrement" field.
        $this->getMetadata()->delete('entityDefs', 'Test', ['fields.testAutoIncrement']);
        $this->getMetadata()->save();

        // Issue that it requires rebuilding between removing and adding new indexes.
        $this->getDataManager()->rebuild();

        $column = $this->getColumn('Test', 'testAutoIncrement');

        $this->assertNotNull($column);
        $this->assertInstanceOf(IntegerType::class, $column->getType());
        $this->assertFalse($column->getNotnull());
        $this->assertFalse($column->getAutoincrement());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function testDeleteCreateColumnOnExistingTable(): void
    {
        // Create "testAutoIncrement" field.
        $this->runColumnOnExistingTable();

        // Delete "testAutoIncrement" field.
        $metadata = $this->getMetadata();
        $metadata->delete('entityDefs', 'Test', ['fields.testAutoIncrement']);
        $metadata->save();

        $this->getDataManager()->rebuild();

        // Create "testAutoIncrement2" field.
        $this->updateDefs('Test', 'testAutoIncrement2', [
            'type' => 'autoincrement',
        ]);

        $column = $this->getColumn('Test', 'testAutoIncrement');

        $this->assertNotNull($column);
        $this->assertInstanceOf(IntegerType::class, $column->getType());
        $this->assertFalse($column->getNotnull());
        $this->assertFalse($column->getAutoincrement());

        $column = $this->getColumn('Test', 'testAutoIncrement2');

        $this->assertNotNull($column);
        $this->assertInstanceOf(IntegerType::class, $column->getType());
        $this->assertTrue($column->getNotnull());
        $this->assertTrue($column->getAutoincrement());
    }
}
