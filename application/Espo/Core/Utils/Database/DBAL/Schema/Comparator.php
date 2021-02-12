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

namespace Espo\Core\Utils\Database\DBAL\Schema;

use Espo\Core\Utils\Database\DBAL\Traits\Schema\Comparator as ComparatorTrait;
use Doctrine\DBAL\{
    Types,
    Schema\Table,
    Schema\Column,
    Schema\TableDiff,
    Schema\ColumnDiff,
    Schema\Index,
    Schema\Comparator as OriginalComparator,
};

class Comparator extends OriginalComparator
{
    // Espo
    use ComparatorTrait;
    // Espo: end

    public function diffColumn(Column $column1, Column $column2)
    {
        $properties1 = $column1->toArray();
        $properties2 = $column2->toArray();

        $changedProperties = [];

        if (get_class($properties1['type']) !== get_class($properties2['type'])) {
            // Espo
            $this->espoFixTypeDiff($changedProperties, $column1, $column2);
            //$changedProperties[] = 'type';
            // Espo: end
        }

        foreach (['notnull', 'unsigned', 'autoincrement'] as $property) {
            if ($properties1[$property] === $properties2[$property]) {
                continue;
            }

            $changedProperties[] = $property;
        }

        // Null values need to be checked additionally as they tell whether to create or drop a default value.
        // null != 0, null != false, null != '' etc. This affects platform's table alteration SQL generation.
        if (
            ($properties1['default'] === null) !== ($properties2['default'] === null)
            || $properties1['default'] != $properties2['default']
        ) {
            $changedProperties[] = 'default';
        }

        if (
            ($properties1['type'] instanceof Types\StringType && ! $properties1['type'] instanceof Types\GuidType) ||
            $properties1['type'] instanceof Types\BinaryType
        ) {
            // check if value of length is set at all, default value assumed otherwise.
            $length1 = $properties1['length'] ?? 255;
            $length2 = $properties2['length'] ?? 255;

            // Espo: column length can only be increased
            /*if ($length1 !== $length2) {
                $changedProperties[] = 'length';
            }*/
            if ($length2 > $length1) {
                $changedProperties[] = 'length';
            }
            if ($length2 < $length1) {
                $column2->setLength($length1);
            }
            // Espo: end

            if ($properties1['fixed'] !== $properties2['fixed']) {
                $changedProperties[] = 'fixed';
            }
        } elseif ($properties1['type'] instanceof Types\DecimalType) {
            if (($properties1['precision'] ?? 10) !== ($properties2['precision'] ?? 10)) {
                $changedProperties[] = 'precision';
            }

            if ($properties1['scale'] !== $properties2['scale']) {
                $changedProperties[] = 'scale';
            }
        }

        // A null value and an empty string are actually equal for a comment so they should not trigger a change.
        if (
            $properties1['comment'] !== $properties2['comment'] &&
            ! ($properties1['comment'] === null && $properties2['comment'] === '') &&
            ! ($properties2['comment'] === null && $properties1['comment'] === '')
        ) {
            $changedProperties[] = 'comment';
        }

        $customOptions1 = $column1->getCustomSchemaOptions();
        $customOptions2 = $column2->getCustomSchemaOptions();

        foreach (array_merge(array_keys($customOptions1), array_keys($customOptions2)) as $key) {
            if (! array_key_exists($key, $properties1) || ! array_key_exists($key, $properties2)) {
                $changedProperties[] = $key;
            } elseif ($properties1[$key] !== $properties2[$key]) {
                $changedProperties[] = $key;
            }
        }

        $platformOptions1 = $column1->getPlatformOptions();
        $platformOptions2 = $column2->getPlatformOptions();

        foreach (array_keys(array_intersect_key($platformOptions1, $platformOptions2)) as $key) {
            if ($properties1[$key] === $properties2[$key]) {
                continue;
            }

            // Espo: skip collation changes
            if ($key == 'collation') {
                $column2->setPlatformOption('collation', $platformOptions1['collation']);
                continue;
            }
            // Espo: end

            $changedProperties[] = $key;
        }

        return array_unique($changedProperties);
    }

