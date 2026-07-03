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

namespace Espo\Core\Job\Processing;

use Espo\Core\Job\Exceptions\TooFrequentRun;
use Espo\Core\Job\PrepareProcessor;
use Espo\Core\Job\Processing\Util\ExitPolicy;
use Espo\Core\Job\Processing\Util\ExitSetup;

/**
 * @since 10.1.0
 */
class PrepareDaemon
{
    private const float INTERVAL = 10.0;

    private bool $stopped = false;

    public function __construct(
        private ExitSetup $exitSetup,
        private ExitPolicy $exitPolicy,
        private PrepareProcessor $prepareProcessor,
    ) {}

    public function run(PrepareDaemon\Params $params): void
    {
        $interval = $this->getInterval($params);

        $prepareParams = new PrepareProcessor\Params(
            skipQueues: $params->skipQueues,
        );

        $this->exitSetup->setup(function () {
            $this->stopped = true;
        });

        $count = 0;

        while (true) {
            try {
                $this->prepareProcessor->process($prepareParams);
            } catch (TooFrequentRun) {
                continue;
            }

            $count ++;

            if ($this->toForceExit() || $this->toExit($params, $count)) {
                break;
            }

            usleep($interval);

            if ($this->toForceExit()) {
                break;
            }
        }

        $this->stopped = false;
    }

    private function getInterval(PrepareDaemon\Params $params): int
    {
        $interval = $params->interval ?? self::INTERVAL;

        return (int) ($interval * 1000000);
    }

    private function isStopped(): bool
    {
        return $this->stopped;
    }

    private function toExit(PrepareDaemon\Params $params, int $count): bool
    {
        return $params->limit && $count >= $params->limit;
    }

    /**
     * @phpstan-impure
     */
    private function toForceExit(): bool
    {
        return $this->isStopped() || $this->exitPolicy->toExit();
    }
}
