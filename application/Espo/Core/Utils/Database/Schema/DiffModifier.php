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

namespace Espo\Core\Utils\Database\Schema;

use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Column as Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Espo\Core\Utils\Database\Dbal\Types\LongtextType;
use Espo\Core\Utils\Database\Dbal\Types\MediumtextType;
use Espo\Core\Utils\Database\Helper;
use Espo\ORM\Name\Attribute;

class DiffModifier
{
    private const int DEFAULT_VARCHAR_LENGTH = 255;

    public function __construct(
        private Helper $helper,
    ) {}


    /**
     * @param RebuildMode::* $mode
     * @throws DbalException
     */
    public function modify(
        SchemaDiff &$diff,
        Schema $schema,
        bool $secondRun = false,
        string $mode = RebuildMode::SOFT,
    ): bool {

        $reRun = false;
        $isHard = $mode === RebuildMode::HARD;

        // @todo Test.
        $diff = $this->handleRemovedSequences($diff, $schema);

        $alteredTables = [];

        foreach ($diff->getAlteredTables() as $tableDiff) {
            [$newTableDiff, $itemReRun] = $this->amendTableDiff($tableDiff, $secondRun, $isHard);

            if ($itemReRun) {
                $reRun = true;
            }

            $alteredTables[] = $newTableDiff;
        }

        $diff = new SchemaDiff(
            createdSchemas: $diff->getCreatedSchemas(),
            droppedSchemas: [],
            createdTables: $diff->getCreatedTables(),
            alteredTables: $alteredTables,
            droppedTables: [],
            createdSequences: $diff->getCreatedSequences(),
            alteredSequences: $diff->getAlteredSequences(),
            droppedSequences: $diff->getDroppedSequences(),
        );

        return $reRun;
    }

    /**
     * @return array{TableDiff, bool}
     * @throws DbalException
     */
    private function amendTableDiff(TableDiff $tableDiff, bool $secondRun, bool $isHard): array
    {
        $reRun = false;

        foreach ($tableDiff->getDroppedColumns() as $column) {
            $itemTableDiff = $this->moveRemovedAutoincrementColumnToChanged($tableDiff, $column);

            if ($itemTableDiff) {
                $tableDiff = $itemTableDiff;

                $reRun = true;
            }

            if (!$isHard && $secondRun) {
                $itemTableDiff = $this->moveDroppedNotNullableToChangedNullable($tableDiff, $column);

                if ($itemTableDiff) {
                    $tableDiff = $itemTableDiff;
                }
            }
        }

        if (!$isHard) {
            $tableDiff = $this->cloneTableDiffWithoutDroppedColumns($tableDiff);
        }

        if ($this->helper->getDbalConnection()->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            foreach ($tableDiff->getAddedColumns() as $column) {
                $reRun = $this->amendAddedColumnAutoincrement($column) || $reRun;
            }
        }

        $changedColumns = [];

        foreach ($tableDiff->getChangedColumns() as $name => $columnDiff) {
            $newColumnDiff = $columnDiff;

            if (!$isHard) {
                $this->amendColumnDiffLength($columnDiff);
                $this->amendColumnDiffTextType($columnDiff);
                $this->amendColumnDiffCollation($columnDiff);
                $this->amendColumnDiffCharset($columnDiff);
            }

            // Prevent setting autoincrement in first run.
            if (!$secondRun) {
                [$newColumnDiff, $itemReRun] = $this->amendColumnDiffAutoincrement($columnDiff);

                if ($itemReRun) {
                    $reRun = $itemReRun;
                }
            }

            $changedColumns[$name] = $newColumnDiff;
        }

        $tableDiff = $this->cloneTableDiffWithChangedColumns($tableDiff, $changedColumns);

        return [$tableDiff, $reRun];
    }

    /**
     * Prevent decreasing length for string columns to prevent data loss.
     */
    private function amendColumnDiffLength(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$columnDiff->hasLengthChanged()) {
            return;
        }

        $fromLength = $fromColumn->getLength() ?? self::DEFAULT_VARCHAR_LENGTH;
        $length = $column->getLength() ?? self::DEFAULT_VARCHAR_LENGTH;

        if ($fromLength <= $length) {
            return;
        }