    public function diffTable(Table $fromTable, Table $toTable)
    {
        $changes                     = 0;
        $tableDifferences            = new TableDiff($fromTable->getName());
        $tableDifferences->fromTable = $fromTable;

        $fromTableColumns = $fromTable->getColumns();
        $toTableColumns   = $toTable->getColumns();

        /* See if all the columns in "from" table exist in "to" table */
        foreach ($toTableColumns as $columnName => $column) {
            if ($fromTable->hasColumn($columnName)) {
                continue;
            }

            $tableDifferences->addedColumns[$columnName] = $column;
            $changes++;
        }

        /* See if there are any removed columns in "to" table */
        foreach ($fromTableColumns as $columnName => $column) {
            // See if column is removed in "to" table.
            if (! $toTable->hasColumn($columnName)) {
                $tableDifferences->removedColumns[$columnName] = $column;
                $changes++;
                continue;
            }

            // See if column has changed properties in "to" table.
            $changedProperties = $this->diffColumn($column, $toTable->getColumn($columnName));

            if (count($changedProperties) === 0) {
                continue;
            }

            $columnDiff = new ColumnDiff($column->getName(), $toTable->getColumn($columnName), $changedProperties);

            $columnDiff->fromColumn                               = $column;
            $tableDifferences->changedColumns[$column->getName()] = $columnDiff;
            $changes++;
        }

        $this->detectColumnRenamings($tableDifferences);

        $fromTableIndexes = $fromTable->getIndexes();
        $toTableIndexes   = $toTable->getIndexes();

        /* See if all the indexes in "from" table exist in "to" table */
        foreach ($toTableIndexes as $indexName => $index) {
            if (($index->isPrimary() && $fromTable->hasPrimaryKey()) || $fromTable->hasIndex($indexName)) {
                continue;
            }

            $tableDifferences->addedIndexes[$indexName] = $index;
            $changes++;
        }

        /* See if there are any removed indexes in "to" table */
        foreach ($fromTableIndexes as $indexName => $index) {
            // See if index is removed in "to" table.
            if (
                ($index->isPrimary() && ! $toTable->hasPrimaryKey()) ||
                ! $index->isPrimary() && ! $toTable->hasIndex($indexName)
            ) {
                $tableDifferences->removedIndexes[$indexName] = $index;
                $changes++;
                continue;
            }

            // See if index has changed in "to" table.
            $toTableIndex = $index->isPrimary() ? $toTable->getPrimaryKey() : $toTable->getIndex($indexName);
            assert($toTableIndex instanceof Index);

            if (! $this->diffIndex($index, $toTableIndex)) {
                continue;
            }

            $tableDifferences->changedIndexes[$indexName] = $toTableIndex;
            $changes++;
        }

        $this->detectIndexRenamings($tableDifferences);

        $fromForeignKeys = $fromTable->getForeignKeys();
        $toForeignKeys   = $toTable->getForeignKeys();

        foreach ($fromForeignKeys as $fromKey => $fromConstraint) {
            foreach ($toForeignKeys as $toKey => $toConstraint) {
                if ($this->diffForeignKey($fromConstraint, $toConstraint) === false) {
                    unset($fromForeignKeys[$fromKey], $toForeignKeys[$toKey]);
                } else {
                    if (strtolower($fromConstraint->getName()) === strtolower($toConstraint->getName())) {
                        $tableDifferences->changedForeignKeys[] = $toConstraint;
                        $changes++;
                        unset($fromForeignKeys[$fromKey], $toForeignKeys[$toKey]);
                    }
                }
            }
        }

        foreach ($fromForeignKeys as $fromConstraint) {
            $tableDifferences->removedForeignKeys[] = $fromConstraint;
            $changes++;
        }

        foreach ($toForeignKeys as $toConstraint) {
            $tableDifferences->addedForeignKeys[] = $toConstraint;
            $changes++;
        }

        return $changes > 0 ? $tableDifferences : false;
    }

    /**
     * Try to find columns that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @return void
     */
    private function detectColumnRenamings(TableDiff $tableDifferences)
    {
        $renameCandidates = [];
        foreach ($tableDifferences->addedColumns as $addedColumnName => $addedColumn) {
            foreach ($tableDifferences->removedColumns as $removedColumn) {
                if (count($this->diffColumn($addedColumn, $removedColumn)) !== 0) {
                    continue;
                }

                $renameCandidates[$addedColumn->getName()][] = [$removedColumn, $addedColumn, $addedColumnName];
            }
        }

        foreach ($renameCandidates as $candidateColumns) {
            if (count($candidateColumns) !== 1) {
                continue;
            }

            [$removedColumn, $addedColumn] = $candidateColumns[0];
            $removedColumnName             = strtolower($removedColumn->getName());
            $addedColumnName               = strtolower($addedColumn->getName());

            if (isset($tableDifferences->renamedColumns[$removedColumnName])) {
                continue;
            }

            $tableDifferences->renamedColumns[$removedColumnName] = $addedColumn;
            unset(
                $tableDifferences->addedColumns[$addedColumnName],
                $tableDifferences->removedColumns[$removedColumnName]
            );
        }
    }

    /**
     * Try to find indexes that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @return void
     */
    private function detectIndexRenamings(TableDiff $tableDifferences)
    {
        $renameCandidates = [];

        // Gather possible rename candidates by comparing each added and removed index based on semantics.
        foreach ($tableDifferences->addedIndexes as $addedIndexName => $addedIndex) {
            foreach ($tableDifferences->removedIndexes as $removedIndex) {
                if ($this->diffIndex($addedIndex, $removedIndex)) {
                    continue;
                }

                $renameCandidates[$addedIndex->getName()][] = [$removedIndex, $addedIndex, $addedIndexName];
            }
        }

        foreach ($renameCandidates as $candidateIndexes) {
            // If the current rename candidate contains exactly one semantically equal index,
            // we can safely rename it.
            // Otherwise it is unclear if a rename action is really intended,
            // therefore we let those ambiguous indexes be added/dropped.
            if (count($candidateIndexes) !== 1) {
                continue;
            }

            [$removedIndex, $addedIndex] = $candidateIndexes[0];

            $removedIndexName = strtolower($removedIndex->getName());
            $addedIndexName   = strtolower($addedIndex->getName());

            if (isset($tableDifferences->renamedIndexes[$removedIndexName])) {
                continue;
            }

            // Espo: skip index renaming
            //$tableDifferences->renamedIndexes[$removedIndexName] = $addedIndex;
            // Espo: end
            unset(
                $tableDifferences->addedIndexes[$addedIndexName],
                $tableDifferences->removedIndexes[$removedIndexName]
            );
        }
    }
}
