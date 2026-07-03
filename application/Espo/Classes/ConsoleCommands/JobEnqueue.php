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
use Espo\Core\Console\Exceptions\InvalidArgument;
use Espo\Core\Console\IO;
use Espo\Core\Job\Processing\EnqueueDaemon;
use Espo\Core\Job\QueueName;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class JobEnqueue implements Command
{
    public function __construct(
        private EnqueueDaemon $enqueueDaemon,
    ) {}

    public function run(Params $params, IO $io): void
    {
        $daemonParams = $this->prepareParams($params);

        $this->enqueueDaemon->run($daemonParams);
    }

    private function prepareParams(Params $params): EnqueueDaemon\Params
    {
        $interval = $this->getInterval($params);
        $limit = $this->getLimit($params);
        $portion = $this->getPortion($params);
        $queue = $params->getOption('queue');

        if ($queue === QueueName::M0) {
            throw new InvalidArgument("Queue 'm0' is not allowed. It is processed in the main queue.");
        }

        return new EnqueueDaemon\Params(
            interval: $interval,
            limit: $limit,
            portion: $portion,
            queue: $queue,
        );
    }

    private function getInterval(Params $params): ?float
    {
        $valueString = $params->getOption('interval');

        if ($valueString === null) {
            return null;
        }

        if (
            filter_var($valueString, FILTER_VALIDATE_INT) !== false ||
            filter_var($valueString, FILTER_VALIDATE_FLOAT) !== false
        ) {
            return (float) $valueString;
        }

        throw new RuntimeException("Bad interval.");
    }

    private function getLimit(Params $params): ?int
    {
        $valueString = $params->getOption('limit');

        if ($valueString === null) {
            return null;
        }

        if (filter_var($valueString, FILTER_VALIDATE_INT) !== false) {
            return (int) $valueString;
        }

        throw new RuntimeException("Bad limit.");
    }

    private function getPortion(Params $params): ?int
    {
        $valueString = $params->getOption('portion');

        if ($valueString === null) {
            return null;
        }

        if (filter_var($valueString, FILTER_VALIDATE_INT) !== false) {
            return (int) $valueString;
        }

        throw new RuntimeException("Bad portion.");
    }
}