        $column->setLength($fromLength);
    }

    /**
     * Prevent longtext => mediumtext to prevent data loss.
     *
     * @throws DbalException
     */
    private function amendColumnDiffTextType(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$columnDiff->hasTypeChanged()) {
            return;
        }

        $fromType = $fromColumn->getType();
        $type = $column->getType();

        if (
            !$fromType instanceof TextType ||
            !$type instanceof TextType
        ) {
            return;
        }

        $typePriority = [
            Types::TEXT,
            MediumtextType::NAME,
            LongtextType::NAME,
        ];

        $fromName = $fromType::getTypeRegistry()->lookupName($fromType);
        $toName = $type::getTypeRegistry()->lookupName($type);

        $fromIndex = array_search($fromName, $typePriority);
        $index = array_search($toName, $typePriority);

        if ($index >= $fromIndex) {
            return;
        }

        $column->setType(Type::getType($fromName));
    }

    /**
     * Prevent changing collation.
     */
    private function amendColumnDiffCollation(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        $fromCollation = $fromColumn->getCollation();
        $collation = $column->getCollation();

        if (!$fromCollation || $fromCollation === $collation) {
            return;
        }

        $column->setPlatformOption('collation', $fromCollation);
    }

    /**
     * Prevent changing charset.
     */
    private function amendColumnDiffCharset(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        $fromCharset = $fromColumn->getCharset();
        $charset = $column->getCharset();

        if (!$fromCharset || $fromCharset === $charset) {
            return;
        }

        $column->setPlatformOption('charset', $fromCharset);
    }

    /**
     * @param ColumnDiff $columnDiff
     * @return array{ColumnDiff, bool}
     */
    private function amendColumnDiffAutoincrement(ColumnDiff $columnDiff): array
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$columnDiff->hasAutoIncrementChanged() /*|| $fromColumn->getAutoincrement()*/) {
            return [$columnDiff, false];
        }

        $notNull = false;

        if ($fromColumn->getAutoincrement()) {
            // Need to preserve while the sequence is still there.
            // To be set to false in the second run.
            $notNull = $fromColumn->getNotnull();
        }

        $column
            ->setAutoincrement(false)
            ->setNotnull($notNull)
            ->setDefault(null);

        $name = $column->getObjectName()->getIdentifier()->getValue();

        if ($name === Attribute::ID) {
            $column->setNotnull(true);
        }

        return [$columnDiff, true];
    }

    /**
     * Suppress autoincrement as need having a unique index first.
     */
    private function amendAddedColumnAutoincrement(Column $column): bool
    {
        if (!$column->getAutoincrement()) {
            return false;
        }

        $column
            ->setAutoincrement(false)
            ->setNotnull(false)
            ->setDefault(null);

        return true;
    }

    private function moveRemovedAutoincrementColumnToChanged(TableDiff $tableDiff, Column $column): ?TableDiff
    {
        if (!$column->getAutoincrement()) {
            return null;
        }

        $name = $column->getObjectName()->getIdentifier()->getValue();

        $newColumn = clone $column;

        $newColumn
            ->setAutoincrement(false)
            ->setNotnull(false)
            ->setDefault(null);

        $columnDiff = new ColumnDiff(
            oldColumn: $column,
            newColumn: $newColumn,
        );

        $changedColumns = [...$tableDiff->getChangedColumns(), $name => $columnDiff];

        $tableDiff = $this->cloneTableDiffWithChangedColumns($tableDiff, $changedColumns);

        $droppedIndexes = [];

        foreach ($tableDiff->getDroppedIndexes() as $index) {
            if (
                count($index->getIndexedColumns()) === 1 &&
                $index->getIndexedColumns()[0]->getColumnName()->toString() === $name
            ) {
                continue;
            }

            $droppedIndexes[] = $index;
        }

        return $this->cloneTableDiffWithDroppedIndexes($tableDiff, $droppedIndexes);
    }

    /**
     * After autoincrement is removed, the column is still not-nullable after the first run.
     * Otherwise, a conflict with the sequence occurs. Here, it is set to nullable.
     */
    private function moveDroppedNotNullableToChangedNullable(TableDiff $tableDiff, Column $column): ?TableDiff
    {
        if (!$column->getNotnull()) {
            return null;
        }

        $newColumn = clone $column;

        $newColumn
            ->setNotnull(false);

        $columnDiff = new ColumnDiff(
            oldColumn: $column,
            newColumn: $newColumn,
        );

        $name = $column->getObjectName()->getIdentifier()->getValue();

        $changedColumns = [...$tableDiff->getChangedColumns(), $name => $columnDiff];

        return $this->cloneTableDiffWithChangedColumns($tableDiff, $changedColumns);
    }

    /**
     * DBAL does not handle autoincrement columns that are not primary keys,
     * making them dropped.
     */
    private function handleRemovedSequences(SchemaDiff $diff, Schema $schema): SchemaDiff
    {
        $droppedSequences = $diff->getDroppedSequences();

        if ($droppedSequences === []) {
            return $diff;
        }

        foreach ($droppedSequences as $i => $sequence) {
            foreach ($schema->getTables() as $table) {
                $namespace = $table->getNamespaceName();
                $tableName = $table->getShortestName($namespace);

                foreach ($table->getColumns() as $column) {
                    if (!$column->getAutoincrement()) {
                        continue;
                    }

                    $sequenceName = $sequence->getShortestName($namespace);

                    $tableSequenceName = sprintf('%s_%s_seq', $tableName, $column->getShortestName($namespace));

                    if ($tableSequenceName !== $sequenceName) {
                        continue;
                    }

                    unset($droppedSequences[$i]);

                    continue 3;
                }
            }
        }

        $droppedSequences = array_values($droppedSequences);

        return new SchemaDiff(
            createdSchemas: $diff->getCreatedSchemas(),
            droppedSchemas: $diff->getDroppedSchemas(),
            createdTables: $diff->getCreatedTables(),
            alteredTables: $diff->getAlteredTables(),
            droppedTables: $diff->getDroppedTables(),
            createdSequences: $diff->getCreatedSequences(),
            alteredSequences: $diff->getAlteredSequences(),
            droppedSequences: $droppedSequences,
        );
    }

    private function cloneTableDiffWithoutDroppedColumns(TableDiff $tableDiff): TableDiff
    {
        return new TableDiff(
            oldTable: $tableDiff->getOldTable(),
            addedColumns: $tableDiff->getAddedColumns(),
            changedColumns: $tableDiff->getChangedColumns(),
            // Prevents column removal to prevent data loss.
            droppedColumns: [],
            addedIndexes: $tableDiff->getAddedIndexes(),
            modifiedIndexes: $tableDiff->getModifiedIndexes(),
            droppedIndexes: $tableDiff->getDroppedIndexes(),
            renamedIndexes: $tableDiff->getRenamedIndexes(),
            addedForeignKeys: $tableDiff->getAddedForeignKeys(),
            modifiedForeignKeys: $tableDiff->getModifiedForeignKeys(),
            droppedForeignKeys: $tableDiff->getDroppedForeignKeys(),
        );
    }

    /**
     * @param array<string, ColumnDiff> $changedColumns
     */
    private function cloneTableDiffWithChangedColumns(TableDiff $tableDiff, array $changedColumns): TableDiff
    {
        return new TableDiff(
            oldTable: $tableDiff->getOldTable(),
            addedColumns: $tableDiff->getAddedColumns(),
            changedColumns: $changedColumns,
            droppedColumns: $tableDiff->getDroppedColumns(),
            addedIndexes: $tableDiff->getAddedIndexes(),
            modifiedIndexes: $tableDiff->getModifiedIndexes(),
            droppedIndexes: $tableDiff->getDroppedIndexes(),
            renamedIndexes: $tableDiff->getRenamedIndexes(),
            addedForeignKeys: $tableDiff->getAddedForeignKeys(),
            modifiedForeignKeys: $tableDiff->getModifiedForeignKeys(),
            droppedForeignKeys: $tableDiff->getDroppedForeignKeys(),
        );
    }

    /**
     * @param Index[] $droppedIndexes
     */
    private function cloneTableDiffWithDroppedIndexes(TableDiff $tableDiff, array $droppedIndexes): TableDiff
    {
        return new TableDiff(
            oldTable: $tableDiff->getOldTable(),
            addedColumns: $tableDiff->getAddedColumns(),
            changedColumns: $tableDiff->getChangedColumns(),
            droppedColumns: $tableDiff->getDroppedColumns(),
            addedIndexes: $tableDiff->getAddedIndexes(),
            modifiedIndexes: $tableDiff->getModifiedIndexes(),
            droppedIndexes: $droppedIndexes,
            renamedIndexes: $tableDiff->getRenamedIndexes(),
            addedForeignKeys: $tableDiff->getAddedForeignKeys(),
            modifiedForeignKeys: $tableDiff->getModifiedForeignKeys(),
            droppedForeignKeys: $tableDiff->getDroppedForeignKeys(),
        );
    }
}
