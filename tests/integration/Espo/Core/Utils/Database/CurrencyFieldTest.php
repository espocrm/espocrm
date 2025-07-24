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

use PHPUnit\Framework\Attributes\DataProvider;

class CurrencyFieldTest extends Base
{
    static public function fieldList(): array
    {
        return [
            ['testCurrency', 'double', null, null],
            ['testCurrencyCurrency', 'varchar', 3, 'utf8mb4_unicode_ci'],
        ];
    }

    #[DataProvider('fieldList')]
    public function testColumns($fieldName, $type, $length, $collation): void
    {
        $column = $this->getColumnInfo('Test', $fieldName);

        $this->assertNotEmpty($column);
        $this->assertEquals($type, $column['DATA_TYPE']);
        $this->assertEquals($length, $column['CHARACTER_MAXIMUM_LENGTH']);
        $this->assertEquals('YES', $column['IS_NULLABLE']);
        $this->assertEquals($collation, $column['COLLATION_NAME']);
    }
}
