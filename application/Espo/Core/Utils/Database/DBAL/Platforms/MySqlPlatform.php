<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Core\Utils\Database\DBAL\Platforms;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Column;

class MySqlPlatform extends \Doctrine\DBAL\Platforms\MySqlPlatform
{
    public function getAlterTableSQL(TableDiff $diff)
    {
        $columnSql = array();
        $queryParts = array();
        if ($diff->newName !== false) {
            $queryParts[] = 'RENAME TO ' . $diff->newName;
        }

        //espo: It works not correctly. It can rename some existing fields
        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            //espo: remaned autoincrement field
            if ($column->getAutoincrement()) {
                $diff->removedColumns[$oldColumnName] = new Column($oldColumnName, $column->getType(), $column->toArray());

                $columnName = $column->getQuotedName($this);
                $diff->addedColumns[$columnName] = $column;
                continue;
            }
            //END espo

            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            /*$queryParts[] =  'CHANGE ' . $oldColumnName . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray); */
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray); //espo: fixed the problem
        } //espo: END

        foreach ($diff->removedColumns as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            //espo: remove autoincrement option
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
            }
            //END espo

            //$queryParts[] =  'DROP ' . $column->getQuotedName($this); //espo: no needs to remove columns
        }

        foreach ($diff->changedColumns as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            /* @var $columnDiff \Doctrine\DBAL\Schema\ColumnDiff */
            $column = $columnDiff->column;
            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);

            $queryParts[] =  'CHANGE ' . $this->espoQuote($columnDiff->oldColumnName) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        foreach ($diff->addedColumns as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        $sql = array();
        $tableSql = array();

        if ( ! $this->onSchemaAlterTable($diff, $tableSql)) {
            if (count($queryParts) > 0) {
                $sql[] = 'ALTER TABLE ' . $this->espoQuote($diff->name) . ' ' . implode(", ", $queryParts);
            }
            $sql = array_merge(
                $this->getPreAlterTableIndexForeignKeySQL($diff),
                $sql,
                $this->getPostAlterTableIndexForeignKeySQL($diff)
            );
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = array();
        $table = $diff->name;

        foreach ($diff->removedIndexes as $remKey => $remIndex) {

            foreach ($diff->addedIndexes as $addKey => $addIndex) {
                if ($remIndex->getColumns() == $addIndex->getColumns()) {

                    $type = '';
                    if ($addIndex->isUnique()) {
                        $type = 'UNIQUE ';
                    }

                    $query = 'ALTER TABLE ' . $this->espoQuote($table) . ' DROP INDEX ' . $remIndex->getName() . ', ';
                    $query .= 'ADD ' . $type . 'INDEX ' . $addIndex->getName();
                    $query .= ' (' . $this->getIndexFieldDeclarationListSQL($addIndex->getQuotedColumns($this)) . ')';

                    $sql[] = $query;

                    unset($diff->removedIndexes[$remKey]);
                    unset($diff->addedIndexes[$addKey]);

                    break;
                }
            }
        }

        $sql = array_merge($sql, parent::getPreAlterTableIndexForeignKeySQL($diff));

        return $sql;
    }

    public function getDropIndexSQL($index, $table=null)
    {
        if ($index instanceof Index) {
            $indexName = $index->getQuotedName($this);
        } else if(is_string($index)) {
            $indexName = $index;
        } else {
            throw new \InvalidArgumentException('MysqlPlatform::getDropIndexSQL() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.');
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        } else if(!is_string($table)) {
            throw new \InvalidArgumentException('MysqlPlatform::getDropIndexSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        if ($index instanceof Index && $index->isPrimary()) {
            // mysql primary keys are always named "PRIMARY",
            // so we cannot use them in statements because of them being keyword.
            return $this->getDropPrimaryKeySQL($table);
        }

        return 'DROP INDEX ' . $indexName . ' ON ' . $this->espoQuote($table);
    }

    protected function getDropPrimaryKeySQL($table)
    {
        return 'ALTER TABLE ' . $this->espoQuote($table) . ' DROP PRIMARY KEY';
    }

    public function getDropTemporaryTableSQL($table)
    {
        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        } else if(!is_string($table)) {
            throw new \InvalidArgumentException('getDropTableSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        return 'DROP TEMPORARY TABLE ' . $this->espoQuote($table);
    }

    //ESPO: fix problem with quoting table name
    public function espoQuote($name)
    {
        if ($name instanceof Table) {
            $name = $name->getQuotedName($this);
        }

        if (isset($name[0]) && $name[0] != '`') {
            $name = $this->quoteIdentifier($name);
        }
        return $name;
    }

    public function getCreateForeignKeySQL(\Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey, $table)
    {
        $query = 'ALTER TABLE ' . $this->espoQuote($table) . ' ADD ' . $this->getForeignKeyDeclarationSQL($foreignKey);

        return $query;
    }

    public function getIndexDeclarationSQL($name, Index $index)
    {
        $columns = $index->getQuotedColumns($this);

        if (count($columns) === 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        return $this->getCreateIndexSQLFlags($index) . 'INDEX ' . $this->espoQuote($name) . ' ('
             . $this->getIndexFieldDeclarationListSQL($columns)
             . ')';
    }

    public function getCreateIndexSQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }
        $name = $index->getQuotedName($this);
        $columns = $index->getQuotedColumns($this);

        if (count($columns) == 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        if ($index->isPrimary()) {
            return $this->getCreatePrimaryKeySQL($index, $table);
        }

        $query = 'CREATE ' . $this->getCreateIndexSQLFlags($index) . 'INDEX ' . $name . ' ON ' . $this->espoQuote($table);
        $query .= ' (' . $this->getIndexFieldDeclarationListSQL($columns) . ')';

        return $query;
    }

    public function getDropConstraintSQL($constraint, $table)
    {
        if ($constraint instanceof Constraint) {
            $constraint = $constraint->getQuotedName($this);
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        return 'ALTER TABLE ' . $this->espoQuote($table) . ' DROP CONSTRAINT ' . $constraint;
    }

    public function getDropForeignKeySQL($foreignKey, $table)
    {
        if ($foreignKey instanceof ForeignKeyConstraint) {
            $foreignKey = $foreignKey->getQuotedName($this);
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        return 'ALTER TABLE ' . $this->espoQuote($table) . ' DROP FOREIGN KEY ' . $foreignKey;
    }

    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        return 'ALTER TABLE ' . $this->espoQuote($table) . ' ADD PRIMARY KEY (' . $this->getIndexFieldDeclarationListSQL($index->getQuotedColumns($this)) . ')';
    }

    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        if (!isset($options['engine'])) {
            $options['engine'] = 'InnoDB';
        }

        if (!isset($options['charset'])) {
            $options['charset'] = 'utf8mb4';
        }

        if (!isset($options['collate'])) {
            $options['collate'] = 'utf8mb4_unicode_ci';
        }

        return parent::_getCreateTableSQL($tableName, $columns, $options);
    }

    public function getColumnCollationDeclarationSQL($collation)
    {
        return $this->getCollationFieldDeclaration($collation);
    }
    //end: ESPO
}