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

namespace Espo\Core\Utils\Database\Dbal\Platforms;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager as BasePostgreSQLSchemaManager;

class PostgreSQLSchemaManager extends BasePostgreSQLSchemaManager
{
    /**
     * DBAL does not add the 'fulltext' flag on reverse engineering.
     *
     * @todo Test.
     */
    protected function _getPortableTableIndexesList(array $rows, string $tableName): array
    {
        $indexes = parent::_getPortableTableIndexesList($rows, $tableName);

        foreach ($rows as $row) {
            $key = $row['key_name'];

            if (str_starts_with($tableName, '"') && str_ends_with($tableName, '"')) {
                $tableName = substr($tableName, 1, -1);
            }

            if ($key !== "idx_{$tableName}_system_full_text_search") {
                continue;
            }

            $sql = "SELECT indexdef FROM pg_indexes WHERE indexname = '$key'";

            $items = $this->connection->fetchAllAssociative($sql);

            if (!$items) {
                continue;
            }

            $columns = self::parseColumnsIndexFromDeclaration($items[0]['indexdef']);

            if (!$columns) {
                unset($indexes[$key]);

                continue;
            }

            $indexes[$key] = new Index(
                name: $key,
                columns: array_values($columns),
                isUnique: false,
                isPrimary: false,
                flags: ['fulltext'],
            );
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

        return array_map(fn ($item) => trim($item),  explode(' ', $part));
    }
}
