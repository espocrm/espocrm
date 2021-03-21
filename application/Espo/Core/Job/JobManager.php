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
    Exceptions\Error,
    ServiceFactory,
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\System,
    Utils\Cron\ScheduledJob as CronScheduledJob,
    Utils\Cron\Job as CronJob,
    ORM\EntityManager,
    Utils\Cron\JobTask,
    Utils\Log,
    Job\JobTargeted,
};

use Espo\{
    Entities\Job as JobEntity,
    Entities\ScheduledJob as ScheduledJobEntity,
};

use Spatie\Async\Pool as AsyncPool;

use Cron\CronExpression;

use Exception;
use Throwable;

/**
 * Handles running jobs.
 */
class JobManager
{
    private $cronJobUtil;

    private $cronScheduledJobUtil;

    private $useProcessPool = false;

    private $asSoonAsPossibleSchedulingList = [
        '*',
        '* *',
        '* * *',
        '* * * *',
        '* * * * *',
        '* * * * * *',
    ];

    const PENDING = 'Pending';

    const READY = 'Ready';

    const RUNNING = 'Running';

    const SUCCESS = 'Success';

    const FAILED = 'Failed';

    protected $lastRunTimeFile = 'data/cache/application/cronLastRunTime.php';

    private $config;

    private $fileManager;

    private $entityManager;

    private $serviceFactory;

    private $jobFactory;

    private $log;

    public function __construct(
        Config $config,
        FileManager $fileManager,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        JobFactory $jobFactory,
        Log $log
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->jobFactory = $jobFactory;
        $this->log = $log;

        $this->cronJobUtil = new CronJob($this->config, $this->entityManager);

        $this->cronScheduledJobUtil = new CronScheduledJob($this->entityManager);

        if ($this->config->get('jobRunInParallel')) {
            if (AsyncPool::isSupported()) {
                $this->useProcessPool = true;
            }
            else {
                $this->log->warning("JobManager: useProcessPool requires pcntl and posix extensions.");
            }
        }
    }

    protected function getLastRunTime()
    {
        $lastRunData = $this->fileManager->getPhpContents($this->lastRunTimeFile);

        if (is_array($lastRunData) && !empty($lastRunData['time'])) {
            $lastRunTime = $lastRunData['time'];
        }
        else {
            $lastRunTime = time() - intval($this->config->get('cronMinInterval', 0)) - 1;
        }

        return $lastRunTime;
    }

    protected function setLastRunTime($time)
    {
        $data = [
            'time' => $time,
        ];

        return $this->fileManager->putPhpContents($this->lastRunTimeFile, $data, false, true);
    }

    protected function checkLastRunTime()
    {
        $currentTime = time();
        $lastRunTime = $this->getLastRunTime();

        $cronMinInterval = $this->config->get('cronMinInterval', 0);

        if ($currentTime > ($lastRunTime + $cronMinInterval)) {
            return true;
        }

        return false;
    }

    protected function useProcessPool() : bool
    {
        return $this->useProcessPool;
    }

    public function setUseProcessPool(bool $useProcessPool) : void
    {
        $this->useProcessPool = $useProcessPool;
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

        $this->setLastRunTime(time());

        $this->cronJobUtil->markJobsFailed();
        $this->cronJobUtil->updateFailedJobAttempts();

        $this->createJobsFromScheduledJobs();

        $this->cronJobUtil->removePendingJobDuplicates();

        $this->processPendingJobs();
    }

    /**
     * Process pending jobs from a specific queue. Jobs within a queue are processed one by one.
     */
    public function processQueue(string $queue, int $limit) : void
    {
        $this->processPendingJobs($queue, $limit, true, true);
    }

    protected function processPendingJobs(
        ?string $queue = null,
        ?int $limit = null,
        bool $poolDisabled = false,
        bool $noLock = false
    ) : void {

        if (is_null($limit)) {
            $limit = intval($this->config->get('jobMaxPortion', 0));
        }

        $pendingJobList = $this->cronJobUtil->getPendingJobList($queue, $limit);

        $useProcessPool = $this->useProcessPool();

        if ($poolDisabled) {
            $useProcessPool = false;
        }

        $pool = null;

        if ($useProcessPool) {
            $pool = AsyncPool
                ::create()
                ->autoload(getcwd() . '/vendor/autoload.php')
                ->concurrency($this->config->get('jobPoolConcurrencyNumber'))
                ->timeout($this->config->get('jobPeriodForActiveProcess'));
        }

        foreach ($pendingJobList as $job) {
            $this->processPendingJob($job, $pool, $noLock);
        }

        if ($useProcessPool) {
            $pool->wait();
        }
    }

