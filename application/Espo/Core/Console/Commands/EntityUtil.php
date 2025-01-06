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

namespace Espo\Core\Console\Commands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;

use Espo\Tools\EntityManager\Rename\Renamer;
use Espo\Tools\EntityManager\Rename\FailReason as RenameFailReason;

/**
 * @noinspection PhpUnused
 */
class EntityUtil implements Command
{
    public function __construct(private Renamer $renamer)
    {}

    public function run(Params $params, IO $io): void
    {
        $subCommand = $params->getArgument(0);

        if (!$subCommand) {
            $io->writeLine("No sub-command specified.");

            return;
        }

        if ($subCommand === 'rename') {
            $this->runRename($params, $io);
        }
    }

    private function runRename(Params $params, IO $io): void
    {
        $entityType = $params->getOption('entityType');
        $newName = $params->getOption('newName');

        if (!$entityType) {
            $io->writeLine("No --entity-type option specified.");
        }

        if (!$newName) {
            $io->writeLine("No --new-name option specified.");
        }

        if (!$entityType || !$newName) {
            return;
        }

        $result = $this->renamer->process($entityType, $newName, $io);

        $io->writeLine("");

        if (!$result->isFail()) {
            $io->writeLine("Finished.");

            return;
        }

        $io->setExitStatus(1);
        $io->write("Failed. ");

        $failReason = $result->getFailReason();

        if ($failReason === RenameFailReason::NAME_BAD) {
            $io->writeLine("Name is bad.");

            return;
        }

        if ($failReason === RenameFailReason::NAME_NOT_ALLOWED) {
            $io->writeLine("Name is not allowed.");

            return;
        }

        if ($failReason === RenameFailReason::NAME_TOO_LONG) {
            $io->writeLine("Name is too long.");

            return;
        }

        if ($failReason === RenameFailReason::NAME_TOO_SHORT) {
            $io->writeLine("Name is too short.");

            return;
        }

        if ($failReason === RenameFailReason::NAME_USED) {
            $io->writeLine("Name is already used.");

            return;
        }

        if ($failReason === RenameFailReason::DOES_NOT_EXIST) {
            $io->writeLine("Entity type `$entityType` does not exist.");

            return;
        }

        if ($failReason === RenameFailReason::NOT_CUSTOM) {
            $io->writeLine("Entity type `$entityType` is not custom, hence can't be renamed.");

            return;
        }

        if ($failReason === RenameFailReason::ENV_NOT_SUPPORTED) {
            $io->writeLine("Environment is not supported.");

            return;
        }

        if ($failReason === RenameFailReason::TABLE_EXISTS) {
            $io->writeLine("Table already exists.");

            return;
        }

        if ($failReason === RenameFailReason::ERROR) {
            $io->writeLine("Error occurred.");
        }
    }
}
