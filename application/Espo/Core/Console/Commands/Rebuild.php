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
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Database\Schema\RebuildMode;

/**
 * @noinspection PhpUnused
 */
class Rebuild implements Command
{
    public function __construct(private DataManager $dataManager)
    {}

    /**
     * @throws Error
     */
    public function run(Params $params, IO $io): void
    {
        $this->dataManager->rebuild();

        if ($params->hasFlag('hard')) {
            if (!$params->hasFlag('y')) {
                $message =
                    "Are you sure you want to run a hard DB rebuild? It will drop unused columns, " .
                    "decrease exceeding column lengths. It may take some time to process.\nType [Y] to proceed.";

                $io->writeLine($message);

                $input = $io->readLine();

                if (strtolower($input) !== 'y') {
                    return;
                }
            }

            $this->dataManager->rebuildDatabase(null, RebuildMode::HARD);
        }

        $io->writeLine("Rebuild has been done.");
    }
}
