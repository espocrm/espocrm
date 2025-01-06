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

use Espo\Core\Utils\Database\Helper as DatabaseHelper;

class VarcharFieldTest extends Base
{
    public function testColumn()
    {
        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertNotEmpty($column);
        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals(100, $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testIncreaseColumnLength()
    {
        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 150,
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertNotEmpty($column);
        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('150', $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testReduceColumnLength()
    {
        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 50,
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertNotEmpty($column);
        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('100', $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testReduceColumnLength2()
    {
        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 50,
            'default' => 'test-default',
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertNotEmpty($column);
        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('100', $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testReduceColumnLength3()
    {
        $this->executeQuery(
            "ALTER TABLE test MODIFY COLUMN test_varchar VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;"
        );

        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 50,
            'default' => 'test-default',
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertNotEmpty($column);
        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('100', $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);

        $this->assertContains($column['COLLATION_NAME'], [
            'utf8_unicode_ci',
            'utf8mb3_unicode_ci'
        ]);
    }

    public function testCollationForExistingColumn()
    {
        $column = $this->getColumnInfo('Test', 'testVarchar');
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);

        $this->executeQuery(
            "ALTER TABLE test MODIFY COLUMN test_varchar VARCHAR(". $column['CHARACTER_MAXIMUM_LENGTH'] .") CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;"
        );

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertContains($column['COLLATION_NAME'], [
            'utf8_unicode_ci',
            'utf8mb3_unicode_ci'
        ]);

        $this->updateDefs('Test', 'testVarchar', [
            'maxLength' => 150,
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('150', $column['CHARACTER_MAXIMUM_LENGTH']);

        $this->assertContains($column['COLLATION_NAME'], [
            'utf8_unicode_ci',
            'utf8mb3_unicode_ci'
        ]);
    }

    public function testCollationForExistingColumn2()
    {
        $column = $this->getColumnInfo('Test', 'testVarchar');
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);

        $this->executeQuery(
            "ALTER TABLE test MODIFY COLUMN test_varchar VARCHAR(". $column['CHARACTER_MAXIMUM_LENGTH'] .") CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;"
        );

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertContains($column['COLLATION_NAME'], [
            'utf8_unicode_ci',
            'utf8mb3_unicode_ci'
        ]);

        $this->updateDefs('Test', 'testVarchar', [
            'default' => 'test-default',
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertEquals('varchar', $column['DATA_TYPE']);

        $this->assertContains($column['COLLATION_NAME'], [
            'utf8_unicode_ci',
            'utf8mb3_unicode_ci'
        ]);
    }

    public function testCollationForNewColumn()
    {
        $this->updateDefs('Test', 'newTestVarchar', [
            'type' => 'varchar',
        ]);

        $column = $this->getColumnInfo('Test', 'newTestVarchar');

        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testDefaultValue()
    {
        $this->updateDefs('Test', 'testVarchar', [
            'default' => 'test-default',
        ]);

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertEquals('varchar', $column['DATA_TYPE']);
        $this->assertEquals('100', $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);

        $dbHelper = $this->getInjectableFactory()->create(DatabaseHelper::class);

        if (
            $dbHelper->getType() == 'MariaDB'
            && version_compare($dbHelper->getVersion(), '10.2.7', '>=')
        ) {
            $this->assertEquals("'test-default'", $column['COLUMN_DEFAULT']);
        } else {
            $this->assertEquals('test-default', $column['COLUMN_DEFAULT']);
        }
    }

    /**
     * Make sure columns not removed.
     */
    public function testRemoveField(): void
    {
        $this->getMetadata()->delete('entityDefs', 'Test', ['fields.testVarchar']);
        $this->getMetadata()->save();
        $this->getDataManager()->rebuildDatabase();

        $column = $this->getColumnInfo('Test', 'testVarchar');

        $this->assertTrue((bool) $column);
    }
}
