<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Database\DBAL\Traits\Platforms;

use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\TextType;

use Doctrine\DBAL\Schema\{
    TableDiff,
    ColumnDiff,
    Column,
    Identifier,
};

trait MySQLPlatform
{
    public function getAlterTableSQL(TableDiff $diff)
    {
        $columnSql  = [];
        $queryParts = [];
        $newName    = $diff->getNewName();

        if ($newName !== false) {
            $queryParts[] = 'RENAME TO ' . $newName->getQuotedName($this);
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            // Espo: handle remained autoincrement column
            if ($column->getAutoincrement()) {
                $oldColumnOptions = array_diff_key($column->toArray(), array_flip(['name', 'type', 'collation']));
                $diff->removedColumns[$oldColumnName] = new Column($oldColumnName, $column->getType(), $oldColumnOptions);

                $columnName = $column->getQuotedName($this);
                $diff->addedColumns[$columnName] = $column;
                continue;
            }
            // Espo: end

            $oldColumnName          = new Identifier($oldColumnName);
            $columnArray            = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);

            // Espo: do not rename the column
            /* $queryParts[] = 'CHANGE ' . $oldColumnName->getQuotedName($this) . ' '
                   $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray); */
            $queryParts[]           =  'ADD '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
            // Espo: end
        }

        foreach ($diff->addedColumns as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnArray = array_merge($column->toArray(), [
                'comment' => $this->getColumnComment($column),
            ]);

            // Espo: Unable to create autoindex column in existing table fix
            if ($column->getAutoincrement()) {
                $columnArray['unique'] = true;

                $columnName = $column->getQuotedName($this);

                foreach ($diff->addedIndexes as $indexName => $index) {
                    if ($index->getColumns() === [$columnName]) {
                        $columnArray['uniqueDeclaration'] = $index->getName() . "(" . $columnName . ")";
                        unset($diff->addedIndexes[$indexName]);
                    }
                }
            }
            // Espo: end

            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        // Espo: remove autoincrement column
        $autoincrementRemovedIndexes = [];
        // Espo: end

        foreach ($diff->removedColumns as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            // Espo: remove autoincrement option
            if ($column->getAutoincrement()) {

                $columnName = $column->getQuotedName($this);

                $changedColumn = clone $column;
                $changedColumn->setNotNull(false);
                $changedColumn->setAutoincrement(false);

                $changedProperties = array(
                    'notnull',
                    'autoincrement',
                );

                $diff->changedColumns[$columnName] = new ColumnDiff($columnName, $changedColumn, $changedProperties, $column);

                foreach ($diff->removedIndexes as $indexName => $index) {
                    if ($index->getColumns() === [$columnName]) {
                        $autoincrementRemovedIndexes[$indexName] = $index;
                        unset($diff->removedIndexes[$indexName]);
                    }
                }
            }
            // Espo: end

            // Espo: No need to delete the column
            //$queryParts[] =  'DROP ' . $column->getQuotedName($this);
            // Espo: end
        }

        foreach ($diff->changedColumns as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            $column      = $columnDiff->column;
            $columnArray = $column->toArray();

            // Don't propagate default value changes for unsupported column types.
            if (
                $columnDiff->hasChanged('default') &&
                count($columnDiff->changedProperties) === 1 &&
                ($columnArray['type'] instanceof TextType || $columnArray['type'] instanceof BlobType)
            ) {
                continue;
            }

            // Espo: Unable to create autoindex column in existing table fix
            if ($column->getAutoincrement()) {
                $columnArray['unique'] = true;

                $columnName = $column->getQuotedName($this);

                foreach ($diff->addedIndexes as $indexName => $index) {
                    if ($index->getColumns() === [$columnName]) {
                        $columnArray['uniqueDeclaration'] = $index->getName() . "(" . $columnName . ")";
                        unset($diff->addedIndexes[$indexName]);
                    }
                }
            }
            // Espo: end

            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[]           =  'CHANGE ' . ($columnDiff->getOldColumnName()->getQuotedName($this)) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        if (isset($diff->addedIndexes['primary'])) {
            $keyColumns   = array_unique(array_values($diff->addedIndexes['primary']->getColumns()));
            $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
            unset($diff->addedIndexes['primary']);
        } elseif (isset($diff->changedIndexes['primary'])) {
            // Necessary in case the new primary key includes a new auto_increment column
            foreach ($diff->changedIndexes['primary']->getColumns() as $columnName) {
                if (isset($diff->addedColumns[$columnName]) && $diff->addedColumns[$columnName]->getAutoincrement()) {
                    $keyColumns   = array_unique(array_values($diff->changedIndexes['primary']->getColumns()));
                    $queryParts[] = 'DROP PRIMARY KEY';
                    $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
                    unset($diff->changedIndexes['primary']);
                    break;
                }
            }
        }

        $sql      = [];
        $tableSql = [];

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            if (count($queryParts) > 0) {
                $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' '
                    . implode(', ', $queryParts);
            }

            // Espo: remove autoincrement column
            if (!empty($autoincrementRemovedIndexes)) {
                $tableName = $diff->getName($this)->getQuotedName($this);

                foreach ($autoincrementRemovedIndexes as $index) {
                    $sql[] = $this->getDropIndexSQL($index, $tableName);
                }
            }
            // Espo: end

            $sql = array_merge(
                $this->getPreAlterTableIndexForeignKeySQL($diff),
                $sql,
                $this->getPostAlterTableIndexForeignKeySQL($diff)
            );
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    public function getClobTypeDeclarationSQL(array $column)
    {
        if (! empty($column['length']) && is_numeric($column['length'])) {
            $length = $column['length'];

            if ($length <= static::LENGTH_LIMIT_TINYTEXT) {
                return 'TINYTEXT';
            }

            if ($length <= static::LENGTH_LIMIT_TEXT) {
                return 'TEXT';
            }

            if ($length > static::LENGTH_LIMIT_MEDIUMTEXT) {
                return 'LONGTEXT';
            }
        }

        return 'MEDIUMTEXT';
    }

    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        if (!isset($options['charset'])) {
            $options['charset'] = 'utf8mb4';
        }

        return parent::_getCreateTableSQL($name, $columns, $options);
    }

    public function getColumnDeclarationSQL($name, array $column)
    {
        if (isset($column['columnDefinition'])) {
            $declaration = $this->getCustomTypeDeclarationSQL($column);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($column);

            $charset = ! empty($column['charset']) ?
                ' ' . $this->getColumnCharsetDeclarationSQL($column['charset']) : '';

            $collation = ! empty($column['collation']) ?
                ' ' . $this->getColumnCollationDeclarationSQL($column['collation']) : '';

            $notnull = ! empty($column['notnull']) ? ' NOT NULL' : '';

            $unique = ! empty($column['unique']) ?
                ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = ! empty($column['check']) ? ' ' . $column['check'] : '';

            $typeDecl    = $column['type']->getSQLDeclaration($column, $this);
            $declaration = $typeDecl . $charset . $default . $notnull . $unique . $check . $collation;

            // Espo: Unable to create autoindex column in existing table fix
            if (!empty($column['uniqueDeclaration'])) {
                $declaration = $typeDecl . $charset . $default . $notnull . $check . $collation
                    . ", ADD". $unique . " " . $column['uniqueDeclaration'];
            }
            // Espo: end

            if ($this->supportsInlineColumnComments() && isset($column['comment']) && $column['comment'] !== '') {
                $declaration .= ' ' . $this->getInlineColumnCommentSQL($column['comment']);
            }
        }

        return $name . ' ' . $declaration;
    }
}
