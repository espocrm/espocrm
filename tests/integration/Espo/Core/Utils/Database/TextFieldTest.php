<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

class TextFieldTest extends Base
{
    public function testColumn()
    {
        $column = $this->getColumnInfo('Test', 'testText');

        $this->assertNotEmpty($column);
        $this->assertEquals('mediumtext', $column['COLUMN_TYPE']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testIncreaseColumnLength()
    {
        $this->updateDefs('Test', 'testText', [
            'type' => 'text',
            'dbType' => 'longtext',
        ]);

        $column = $this->getColumnInfo('Test', 'testText');

        $this->assertNotEmpty($column);
        $this->assertEquals('longtext', $column['COLUMN_TYPE']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }

    public function testReduceColumnLength()
    {
        $this->updateDefs('Test', 'testText', [
            'type' => 'text',
            'dbType' => 'longtext',
        ]);

        $this->getContainer()->get('metadata')->delete('entityDefs', 'Test', [
            'fields.testText.dbType',
        ]);
        $this->getContainer()->get('metadata')->save();

        $this->getDataManager()->rebuildDatabase();

        $column = $this->getColumnInfo('Test', 'testText');

        $this->assertNotEmpty($column);
        $this->assertEquals('longtext', $column['COLUMN_TYPE']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals('utf8mb4_unicode_ci', $column['COLLATION_NAME']);
    }
}
