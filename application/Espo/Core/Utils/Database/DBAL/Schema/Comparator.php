<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Database\DBAL\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\ColumnDiff;

class Comparator extends \Doctrine\DBAL\Schema\Comparator
{
    public function diffColumn(Column $column1, Column $column2)
    {
        $changedProperties = array();
        if ( $column1->getType() != $column2->getType() ) {

            //espo: fix problem with executing query for custom types
            $column1DbTypeName = method_exists($column1->getType(), 'getDbTypeName') ? $column1->getType()->getDbTypeName() : $column1->getType()->getName();
            $column2DbTypeName = method_exists($column2->getType(), 'getDbTypeName') ? $column2->getType()->getDbTypeName() : $column2->getType()->getName();

            if (strtolower($column1DbTypeName) != strtolower($column2DbTypeName)) {
                $changedProperties[] = 'type';
            }
            //END: espo
        }

        if ($column1->getNotnull() != $column2->getNotnull()) {
            $changedProperties[] = 'notnull';
        }

        if ($column1->getDefault() != $column2->getDefault()) {
            $changedProperties[] = 'default';
        }

        if ($column1->getUnsigned() != $column2->getUnsigned()) {
            $changedProperties[] = 'unsigned';
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\StringType) {
            // check if value of length is set at all, default value assumed otherwise.
            $length1 = $column1->getLength() ?: 255;
            $length2 = $column2->getLength() ?: 255;

            /** Espo: column length can be increased only */
            /*if ($length1 != $length2) {
                $changedProperties[] = 'length';
            }*/
            if ($length2 > $length1) {
                $changedProperties[] = 'length';
            }
            /** Espo: end */

            if ($column1->getFixed() != $column2->getFixed()) {
                $changedProperties[] = 'fixed';
            }
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\TextType) {
            $length1 = $column1->getLength() ?: 16777215/* mediumtext length*/;
            $length2 = $column2->getLength() ?: 16777215;

            if ($length2 > $length1) {
                $changedProperties[] = 'length';
            }
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\DecimalType) {
            if (($column1->getPrecision()?:10) != ($column2->getPrecision()?:10)) {
                $changedProperties[] = 'precision';
            }
            if ($column1->getScale() != $column2->getScale()) {
                $changedProperties[] = 'scale';
            }
        }

        if ($column1->getAutoincrement() != $column2->getAutoincrement()) {
            $changedProperties[] = 'autoincrement';
        }

        // only allow to delete comment if its set to '' not to null.
        if ($column1->getComment() !== null && $column1->getComment() != $column2->getComment()) {
            $changedProperties[] = 'comment';
        }

        $options1 = $column1->getCustomSchemaOptions();
        $options2 = $column2->getCustomSchemaOptions();

        $commonKeys = array_keys(array_intersect_key($options1, $options2));

        foreach ($commonKeys as $key) {
            if ($options1[$key] !== $options2[$key]) {
                $changedProperties[] = $key;
            }
        }

        $diffKeys = array_keys(array_diff_key($options1, $options2) + array_diff_key($options2, $options1));

        $changedProperties = array_merge($changedProperties, $diffKeys);

        /** Espo: do not change a field length while changing other parameters */
        if (!empty($changedProperties) && !in_array('length', $changedProperties) && $column1->getType() instanceof \Doctrine\DBAL\Types\StringType) {
            $length1 = $column1->getLength() ?: 255;
            $length2 = $column2->getLength() ?: 255;

            if ($length1 > $length2) {
                $changedProperties[] = 'length';
                $column2->setLength($length1);
            }
        }
        /** Espo: end */

        return $changedProperties;
    }

