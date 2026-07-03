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

namespace Espo\Core\Job;

use Espo\Core\Job\Exceptions\TooFrequentRun;
use Espo\Core\Job\PrepareProcessor\Params;

/**
 * @since 10.1.0
 */
class PrepareProcessor
{
    public function __construct(
        private QueueUtil $queueUtil,
        private ScheduleProcessor $scheduleProcessor,
        private CronUtil $cronUtil,
    ) {}

    /**
     * Jobs are be created according scheduling of scheduled jobs.
     * This method is meant to be called on every Cron run or loop iteration of the Daemon.
     *
     * @throws TooFrequentRun
     */
    public function process(Params $params = new Params()): void
    {
        if (!$this->cronUtil->checkLastRunTime()) {
            throw new TooFrequentRun('JobManager: Skip job processing. Too frequent execution.');
        }

        $this->cronUtil->updateLastRunTime();

        $this->processPrepare($params);
    }

    private function processPrepare(Params $params): void
    {
        $scheduleParams = new ScheduleProcessor\Params(
            skipQueues: $params->skipQueues,
        );

        $this->queueUtil->markJobsFailed();
        $this->queueUtil->updateFailedJobAttempts();
        $this->scheduleProcessor->process($scheduleParams);
        $this->queueUtil->removePendingJobDuplicates();
    }
}
