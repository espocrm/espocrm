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

namespace Espo\Classes\ConsoleCommands;

use Espo\Tools\Import\Service;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;

use Throwable;

class Import implements Command
{
    public function __construct(private Service $service, private FileManager $fileManager)
    {}

    public function run(Params $params, IO $io) : void
    {
        $id = $params->getOption('id');
        $filePath = $params->getOption('file');
        $paramsId = $params->getOption('paramsId');

        $forceResume = $params->hasFlag('resume');
        $revert = $params->hasFlag('revert');

        if (!$id && $filePath) {
            if (!$paramsId) {
                $io->writeLine("You need to specify --params-id option.");

                return;
            }

            if (!$this->fileManager->isFile($filePath)) {
                $io->writeLine("File not found.");

                return;
            }

            $contents = $this->fileManager->getContents($filePath);

            try {
                $result = $this->service->importContentsWithParamsId($contents, $paramsId);

                $resultId = $result->getId();
                $countCreated = $result->getCountCreated();
                $countUpdated = $result->getCountUpdated();
                $countError = $result->getCountError();
                $countDuplicate = $result->getCountDuplicate();
            } catch (Throwable $e) {
                $io->writeLine("Error occurred: " . $e->getMessage());

                return;
            }

            $io->writeLine("Finished.");
            $io->writeLine("  Import ID: {$resultId}");
            $io->writeLine("  Created: {$countCreated}");
            $io->writeLine("  Updated: {$countUpdated}");
            $io->writeLine("  Duplicates: {$countDuplicate}");
            $io->writeLine("  Errors: {$countError}");

            return;
        }

        if ($id && $revert) {
            $io->writeLine("Reverting import...");

            try {
                $this->service->revert($id);
            } catch (Throwable $e) {
                $io->writeLine("Error occurred: " . $e->getMessage());

                return;
            }

            $io->writeLine("Finished.");

            return;
        }

        if ($id) {
            $io->writeLine("Running import, this may take a while...");

            try {
                $result = $this->service->importById($id, true, $forceResume);
            } catch (Throwable $e) {
                $io->writeLine("Error occurred: " . $e->getMessage());

                return;
            }

            $countCreated = $result->getCountCreated();
            $countUpdated = $result->getCountUpdated();
            $countError = $result->getCountError();
            $countDuplicate = $result->getCountDuplicate();

            $io->writeLine("Finished.");
            $io->writeLine("  Created: {$countCreated}");
            $io->writeLine("  Updated: {$countUpdated}");
            $io->writeLine("  Duplicates: {$countDuplicate}");
            $io->writeLine("  Errors: {$countError}");

            return;
        }

        $io->writeLine("Not enough params passed.");
    }
}
