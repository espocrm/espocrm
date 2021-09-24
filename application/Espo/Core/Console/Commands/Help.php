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

namespace Espo\Core\Console\Commands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;

class Help implements Command
{
    private $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function run(Params $params, IO $io): void
    {
        $commandList = array_filter(
            array_keys($this->metadata->get(['app', 'consoleCommands']) ?? []),
            function (string $item): bool {
                return (bool) $this->metadata->get(['app', 'consoleCommands', $item]);
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
