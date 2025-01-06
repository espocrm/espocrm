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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

class PostgresqlPlatform extends PostgreSQL100Platform
{
    private const TEXT_SEARCH_CONFIG = 'pg_catalog.simple';

    private ?string $textSearchConfig;

    public function setTextSearchConfig(?string $textSearchConfig): void
    {
        $this->textSearchConfig = $textSearchConfig;
    }

    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        return new PostgreSQLSchemaManager($connection, $this);
    }

    public function getCreateIndexSQL(Index $index, $table)
    {
        if (!$index->hasFlag('fulltext')) {
            return parent::getCreateIndexSQL($index, $table);
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        $name = $index->getQuotedName($this);
        $columns = $index->getColumns();

        if (count($columns) === 0) {
            throw new \InvalidArgumentException(sprintf(
                'Incomplete or invalid index definition %s on table %s',
                $name,
                $table,
            ));
        }

        $columnsPart = implode(" || ' ' || ", $index->getQuotedColumns($this));
        $partialPart = $this->getPartialIndexSQL($index);

        $textSearchConfig = $this->textSearchConfig ?? self::TEXT_SEARCH_CONFIG;
        $textSearchConfig = preg_replace('/[^A-Za-z0-9_.\-]+/', '', $textSearchConfig) ?? '';
        $configPart = $this->quoteStringLiteral($textSearchConfig);

        return "CREATE INDEX {$name} ON {$table} USING GIN (TO_TSVECTOR({$configPart}, {$columnsPart})) {$partialPart}";
    }
}
