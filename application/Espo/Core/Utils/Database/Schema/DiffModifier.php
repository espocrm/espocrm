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
use Doctrine\DBAL\Schema\Column as Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Espo\Core\Utils\Database\Dbal\Types\LongtextType;
use Espo\Core\Utils\Database\Dbal\Types\MediumtextType;
use Espo\ORM\Name\Attribute;

class DiffModifier
{
    /**
     * @param RebuildMode::* $mode
     * @throws DbalException
     */
    public function modify(
        SchemaDiff $diff,
        Schema $schema,
        bool $secondRun = false,
        string $mode = RebuildMode::SOFT,
    ): bool {

        $reRun = false;
        $isHard = $mode === RebuildMode::HARD;

        $diff = $this->handleRemovedSequences($diff, $schema);

        $diff->removedTables = [];

        foreach ($diff->changedTables as $tableDiff) {
            $reRun = $this->amendTableDiff($tableDiff, $secondRun, $isHard) || $reRun;
        }

        return $reRun;
    }

    /**
     * @throws DbalException
     */
    private function amendTableDiff(TableDiff $tableDiff, bool $secondRun, bool $isHard): bool
    {
        $reRun = false;

        foreach ($tableDiff->removedColumns as $name => $column) {
            $reRun = $this->moveRemovedAutoincrementColumnToChanged($tableDiff, $column, $name) || $reRun;
        }

        if (!$isHard) {
            // Prevent column removal to prevent data loss.
            $tableDiff->removedColumns = [];

            // @todo Recreate $tableDiff w/o dropped columns. Or config.
        }

        // @todo Config.
        // Prevent column renaming as a not desired behavior.
        /*foreach ($tableDiff->getRenamedColumns() as $renamedColumn) {
            $addedName = strtolower($renamedColumn->getName());

            $tableDiff->addedColumns[$addedName] = $renamedColumn;
        }*/


        $tableDiff->renamedColumns = [];

        foreach ($tableDiff->getAddedColumns() as $column) {
            // Suppress autoincrement as need having a unique index first.
            $reRun = $this->amendAddedColumnAutoincrement($column) || $reRun;
        }

        foreach ($tableDiff->getModifiedColumns() as $columnDiff) {
            if (!$isHard) {
                // Prevent decreasing length for string columns to prevent data loss.
                $this->amendColumnDiffLength($columnDiff);
                // Prevent longtext => mediumtext to prevent data loss.
                $this->amendColumnDiffTextType($columnDiff);
                // Prevent changing collation.
                $this->amendColumnDiffCollation($columnDiff);
                // Prevent changing charset.
                $this->amendColumnDiffCharset($columnDiff);
            }

            // Prevent setting autoincrement in first run.
            if (!$secondRun) {
                $reRun = $this->amendColumnDiffAutoincrement($columnDiff) || $reRun;
            }
        }

        return $reRun;
    }

    private function amendColumnDiffLength(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$fromColumn) {
            return;
        }

        if (!$columnDiff->hasLengthChanged()) {
            return;
        }

        $fromLength = $fromColumn->getLength() ?? 255;
        $length = $column->getLength() ?? 255;

        if ($fromLength <= $length) {
            return;
        }

        $column->setLength($fromLength);
    }

    /**
     * @throws DbalException
     */
    private function amendColumnDiffTextType(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$fromColumn) {
            return;
        }

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

    private function amendColumnDiffCollation(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$fromColumn) {
            return;
        }

        $fromCollation = $fromColumn->getPlatformOption('collation');
        $collation = $column->getPlatformOption('collation');

        if (!$fromCollation || $fromCollation === $collation) {
            return;
        }

        $column->setPlatformOption('collation', $fromCollation);
    }

    private function amendColumnDiffCharset(ColumnDiff $columnDiff): void
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$fromColumn) {
            return;
        }

        $fromCharset = $fromColumn->getPlatformOption('charset');
        $charset = $column->getPlatformOption('charset');

        if (!$fromCharset || $fromCharset === $charset) {
            return;
        }

        $column->setPlatformOption('charset', $fromCharset);
    }

    private function amendColumnDiffAutoincrement(ColumnDiff $columnDiff): bool
    {
        $fromColumn = $columnDiff->getOldColumn();
        $column = $columnDiff->getNewColumn();

        if (!$fromColumn) {
            return false;
        }

        if (!$columnDiff->hasAutoIncrementChanged() || $fromColumn->getAutoincrement()) {
            return false;
        }

        $column
            ->setAutoincrement(false)
            ->setNotnull(false)
            ->setDefault(null);

        if ($column->getName() === Attribute::ID) {
            $column->setNotnull(true);
        }

        return true;
    }

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

    private function moveRemovedAutoincrementColumnToChanged(TableDiff $tableDiff, Column $column, string $name): bool
    {
        if (!$column->getAutoincrement()) {
            return false;
        }

        $newColumn = clone $column;

        $newColumn
            ->setAutoincrement(false)
            ->setNotnull(false)
            ->setDefault(null);

        $changedProperties = [
            'autoincrement',
            'notnull',
            'default',
        ];

        $tableDiff->changedColumns[$name] = new ColumnDiff($name, $newColumn, $changedProperties, $column);

        foreach ($tableDiff->removedIndexes as $indexName => $index) {
            if ($index->getColumns() === [$name]) {
                unset($tableDiff->removedIndexes[$indexName]);
            }
        }

        return true;
    }

    private static function unsetChangedColumnProperty(
        TableDiff $tableDiff,
        ColumnDiff $columnDiff,
        string $name,
        string $property
    ): void {

        if (count($columnDiff->changedProperties) === 1) {
            unset($tableDiff->changedColumns[$name]);
        }

        $columnDiff->changedProperties = array_diff($columnDiff->changedProperties, [$property]);
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

        $diff->removedSequences = array_values($droppedSequences);

        return $diff;
    }
}
