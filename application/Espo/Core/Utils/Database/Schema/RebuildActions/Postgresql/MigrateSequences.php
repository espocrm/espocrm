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
            CREATE OR REPLACE FUNCTION upgrade_serial_to_identity(tbl regclass, col name)
                RETURNS void
                LANGUAGE plpgsql
                AS $$
                DECLARE
                    colnum smallint;
                    seqid oid;
                    count int;
                BEGIN
                -- Find column number.
                SELECT attnum INTO colnum FROM pg_attribute WHERE attrelid = tbl AND attname = col;
                IF NOT FOUND THEN
                    RAISE EXCEPTION 'column does not exist';
                END IF;

                -- Find sequence.
                SELECT INTO seqid objid
                FROM pg_depend
                WHERE
                    (refclassid, refobjid, refobjsubid) = ('pg_class'::regclass, tbl, colnum) AND
                    classid = 'pg_class'::regclass AND
                    objsubid = 0 AND
                    deptype = 'a';

                GET DIAGNOSTICS count = ROW_COUNT;
                IF count < 1 THEN
                    RAISE EXCEPTION 'no linked sequence found';
                ELSIF count > 1 THEN
                    RAISE EXCEPTION 'more than one linked sequence found';
                END IF;

                -- Drop the default.
                EXECUTE 'ALTER TABLE ' || tbl || ' ALTER COLUMN ' || quote_ident(col) || ' DROP DEFAULT';

                -- Change the dependency between column and sequence to internal.
                UPDATE pg_depend
                SET deptype = 'i'
                WHERE
                    (classid, objid, objsubid) = ('pg_class'::regclass, seqid, 0) AND
                    deptype = 'a';

                -- Mark the column as identity column.
                UPDATE pg_attribute
                SET attidentity = 'd'
                WHERE
                    attrelid = tbl AND
                    attname = col;
                END;
            $$;

            DO $$
                DECLARE
                    r record;
                BEGIN
                    FOR r IN
                        SELECT
                            a.attrelid::regclass AS table_name,
                            a.attname AS column_name
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
                            PERFORM
                                upgrade_serial_to_identity(r.table_name, r.column_name);
                        END LOOP;
                END;
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
