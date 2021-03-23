<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Job;

use Espo\Core\{
    Utils\Config,
    Utils\File\Manager as FileManager,
    ORM\EntityManager,
    Utils\Log,
};

use Espo\{
    Entities\Job as JobEntity,
};

use Throwable;

/**
 * Handles processing jobs.
 */
class JobManager
{
    const PENDING = 'Pending';

    const READY = 'Ready';

    const RUNNING = 'Running';

    const SUCCESS = 'Success';

    const FAILED = 'Failed';

    private $useProcessPool = false;

    protected $lastRunTimeFile = 'data/cache/application/cronLastRunTime.php';

    private $config;

    private $fileManager;

    private $entityManager;

    private $jobRunner;

    private $log;

    private $scheduleProcessor;

    private $queueUtil;

    private $asyncPoolFactory;

    private $queueProcessorFactory;

    public function __construct(
        Config $config,
        FileManager $fileManager,
        EntityManager $entityManager,
        JobRunner $jobRunner,
        Log $log,
        ScheduleProcessor $scheduleProcessor,
        QueueUtil $queueUtil,
        AsyncPoolFactory $asyncPoolFactory,
        QueueProcessorFactory $queueProcessorFactory
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->jobRunner = $jobRunner;
        $this->log = $log;
        $this->scheduleProcessor = $scheduleProcessor;
        $this->queueUtil = $queueUtil;
        $this->asyncPoolFactory = $asyncPoolFactory;
        $this->queueProcessorFactory = $queueProcessorFactory;

        if ($this->config->get('jobRunInParallel')) {
            if ($this->asyncPoolFactory->isSupported()) {
                $this->useProcessPool = true;
            }
            else {
                $this->log->warning("JobManager: useProcessPool requires pcntl and posix extensions.");
            }
        }
    }

    protected function getLastRunTime() : int
    {
        $lastRunData = $this->fileManager->getPhpContents($this->lastRunTimeFile);

        if (is_array($lastRunData) && !empty($lastRunData['time'])) {
            $lastRunTime = $lastRunData['time'];
        }
        else {
            $lastRunTime = time() - intval($this->config->get('cronMinInterval', 0)) - 1;
        }

        return (int) $lastRunTime;
    }

    protected function updateLastRunTime() : void
    {
        $data = [
            'time' => time(),
        ];

        $this->fileManager->putPhpContents($this->lastRunTimeFile, $data, false, true);
    }

    protected function checkLastRunTime() : bool
    {
        $currentTime = time();
        $lastRunTime = $this->getLastRunTime();

        $cronMinInterval = $this->config->get('cronMinInterval', 0);

        if ($currentTime > ($lastRunTime + $cronMinInterval)) {
            return true;
        }

        return false;
    }

    /**
     * Process jobs. Jobs will be created according scheduling. Then pending jobs will be processed.
     * This method supposed to be called on every Cron run or loop iteration of the Daemon.
     */
    public function process() : void
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
    public function processQueue(string $queue, int $limit) : void
    {
        $params = QueueProcessorParams
            ::fromNothing()
            ->withQueue($queue)
            ->withLimit($limit)
            ->withUseProcessPool(false)
            ->withNoLock(true);

        $processor = $this->queueProcessorFactory->create($params);

        $processor->process();
    }

    private function processMainQueue() : void
    {
        $limit = (int) $this->config->get('jobMaxPortion', 0);

        $params = QueueProcessorParams
            ::fromNothing()
            ->withUseProcessPool($this->useProcessPool)
            ->withLimit($limit);

        $processor = $this->queueProcessorFactory->create($params);

        $processor->process();
    }

    /**
     * Run a specific job by ID. A job status should be set to 'Ready'.
     */
    public function runJobById(string $id) : void
    {
        $this->jobRunner->runById($id);
    }

    /**
     * Run a specific job.
     *
     * @throws Throwable
     */
    public function runJob(JobEntity $job) : void
    {
        $this->jobRunner->runThrowingException($job);
    }
}
