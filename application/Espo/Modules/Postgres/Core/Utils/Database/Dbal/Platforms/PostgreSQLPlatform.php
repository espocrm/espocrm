<?php

namespace Espo\Modules\Postgres\Core\Utils\Database\Dbal\Platforms;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

class PostgreSQLPlatform extends \Doctrine\DBAL\Platforms\PostgreSQL100Platform
{
    public function getIndexFieldDeclarationListSQL($columnsOrIndex): string
    {
        if ($columnsOrIndex instanceof Index && $columnsOrIndex->hasFlag('fulltext')) {
            return implode(', ', array_map(
                static fn($column) => "to_tsvector('english', \"$column\")",
                $columnsOrIndex->getQuotedColumns($this)
            ));
        }

        return parent::getIndexFieldDeclarationListSQL($columnsOrIndex);
    }

    public function getCreateIndexSQL(Index $index, $table): string
    {
        if (!$index->hasFlag('fulltext')) {
            return parent::getCreateIndexSQL($index, $table);
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        $table = "$table USING GIN";

        return parent::getCreateIndexSQL($index, $table);
    }

}
