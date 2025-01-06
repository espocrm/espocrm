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

namespace Espo\Core\Utils\Database\Schema\RebuildActions;

use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Schema\Schema as DbalSchema;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Database\Schema\RebuildAction;
use Espo\Core\Utils\Log;

use Exception;

class PrepareForFulltextIndex implements RebuildAction
{
    public function __construct(
        private Helper $helper,
        private Log $log
    ) {}

    /**
     * @throws DbalException
     */
    public function process(DbalSchema $oldSchema, DbalSchema $newSchema): void
    {
        if ($oldSchema->getTables() === []) {
            return;
        }

        $connection = $this->helper->getDbalConnection();
        $pdo = $this->helper->getPDO();

        foreach ($newSchema->getTables() as $table) {
            $tableName = $table->getName();
            $indexes = $table->getIndexes();

            foreach ($indexes as $index) {
                if (!$index->hasFlag('fulltext')) {
                    continue;
                }

                $columns = $index->getColumns();

                foreach ($columns as $columnName) {
                    $sql = "SHOW FULL COLUMNS FROM `" . $tableName . "` WHERE Field = " . $pdo->quote($columnName);

                    try {
                        /** @var array{Type: string, Collation: string} $row */
                        $row = $connection->fetchAssociative($sql);
                    } catch (Exception) {
                        continue;
                    }

                    switch (strtoupper($row['Type'])) {
                        case 'LONGTEXT':
                            $alterSql =
                                "ALTER TABLE `{$tableName}` " .
                                "MODIFY `{$columnName}` MEDIUMTEXT COLLATE " . $row['Collation'];

                            $this->log->info('SCHEMA, Execute Query: ' . $alterSql);

                            $connection->executeQuery($alterSql);

                            break;
                    }
                }
            }
        }
    }
}
