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

namespace Espo\Core\Utils\Database\Schema\RebuildActions\Postgresql;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema as DbalSchema;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Database\Schema\RebuildAction;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class MigrateSequences implements RebuildAction
{
    public function __construct(
        private Config\SystemConfig $config,
        private Helper $helper,
    ) {}

    public function process(DbalSchema $oldSchema, DbalSchema $newSchema): void
    {
        $version = $this->config->getVersion();

        if ($version !== '@@version' && version_compare($version, '10.1.999') <= 0) {
            $this->runMigrationScript();
        }
    }

    private function runMigrationScript(): void
    {
        $sql = <<<'SQL'
            DO $$
                DECLARE
                    r record;
                    colnum smallint;
                    seqid oid;
                    cnt int;
                BEGIN
                    FOR r IN
                        SELECT
                            a.attrelid AS table_oid,
                            a.attrelid::regclass AS table_name,
                            a.attname AS column_name,
                            a.attnum AS column_num,
                            d.objid AS sequence_oid
                        FROM pg_attribute a
                            JOIN pg_depend d ON
                                d.refclassid = 'pg_class'::regclass AND
                                d.refobjid = a.attrelid AND
                                d.refobjsubid = a.attnum
                        WHERE
                            a.attnum > 0 AND
                            NOT a.attisdropped AND
                            d.classid = 'pg_class'::regclass AND
                            d.objsubid = 0 AND
                            d.deptype = 'a' AND
                            EXISTS (
                                SELECT 1
                                FROM pg_class s
                                WHERE
                                    s.oid = d.objid AND
                                    s.relkind = 'S'
                            )
                        LOOP
                            -- Drop the default.
                            EXECUTE
                                'ALTER TABLE ' || r.table_oid::regclass ||
                                ' ALTER COLUMN ' || quote_ident(r.column_name) ||
                                ' DROP DEFAULT';

                            -- Change the sequence dependency from auto to internal.
                            UPDATE pg_depend
                            SET deptype = 'i'
                            WHERE
                                classid = 'pg_class'::regclass AND
                                objid = r.sequence_oid AND
                                objsubid = 0 AND
                                deptype = 'a';

                            -- Mark the column as an identity column.
                            UPDATE pg_attribute
                            SET attidentity = 'd'
                            WHERE
                                attrelid = r.table_oid AND
                                attname = r.column_name;
                        END LOOP;
                END
            $$;
        SQL;

        $connection = $this->helper->getDbalConnection();

        try {
            $connection->executeQuery($sql);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }
}
