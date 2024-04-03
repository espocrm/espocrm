<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Upgrades\Migration;

use Espo\Core\Console\IO;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Runner
{
    private string $defaultConfigPath = 'application/Espo/Resources/defaults/config.php';

    public function __construct(
        private Config $config,
        private Manager $fileManager,
        private StepsExtractor $stepsExtractor,
        private StepsProvider $stepsProvider
    ) {}

    public function run(IO $io): void
    {
        $version = $this->config->get('version');
        $targetVersion = $this->getTargetVersion();

        $fullList = $this->stepsProvider->get();
        $steps = $this->stepsExtractor->extract($version, $targetVersion, $fullList);

        if ($steps === []) {
            $io->writeLine(" No migrations to run.");

            return;
        }

        $io->write(" Running migrations...");

        foreach ($steps as $step) {
            $this->runVersionStep($io, $step);
        }
    }

    private function runVersionStep(IO $io, string $step): void
    {
        $phpExecutablePath = $this->getPhpExecutablePath();

        $command = "command.php migration-version-step --step=$step";

        $io->write("    $step...");

        $process = new Process([$phpExecutablePath, $command]);
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            $io->writeLine(" DONE");

            return;
        }

        $io->writeLine(" FAIL");

        throw new RuntimeException();
    }

    private function getPhpExecutablePath(): string
    {
        $phpExecutablePath = $this->config->get('phpExecutablePath');

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new PhpExecutableFinder)->find();
        }

        return $phpExecutablePath;
    }

    private function getTargetVersion(): string
    {
        $data = $this->fileManager->getPhpContents($this->defaultConfigPath);

        if (!is_array($data)) {
            throw new RuntimeException("No default config.");
        }

        $version = $data['version'] ?? null;

        if (!$version || !is_string($version)) {
            throw new RuntimeException("No or bad 'version' parameter in default config.");
        }

        return $version;
    }
}
