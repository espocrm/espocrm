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

namespace tests\integration\Espo\Core\Utils\Database;

class AutoIncrementFieldTest extends Base
{
    public function testColumn()
    {
        $column = $this->getColumnInfo('Case', 'number');

        $this->assertNotEmpty($column);
        $this->assertEquals('int', $column['DATA_TYPE']);
        $this->assertEquals('NO', $column['IS_NULLABLE']);
        $this->assertEquals('10', $column['NUMERIC_PRECISION']);
        $this->assertEquals('auto_increment', $column['EXTRA']);
        $this->assertEquals('UNI', $column['COLUMN_KEY']);
    }

    public function testColumnOnExistingTable(): void
    {
        $this->updateDefs('Test', 'testAutoIncrement', [
            'type' => 'autoincrement',
        ]);

        $column = $this->getColumnInfo('Test', 'testAutoIncrement');

        $this->assertNotEmpty($column);
        $this->assertEquals('int', $column['DATA_TYPE']);
        $this->assertEquals('NO', $column['IS_NULLABLE']);
        $this->assertEquals('10', $column['NUMERIC_PRECISION']);
        $this->assertEquals('auto_increment', $column['EXTRA']);
        $this->assertEquals('UNI', $column['COLUMN_KEY']);
    }

    public function testDeleteColumnOnExistingTable(): void
    {
        // 1. Create "testAutoIncrement" field
        $this->testColumnOnExistingTable();

        // 2. Delete "testAutoIncrement" field
        $this->getMetadata()->delete('entityDefs', 'Test', ['fields.testAutoIncrement']);
        $this->getMetadata()->save();

        // Issue that it requires rebuilding between removing and adding a new indexes.
        $this->getDataManager()->rebuild();

        $column = $this->getColumnInfo('Test', 'testAutoIncrement');

        $this->assertNotEmpty($column);
        $this->assertEquals('int', $column['DATA_TYPE']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('10', $column['NUMERIC_PRECISION']);
        $this->assertEmpty($column['EXTRA']);
        $this->assertEmpty($column['COLUMN_KEY']);
    }

    public function testDeleteCreateColumnOnExistingTable(): void
    {
        // 1. Create "testAutoIncrement" field
        $this->testColumnOnExistingTable();

        // 2. Delete "testAutoIncrement" field
        $metadata = $this->getMetadata();
        $metadata->delete('entityDefs', 'Test', ['fields.testAutoIncrement']);
        $metadata->save();

        $this->getDataManager()->rebuild();

        // 3. Create "testAutoIncrement2" field
        $this->updateDefs('Test', 'testAutoIncrement2', [
            'type' => 'autoincrement',
        ]);

        $column = $this->getColumnInfo('Test', 'testAutoIncrement');
        $this->assertNotEmpty($column);
        $this->assertEquals('int', $column['DATA_TYPE']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('10', $column['NUMERIC_PRECISION']);
        $this->assertEmpty($column['EXTRA']);
        $this->assertEmpty($column['COLUMN_KEY']);

        $column2 = $this->getColumnInfo('Test', 'testAutoIncrement2');
        $this->assertNotEmpty($column2);
        $this->assertEquals('int', $column2['DATA_TYPE']);
        $this->assertEquals('NO', $column2['IS_NULLABLE']);
        $this->assertEquals('10', $column2['NUMERIC_PRECISION']);
        $this->assertEquals('auto_increment', $column2['EXTRA']);
        $this->assertEquals('UNI', $column2['COLUMN_KEY']);
    }
}
