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
    Utils\Log,
    ORM\EntityManager,
    ServiceFactory,
    Utils\System,
};

use Espo\Entities\Job as JobEntity;

use Throwable;

class JobRunner
{
    private $jobFactory;

    private $scheduleUtil;

    private $entityManager;

    private $serviceFactory;

    private $log;

    public function __construct(
        JobFactory $jobFactory,
        ScheduleUtil $scheduleUtil,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        Log $log
    ) {
        $this->jobFactory = $jobFactory;
        $this->scheduleUtil = $scheduleUtil;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->log = $log;
    }

    /**
     * Run a job entity. Does not throw exceptions.
     */
    public function run(JobEntity $job) : void
    {
        $this->runInternal($job, false);
    }

    /**
     * Run a job entity. Throws exceptions.
     *
     * @throws Throwable
     */
    public function runThrowingException(JobEntity $job) : void
    {
        $this->runInternal($job, true);
    }

    /**
     * Run a job by ID. A job must have status 'Ready'.
     * Used when running jobs in parallel processes.
     */
    public function runById(string $id) : void
    {
        if ($id === '') {
            throw new Error();
        }

        $job = $this->entityManager->getEntity('Job', $id);

        if (!$job) {
            throw new Error("Job {$id} not found.");
        }

        if ($job->getStatus() !== JobManager::READY) {
            throw new Error("Can't run job {$id} with no status Ready.");
        }

        if (!$job->getStartedAt()) {
            $job->set('startedAt', date('Y-m-d H:i:s'));
        }

        $job->set('status', JobManager::RUNNING);
        $job->set('pid', System::getPid());

        $this->entityManager->saveEntity($job);

        $this->run($job);
    }

    private function runInternal(JobEntity $job, bool $throwException = false) : void
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

            $this->log->error(
                "JobManager: Failed job running, job '{$job->id}'. " .
                $e->getMessage() . "; at " . $e->getFile() . ":" . $e->getLine() . "."
            );

            if ($throwException) {
                $exception = $e;
            }
        }

        $status = $isSuccess ? JobManager::SUCCESS : JobManager::FAILED;

        $job->set('status', $status);

        if ($isSuccess) {
            $job->set('executedAt', date('Y-m-d H:i:s'));
        }

        $this->entityManager->saveEntity($job);

        if ($throwException && $exception) {
            throw new $exception;
        }

        if ($job->getScheduledJobId() && !$skipLog) {
            $this->scheduleUtil->addLogRecord(
                $job->getScheduledJobId(),
                $status,
                null,
                $job->getTargetId(),
                $job->getTargetType()
            );
        }
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
            throw new Error("No method '{$methodName}' in service '{$serviceName}'.");
        }

        $service->$methodName($job->getData(), $job->getTargetId(), $job->getTargetType());
    }
}