    protected function processPendingJob(JobEntity $job, $pool = null, bool $noLock = false) : void
    {
        $useProcessPool = (bool) $pool;

        $lockTable = (bool) $job->getScheduledJobId() && !$noLock;

        $skip = false;

        if (!$noLock) {
            if ($lockTable) {
                // MySQL doesn't allow to lock non-existent rows. We resort to locking an entire table.
                $this->entityManager->getLocker()->lockExclusive('Job');
            }
            else {
                $this->entityManager->getTransactionManager()->start();
            }
        }

        if ($noLock || $this->cronJobUtil->isJobPending($job->id)) {
            if (
                $job->getScheduledJobId() &&
                $this->cronJobUtil->isScheduledJobRunning(
                    $job->getScheduledJobId(),
                    $job->getTargetId(),
                    $job->getTargetType()
                )
            ) {
                $skip = true;
            }
        } else {
            $skip = true;
        }

        if ($skip && !$noLock) {
            if ($lockTable) {
                $this->entityManager->getLocker()->rollback();
            }
            else {
                $this->entityManager->getTransactionManager()->rollback();
            }
        }

        if ($skip) {
            return;
        }

        $job->set('startedAt', date('Y-m-d H:i:s'));

        if ($useProcessPool) {
            $job->set('status', self::READY);
        }
        else {
            $job->set('status', self::RUNNING);
            $job->set('pid', System::getPid());
        }

        $this->entityManager->saveEntity($job);

        if (!$noLock) {
            if ($lockTable) {
                $this->entityManager->getLocker()->commit();
            }
            else {
                $this->entityManager->getTransactionManager()->commit();
            }
        }

        if ($useProcessPool) {
            $task = new JobTask($job->id);

            $pool->add($task);

            return;
        }

        $this->runJobInternal($job);
    }

    /**
     * Run a specific job by ID. A job status should be set to 'Ready'.
     */
    public function runJobById(string $id) : void
    {
        if (empty($id)) {
            throw new Error();
        }

        $job = $this->entityManager->getEntity('Job', $id);

        if (!$job) {
            throw new Error("Job {$id} not found.");
        }

        if ($job->getStatus() !== self::READY) {
            throw new Error("Can't run job {$id} with no status Ready.");
        }

        if (!$job->getStartedAt()) {
            $job->set('startedAt', date('Y-m-d H:i:s'));
        }

        $job->set('status', self::RUNNING);
        $job->set('pid', System::getPid());

        $this->entityManager->saveEntity($job);

        $this->runJobInternal($job);
    }

    /**
     * Run a specific job.
     *
     * @throws Throwable
     */
    public function runJob(JobEntity $job) : void
    {
        $this->runJobInternal($job, true);
    }

    protected function runJobInternal(JobEntity $job, bool $throwException = false) : void
    {
        $isSuccess = true;

        $skipLog = false;

        $exception = null;

        try {
            if ($job->getScheduledJobId()) {
                $this->runScheduledJob($job);
            }
            else if ($job->getJob()) {
                $this->runJobByName($job);
            }
            else if ($job->getServiceName()) {
                $this->runService($job);
            }
            else {
                $id = $job->getId();

                throw new Error("Not runnable job '{$id}'.");
            }
        }
        catch (Throwable $e) {
            $isSuccess = false;

            if ($e->getCode() === -1) {
                $job->set('attempts', 0);

                $skipLog = true;
            }
            else {
                $this->log->error(
                    "JobManager: Failed job running, job '{$job->id}'. " .
                    $e->getMessage() . "; at " . $e->getFile() . ":" . $e->getLine() . "."
                );
            }

            if ($throwException) {
                $exception = $e;
            }
        }

        $status = $isSuccess ? self::SUCCESS : self::FAILED;

        $job->set('status', $status);

        if ($isSuccess) {
            $job->set('executedAt', date('Y-m-d H:i:s'));
        }

        $this->entityManager->saveEntity($job);

        if ($throwException && $exception) {
            throw new $exception;
        }

        if ($job->getScheduledJobId() && !$skipLog) {
            $this->cronScheduledJobUtil->addLogRecord(
                $job->getScheduledJobId(),
                $status,
                null,
                $job->getTargetId(),
                $job->getTargetType()
            );
        }
    }

