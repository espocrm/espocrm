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

namespace Espo\Core\Job;

use Espo\Core\Job\QueueProcessor\Params;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;
use Espo\Entities\Job as JobEntity;

use RuntimeException;
use Throwable;

/**
 * Handles processing jobs.
 */
class JobManager
{
    private bool $useProcessPool = false;
    protected string $lastRunTimeFile = 'data/cache/application/cronLastRunTime.php';

    public function __construct(
        private FileManager $fileManager,
        private JobRunner $jobRunner,
        private Log $log,
        private ScheduleProcessor $scheduleProcessor,
        private QueueUtil $queueUtil,
        private AsyncPoolFactory $asyncPoolFactory,
        private QueueProcessor $queueProcessor,
        private ConfigDataProvider $configDataProvider
    ) {
        if ($this->configDataProvider->runInParallel()) {
            if ($this->asyncPoolFactory->isSupported()) {
                $this->useProcessPool = true;
            } else {
                $this->log->warning("Enabled `jobRunInParallel` parameter requires pcntl and posix extensions.");
            }
        }
    }

    /**
     * Process jobs. Jobs will be created according scheduling. Then pending jobs will be processed.
     * This method supposed to be called on every Cron run or loop iteration of the Daemon.
     */
    public function process(): void
    {
        if (!$this->checkLastRunTime()) {
            $this->log->info('JobManager: Skip job processing. Too frequent execution.');

            return;
        }

        $this->updateLastRunTime();
        $this->queueUtil->markJobsFailed();
        $this->queueUtil->updateFailedJobAttempts();
        $this->scheduleProcessor->process();
        $this->queueUtil->removePendingJobDuplicates();
        $this->processMainQueue();
    }

    /**
     * Process pending jobs from a specific queue. Jobs within a queue are processed one by one.
     */
    public function processQueue(string $queue, int $limit): void
    {
        $params = Params
            ::create()
            ->withQueue($queue)
            ->withLimit($limit)
            ->withUseProcessPool(false)
            ->withNoLock(true);

        $this->queueProcessor->process($params);
    }

    /**
     * Process pending jobs from a specific group. Jobs within a group are processed one by one.
     */
    public function processGroup(string $group, int $limit): void
    {
        $params = Params
            ::create()
            ->withGroup($group)
            ->withLimit($limit)
            ->withUseProcessPool(false)
            ->withNoLock(true);

        $this->queueProcessor->process($params);
    }

    private function processMainQueue(): void
    {
        $limit = $this->configDataProvider->getMaxPortion();

        $params = Params
            ::create()
            ->withUseProcessPool($this->useProcessPool)
            ->withLimit($limit);

        $subQueueParams = [
            $params->withWeight(0.5),
            $params->withQueue(QueueName::M0)->withWeight(0.5),
        ];

        $params = $params->withSubQueueParamsList($subQueueParams);

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

    /**
     * @todo Move to a separate class.
     */
    private function getLastRunTime(): int
    {
        if ($this->fileManager->isFile($this->lastRunTimeFile)) {
            try {
                $data = $this->fileManager->getPhpContents($this->lastRunTimeFile);
            } catch (RuntimeException) {
                $data = null;
            }

            if (is_array($data) && isset($data['time'])) {
                return (int) $data['time'];
            }
        }

        return time() - $this->configDataProvider->getCronMinInterval() - 1;
    }

    /**
     * @todo Move to a separate class.
     */
    private function updateLastRunTime(): void
    {
        $data = ['time' => time()];

        $this->fileManager->putPhpContents($this->lastRunTimeFile, $data, false, true);
    }

    private function checkLastRunTime(): bool
    {
        $currentTime = time();
        $lastRunTime = $this->getLastRunTime();

        $cronMinInterval = $this->configDataProvider->getCronMinInterval();

        if ($currentTime > ($lastRunTime + $cronMinInterval)) {
            return true;
        }

        return false;
    }
}
