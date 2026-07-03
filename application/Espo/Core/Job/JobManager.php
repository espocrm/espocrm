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

use Espo\Core\Job\QueueProcessor\Params;
use Espo\Core\Job\QueueProcessor\QueueProcessors\SequentialQueueProcessor;
use Espo\Entities\Job as JobEntity;

use Throwable;

/**
 * Handles processing jobs.
 */
class JobManager
{
    public function __construct(
        private JobRunner $jobRunner,
        private QueueProcessor $queueProcessor,
        private ConfigDataProvider $configDataProvider,
        private SequentialQueueProcessor $sequentialQueueProcessor,
    ) {}

    /**
     * Process pending jobs from a specific queue. Jobs within a queue are processed one by one.
     */
    public function processQueue(string $queue, int $limit): void
    {
        $params = Params::create()
            ->withQueue($queue)
            ->withLimit($limit);

        $this->sequentialQueueProcessor->process($params);
    }

    /**
     * Process pending jobs from a specific group. Jobs within a group are processed one by one.
     */
    public function processGroup(string $group, int $limit): void
    {
        $params = Params::create()
            ->withGroup($group)
            ->withLimit($limit);

        $this->sequentialQueueProcessor->process($params);
    }

    /**
     * Process the main job queue.
     */
    public function processMainQueue(): void
    {
        $limit = $this->configDataProvider->getMaxPortion();

        $params = Params::create()
            ->withLimit($limit);

        $params = $params->withSubQueueParamsList([
            $params->withWeight(0.5),
            $params->withQueue(QueueName::M0)->withWeight(0.5),
        ]);

        $this->queueProcessor->process($params);
    }

    /**
     * Run a specific job by ID. A job status should be set to 'Ready'.
     */
    public function runJobById(string $id): void
    {
        $this->jobRunner->runById($id);
    }

    /**
     * Run a specific job.
     *
     * @throws Throwable
     */
    public function runJob(JobEntity $job): void
    {
        $this->jobRunner->runThrowingException($job);
    }
}