    protected function runScheduledJob(JobEntity $job) : void
    {
        $jobName = $job->getScheduledJobJob();

        if (!$jobName) {
            throw new Error(
                "Can't run job '" . $job->getId() . "'. Not a scheduled job."
            );
        }

        $obj = $this->jobFactory->create($jobName);

        if ($obj instanceof JobTargeted) {
            $obj->run($job->getTargetType(), $job->getTargetId(), $job->getData());

            return;
        }

        if (!method_exists($obj, 'run')) {
            throw new Error("No 'run' method in job '{$jobName}'.");
        }

        $obj->run();
    }

    protected function runService(JobEntity $job) : void
    {
        $serviceName = $job->getServiceName();

        if (!$serviceName) {
            throw new Error("Job with empty serviceName.");
        }

        if (!$this->serviceFactory->checkExists($serviceName)) {
            throw new Error();
        }

        $service = $this->serviceFactory->create($serviceName);

        $methodName = $job->getMethodName();

        if (!$methodName) {
            throw new Error('Job with empty methodName.');
        }

        if (!method_exists($service, $methodName)) {
            throw new Error();
        }

        $service->$methodName($job->getData(), $job->getTargetId(), $job->getTargetType());
    }

    protected function runJobByName(JobEntity $job) : void
    {
        $jobName = $job->getJob();

        $obj = $this->jobFactory->create($jobName);

        if ($obj instanceof JobTargeted) {
            $obj->run($job->getTargetType(), $job->getTargetId(), $job->getData());

            return;
        }

        if (!method_exists($obj, 'run')) {
            throw new Error("No 'run' method in job '{$jobName}'.");
        }

        $obj->run();
    }

    protected function createJobsFromScheduledJobs() : void
    {
        $activeScheduledJobList = $this->cronScheduledJobUtil->getActiveScheduledJobList();

        $runningScheduledJobIdList = $this->cronJobUtil->getRunningScheduledJobIdList();

        foreach ($activeScheduledJobList as $scheduledJob) {
            try {
                $this->createJobsFromScheduledJob($scheduledJob, $runningScheduledJobIdList);
            }
            catch (Throwable $e) {
                $id = $scheduledJob->getId();

                $this->log->error("Scheduled Job '{$id}': " . $e->getMessage());
            }
        }
    }

    private function createJobsFromScheduledJob(
        ScheduledJobEntity $scheduledJob, array $runningScheduledJobIdList
    ) : void {

        $scheduling = $scheduledJob->getScheduling();

        $id = $scheduledJob->getId();

        $asSoonAsPossible = in_array($scheduling, $this->asSoonAsPossibleSchedulingList);

        if ($asSoonAsPossible) {
            $executeTime = date('Y-m-d H:i:s');
        }
        else {
            try {
                $cronExpression = CronExpression::factory($scheduling);
            }
            catch (Exception $e) {
                $this->log->error(
                    "Scheduled Job '{$id}': Scheduling expression error: " .
                    $e->getMessage() . '.'
                );

                return;
            }

            try {
                $executeTime = $cronExpression->getNextRunDate()->format('Y-m-d H:i:s');
            }
            catch (Exception $e) {
                $this->log->error(
                    "Scheduled Job '{$id}': Unsupported scheduling expression '{$scheduling}'."
                );

                return;
            }

            $jobAlreadyExists = $this->cronJobUtil->hasScheduledJobOnMinute($id, $executeTime);

            if ($jobAlreadyExists) {
                return;
            }
        }

        $jobName = $scheduledJob->getJob();

        if ($this->jobFactory->isPreparable($jobName)) {
            $jobObj = $this->jobFactory->create($jobName);

            $jobObj->prepare($scheduledJob, $executeTime);

            return;
        }

        if (in_array($id, $runningScheduledJobIdList)) {
            return;
        }

        $pendingCount = $this->cronJobUtil->getPendingCountByScheduledJobId($id);

        if ($asSoonAsPossible) {
            if ($pendingCount > 0) {
                return;
            }
        }
        else {
            if ($pendingCount > 1) {
                return;
            }
        }

        $this->entityManager->createEntity('Job', [
            'name' => $scheduledJob->getName(),
            'status' => self::PENDING,
            'scheduledJobId' => $id,
            'executeTime' => $executeTime,
        ]);
    }
}
