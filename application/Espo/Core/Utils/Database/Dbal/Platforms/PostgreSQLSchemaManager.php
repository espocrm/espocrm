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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager as BasePostgreSQLSchemaManager;
use RuntimeException;

class PostgreSQLSchemaManager extends BasePostgreSQLSchemaManager
{
    /**
     * Partially copy-pasted. Added parts to retrieve fulltext indexes too.
     *
     * Might stop working in future versions in DBAL. In this case, a custom
     * metadata provider might need to be used.
     *
     * @throws Exception
     */
    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $params = [];

        $sql = sprintf(
            <<<'SQL'
            SELECT
                   quote_ident(n.nspname) AS schema_name,
                   quote_ident(c.relname) AS table_name,
                   quote_ident(ic.relname) AS relname,
                   i.indisunique,
                   i.indisprimary,
                   i.indkey,
                   i.indrelid,
                   pg_get_expr(indpred, indrelid) AS "where",
                   quote_ident(attname) AS attname,
                   -- added
                   CASE
                       WHEN keys.attnum = 0
                       THEN pg_get_expr(i.indexprs, i.indrelid)
                       ELSE quote_ident(a.attname)
               END AS attname
              FROM pg_index i
                   JOIN pg_class AS c ON c.oid = i.indrelid
                   JOIN pg_namespace n ON n.oid = c.relnamespace
                   JOIN pg_class AS ic ON ic.oid = i.indexrelid
                   JOIN LATERAL UNNEST(i.indkey) WITH ORDINALITY AS keys(attnum, ord)
                        ON TRUE
                   -- LEFT added
                   LEFT JOIN pg_attribute a
                        ON a.attrelid = c.oid
                            AND a.attnum = keys.attnum
             WHERE %s
             ORDER BY 1, 2, keys.ord;
            SQL,
            implode(' AND ', $this->buildQueryConditions($tableName, $params)),
        );

        return $this->connection->executeQuery($sql, $params);
    }

    /**
     * DBAL does not add the 'fulltext' flag on reverse engineering.
     *
     * @todo Test.
     */
    protected function _getPortableTableIndexesList(array $rows, string $tableName): array
    {
        $indexes = parent::_getPortableTableIndexesList($rows, $tableName);

        foreach ($rows as $row) {
            $key = $row['relname'];

            if (str_starts_with($tableName, '"')) {
                $tableName = substr($tableName, 1, -1);
            }

            if ($key !== "idx_{$tableName}_system_full_text_search") {
                continue;
            }

            $sql = "SELECT indexdef FROM pg_indexes WHERE indexname = '$key'";

            try {
                $items = $this->connection->fetchAllAssociative($sql);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage(), previous: $e);
            }

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

    /**
     * Copy-pasted the private method.
     *
     * @param list<int|string> $params
     * @return non-empty-list<string>
     */
    private function buildQueryConditions(?string $tableName, array &$params): array
    {
        $conditions = [];

        if ($tableName !== null) {
            if (str_contains($tableName, '.')) {
                [$schemaName, $tableName] = explode('.', $tableName);

                $conditions[] = 'n.nspname = ?';
                $params[] = $schemaName;
            } else {
                $conditions[] = 'n.nspname = ANY(current_schemas(false))';
            }

            $conditions[] = 'c.relname = ?';
            $params[] = $tableName;
        }

        $conditions[] = "n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')";

        return $conditions;
    }
}
