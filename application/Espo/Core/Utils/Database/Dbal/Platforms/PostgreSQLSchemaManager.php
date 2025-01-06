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

namespace Espo\Core\Utils\Database\Dbal\Platforms;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager as BasePostgreSQLSchemaManager;

class PostgreSQLSchemaManager extends BasePostgreSQLSchemaManager
{
    /**
     * DBAL does not add the 'fulltext' flag on reverse engineering.
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $indexes = parent::_getPortableTableIndexesList($tableIndexes, $tableName);

        foreach ($tableIndexes as $row) {
            $key = $row['relname'];

            if ($key === "idx_{$tableName}_system_full_text_search") {
                $sql = "SELECT indexdef FROM pg_indexes WHERE indexname = '{$key}'";

                $rows = $this->_conn->fetchAllAssociative($sql);

                if (!$rows) {
                    continue;
                }

                $columns = self::parseColumnsIndexFromDeclaration($rows[0]['indexdef']);

                $indexes[$key] = new Index(
                    $key,
                    $columns,
                    false,
                    false,
                    ['fulltext']
                );
            }
        }

        return $indexes;
    }

    /**
     * @return string[]
     */
    private static function parseColumnsIndexFromDeclaration(string $string): array
    {
        preg_match('/to_tsvector\((.*),(.*)\)/i', $string, $matches);

        if (!$matches || count($matches) < 3) {
            return [];
        }

        $part = $matches[2];

        $part = str_replace("|| ' '::text", '', $part);
        $part = str_replace("::text", '', $part);
        $part = str_replace(" ", '', $part);
        $part = str_replace("||", ' ', $part);
        $part = str_replace("(", '', $part);
        $part = str_replace(")", '', $part);

        $list = array_map(
            fn ($item) => trim($item),
            explode(' ', $part)
        );

        return $list;
    }
}