    public function diffTable(Table $table1, Table $table2)
    {
        $changes = 0;
        $tableDifferences = new TableDiff($table1->getName());
        $tableDifferences->fromTable = $table1;

        $table1Columns = $table1->getColumns();
        $table2Columns = $table2->getColumns();

        /* See if all the fields in table 1 exist in table 2 */
        foreach ( $table2Columns as $columnName => $column ) {
            if ( !$table1->hasColumn($columnName) ) {
                $tableDifferences->addedColumns[$columnName] = $column;
                $changes++;
            }
        }
        /* See if there are any removed fields in table 2 */
        foreach ( $table1Columns as $columnName => $column ) {
            if ( !$table2->hasColumn($columnName) ) {
                $tableDifferences->removedColumns[$columnName] = $column;
                $changes++;
            }
        }

        foreach ( $table1Columns as $columnName => $column ) {
            if ( $table2->hasColumn($columnName) ) {
                $changedProperties = $this->diffColumn( $column, $table2->getColumn($columnName) );
                if (count($changedProperties) ) {
                    $columnDiff = new ColumnDiff($column->getName(), $table2->getColumn($columnName), $changedProperties);
                    $columnDiff->fromColumn = $column;
                    $tableDifferences->changedColumns[$column->getName()] = $columnDiff;
                    $changes++;
                }
            }
        }

        $this->detectColumnRenamings($tableDifferences);

        $table1Indexes = $table1->getIndexes();
        $table2Indexes = $table2->getIndexes();

        foreach ($table2Indexes as $index2Name => $index2Definition) {
            foreach ($table1Indexes as $index1Name => $index1Definition) {
                if ($this->diffIndex($index1Definition, $index2Definition) === false) {
                    unset($table1Indexes[$index1Name]);
                    unset($table2Indexes[$index2Name]);
                } else {
                    if ($index1Name == $index2Name) {
                        /*espo*/ if (isset($table2Indexes[$index2Name])) { /*espo*/
                            $tableDifferences->changedIndexes[$index2Name] = $table2Indexes[$index2Name];
                            unset($table1Indexes[$index1Name]);
                            unset($table2Indexes[$index2Name]);
                            $changes++;
                        /*espo*/ } /*espo*/
                    }
                }
            }
        }

        foreach ($table1Indexes as $index1Name => $index1Definition) {
            $tableDifferences->removedIndexes[$index1Name] = $index1Definition;
            $changes++;
        }

        foreach ($table2Indexes as $index2Name => $index2Definition) {
            $tableDifferences->addedIndexes[$index2Name] = $index2Definition;
            $changes++;
        }

        $fromFkeys = $table1->getForeignKeys();
        $toFkeys = $table2->getForeignKeys();

        foreach ($fromFkeys as $key1 => $constraint1) {
            foreach ($toFkeys as $key2 => $constraint2) {
                if($this->diffForeignKey($constraint1, $constraint2) === false) {
                    unset($fromFkeys[$key1]);
                    unset($toFkeys[$key2]);
                } else {
                    if (strtolower($constraint1->getName()) == strtolower($constraint2->getName())) {
                        $tableDifferences->changedForeignKeys[] = $constraint2;
                        $changes++;
                        unset($fromFkeys[$key1]);
                        unset($toFkeys[$key2]);
                    }
                }
            }
        }

        foreach ($fromFkeys as $constraint1) {
            $tableDifferences->removedForeignKeys[] = $constraint1;
            $changes++;
        }

        foreach ($toFkeys as $constraint2) {
            $tableDifferences->addedForeignKeys[] = $constraint2;
            $changes++;
        }

        return $changes ? $tableDifferences : false;
    }

    private function detectColumnRenamings(TableDiff $tableDifferences)
    {
        $renameCandidates = array();
        foreach ($tableDifferences->addedColumns as $addedColumnName => $addedColumn) {
            foreach ($tableDifferences->removedColumns as $removedColumn) {
                if (count($this->diffColumn($addedColumn, $removedColumn)) == 0) {
                    $renameCandidates[$addedColumn->getName()][] = array($removedColumn, $addedColumn, $addedColumnName);
                }
            }
        }

        foreach ($renameCandidates as $candidateColumns) {
            if (count($candidateColumns) == 1) {
                list($removedColumn, $addedColumn) = $candidateColumns[0];
                $removedColumnName = strtolower($removedColumn->getName());
                $addedColumnName = strtolower($addedColumn->getName());

                if ( ! isset($tableDifferences->renamedColumns[$removedColumnName])) {
                    $tableDifferences->renamedColumns[$removedColumnName] = $addedColumn;
                    unset($tableDifferences->addedColumns[$addedColumnName]);
                    unset($tableDifferences->removedColumns[$removedColumnName]);
                }
            }
        }
    }
}
