<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;

/**
 * @noinspection PhpUnused
 */
class Help implements Command
{
    public function __construct(private Metadata $metadata)
    {}

    public function run(Params $params, IO $io): void
    {
        /** @var string[] $fullCommandList */
        $fullCommandList = array_keys($this->metadata->get(['app', 'consoleCommands']) ?? []);

        $commandList = array_filter(
            $fullCommandList,
            function ($item): bool {
                return (bool) $this->metadata->get(['app', 'consoleCommands', $item, 'listed']);
            }
        );

        sort($commandList);

        $io->writeLine("");
        $io->writeLine("Available commands:");
        $io->writeLine("");

        foreach ($commandList as $item) {
            $io->writeLine(
                ' ' . Util::camelCaseToHyphen($item)
            );
        }

        $io->writeLine("");

        $io->writeLine("Usage:");
        $io->writeLine("");
        $io->writeLine(" bin/command [command-name] [some-argument] [--some-option=value] [--some-flag]");

        $io->writeLine("");

        $io->writeLine("Documentation: https://docs.espocrm.com/administration/commands/");

        $io->writeLine("");
    }
}
