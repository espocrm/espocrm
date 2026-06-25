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

namespace Espo\Classes\ConsoleCommands;

use Espo\Core\Authentication\ConfigDataProvider as AuthenticationConfig;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Upgrades\Migration\VersionDataProvider;
use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\Database\Helper;
use Exception;

/**
 * @noinspection PhpUnused
 */
class AppCheck implements Command
{
    public function __construct(
        private Helper $databaseHelper,
        private SystemConfig $systemConfig,
        private VersionDataProvider $versionDataProvider,
        private AuthenticationConfig $authenticationConfig,
    ) {}

    public function run(Params $params, IO $io): void
    {
        $this->versionMatch($io);
        $this->checkDb($io);
        $this->maintenanceIsOff($io);
        $this->cronEnabled($io);
    }

    private function versionMatch(IO $io): void
    {
        $io->write('Migration not needed: ');

        if ($this->systemConfig->getVersion() === $this->versionDataProvider->getTargetVersion()) {
            $this->writeOK($io);
        } else {
            $this->writeFail($io);
            $io->setExitStatus(1);
        }

        $io->writeLine('');
    }

    private function checkDb(IO $io): void
    {
        $io->write('Database: ');

        try {
            $this->databaseHelper->createPDO();

            $this->writeOK($io);
        } catch (Exception) {
            $this->writeFail($io);
            $io->setExitStatus(1);
        }

        $io->writeLine('');
    }

    private function maintenanceIsOff(IO $io): void
    {
        $io->write('Not in maintenance mode: ');

        if (!$this->authenticationConfig->isMaintenanceMode()) {
            $this->writeOK($io);
        } else {
            $this->writeFail($io);
            $io->setExitStatus(1);
        }

        $io->writeLine('');
    }

    private function cronEnabled(IO $io): void
    {
        $io->write('Cron is enabled: ');

        if ($this->systemConfig->isCronEnabled()) {
            $this->writeOK($io);
        } else {
            $this->writeFail($io);
            $io->setExitStatus(1);
        }

        $io->writeLine('');
    }

    private function writeOK(IO $io): void
    {
        $io->write("\033[32mOK\033[0m");
    }

    private function writeFail(IO $io): void
    {
        $io->write("\033[31mFAIL\033[0m");
    }
}
