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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\Application\Runner;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs daemon. The daemon runs the cron more often than once a minute.
 */
class Daemon implements Runner
{
    use Cli;

    public function __construct(private Config $config, private Log $log)
    {}

    public function run(): void
    {
        $maxProcessNumber = $this->config->get('daemonMaxProcessNumber');
        $interval = $this->config->get('daemonInterval');
        $timeout = $this->config->get('daemonProcessTimeout');

        $phpExecutablePath = $this->config->get('phpExecutablePath');

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new PhpExecutableFinder)->find();
        }

        if (!$maxProcessNumber || !$interval) {
            $this->log->error("Daemon config params are not set.");

            return;
        }

        $processList = [];

        while (true) { /** @phpstan-ignore-line */
            $toSkip = false;
            $runningCount = 0;

            foreach ($processList as $i => $process) {
                if ($process->isRunning()) {
                    $runningCount++;
                } else {
                    unset($processList[$i]);
                }
            }

            $processList = array_values($processList);

            if ($runningCount >= $maxProcessNumber) {
                $toSkip = true;
            }

            if (!$toSkip) {
                $process = new Process([$phpExecutablePath, 'cron.php']);

                $process->setTimeout($timeout);

                $process->start();

                $processList[] = $process;
            }

            sleep($interval);
        }
    }
}
