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

namespace Espo\Core\Upgrades\Migration;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class StepRunner
{
    public function __construct(
        private Config $config,
        private InjectableFactory $injectableFactory,
        private Log $log
    ) {}

    public function runAfterUpgrade(string $step): bool
    {
        $phpExecutablePath = $this->getPhpExecutablePath();

        $command = "command.php";

        $process = new Process([$phpExecutablePath, $command, 'migration-version-step', "--step=$step"]);
        $process->setTimeout(null);
        $process->run();

        $this->processLogging($process);

        return $process->isSuccessful();
    }

    public function runPrepare(string $step): void
    {
        $dir = 'V' . str_replace('.', '_', $step);

        $className = "Espo\\Core\\Upgrades\\Migrations\\$dir\\Prepare";

        if (!class_exists($className)) {
            throw new RuntimeException("No prepare script $step.");
        }

        /** @var Script $script */
        $script = $this->injectableFactory->create($className);
        $script->run();
    }

    private function getPhpExecutablePath(): string
    {
        $phpExecutablePath = $this->config->get('phpExecutablePath');

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new PhpExecutableFinder)->find();
        }

        return $phpExecutablePath;
    }

    private function processLogging(Process $process): void
    {
        if ($process->isSuccessful()) {
            return;
        }

        $output = $process->getOutput();

        if ($output) {
            $this->log->error("Migration step command: " . $output);
        }
    }
}
