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

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Job\Processing\PrepareDaemon;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class JobPrepare implements Command
{
    public function __construct(
        private PrepareDaemon $prepareDaemon,
    ) {}

    public function run(Params $params, IO $io): void
    {
        $interval = $this->getInterval($params);
        $limit = $this->getLimit($params);
        $skipQueues = $params->hasFlag('sq');

        $daemonParams = new PrepareDaemon\Params(
            interval: $interval,
            limit: $limit,
            skipQueues: $skipQueues,
        );

        $this->prepareDaemon->run($daemonParams);
    }

    private function getInterval(Params $params): ?float
    {
        $intervalString = $params->getOption('interval');

        if ($intervalString === null) {
            return null;
        }

        if (
            filter_var($intervalString, FILTER_VALIDATE_INT) !== false ||
            filter_var($intervalString, FILTER_VALIDATE_FLOAT) !== false
        ) {
            return (float) $intervalString;
        } else {
            throw new RuntimeException("Bad interval.");
        }
    }

    private function getLimit(Params $params): ?int
    {
        $limitString = $params->getOption('limit');

        if ($limitString === null) {
            return null;
        }

        if (filter_var($limitString, FILTER_VALIDATE_INT) !== false) {
            return (int) $limitString;
        } else {
            throw new RuntimeException("Bad limit.");
        }
    }
}
