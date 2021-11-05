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
    Utils\DateTime as DateTimeUtil,
};

use Espo\Core\Job\Job\Data;
use Espo\Core\Job\Job\Status;

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
    public function run(JobEntity $jobEntity): void
    {
        $this->runInternal($jobEntity, false);
    }

    /**
     * Run a job entity. Throws exceptions.
     *
     * @throws Throwable
     */
    public function runThrowingException(JobEntity $jobEntity): void
    {
        $this->runInternal($jobEntity, true);
    }

    /**
     * Run a job by ID. A job must have status 'Ready'.
     * Used when running jobs in parallel processes.
     */
    public function runById(string $id): void
    {
        if ($id === '') {
            throw new Error();
        }

        /** @var JobEntity|null $jobEntity */
        $jobEntity = $this->entityManager->getEntity(JobEntity::ENTITY_TYPE, $id);

        if (!$jobEntity) {
            throw new Error("Job '{$id}' not found.");
        }

        if ($jobEntity->getStatus() !== Status::READY) {
            throw new Error("Can't run job '{$id}' with no status Ready.");
        }

        $this->setJobRunning($jobEntity);

        $this->run($jobEntity);
    }

    private function runInternal(JobEntity $jobEntity, bool $throwException = false): void
    {
        $isSuccess = true;

        $exception = null;

        if ($jobEntity->getStatus() !== Status::RUNNING) {
            $this->setJobRunning($jobEntity);
        }

        try {
            if ($jobEntity->getScheduledJobId()) {
                $this->runScheduledJob($jobEntity);
            }
            else if ($jobEntity->getJob()) {
                $this->runJobNamed($jobEntity);
            }
            else if ($jobEntity->getClassName()) {
                $this->runJobWithClassName($jobEntity);
            }
            else if ($jobEntity->getServiceName()) {
                $this->runService($jobEntity);
            }
            else {
                $id = $jobEntity->getId();

                throw new Error("Not runnable job '{$id}'.");
            }
        }
        catch (Throwable $e) {
            $isSuccess = false;

            $this->log->error(
                "JobManager: Failed job running, job '{$jobEntity->id}'. " .
                $e->getMessage() . "; at " . $e->getFile() . ":" . $e->getLine() . "."
            );

            if ($throwException) {
                $exception = $e;
            }
        }

        $status = $isSuccess ? Status::SUCCESS : Status::FAILED;

        $jobEntity->set('status', $status);

        if ($isSuccess) {
            $jobEntity->set('executedAt', DateTimeUtil::getSystemNowString());
        }

        $this->entityManager->saveEntity($jobEntity);

        if ($throwException && $exception) {
            throw new $exception($exception->getMessage());
        }

        if ($jobEntity->getScheduledJobId()) {
            $this->scheduleUtil->addLogRecord(
                $jobEntity->getScheduledJobId(),
                $status,
                null,
                $jobEntity->getTargetId(),
                $jobEntity->getTargetType()
            );
        }
    }

    private function runJobNamed(JobEntity $jobEntity): void
    {
        $jobName = $jobEntity->getJob();

        $job = $this->jobFactory->create($jobName);

        $this->runJob($job, $jobEntity);
    }

    private function runScheduledJob(JobEntity $jobEntity): void
    {
        $jobName = $jobEntity->getScheduledJobJob();

        if (!$jobName) {
            throw new Error(
                "Can't run job '" . $jobEntity->getId() . "'. Not a scheduled job."
            );
        }

        $job = $this->jobFactory->create($jobName);

        $this->runJob($job, $jobEntity);
    }

    private function runJobWithClassName(JobEntity $jobEntity): void
    {
        $className = $jobEntity->getClassName();

        $job = $this->jobFactory->createByClassName($className);

        $this->runJob($job, $jobEntity);
    }

    /**
     * @param Job|JobDataLess $job
     */
    private function runJob($job, JobEntity $jobEntity): void
    {
        $data = Data::create($jobEntity->getData())
            ->withTargetId($jobEntity->getTargetId())
            ->withTargetType($jobEntity->getTargetType());

        $job->run($data);
    }

    private function runService(JobEntity $jobEntity): void
    {
        $serviceName = $jobEntity->getServiceName();

        if (!$serviceName) {
            throw new Error("Job with empty serviceName.");
        }

        if (!$this->serviceFactory->checkExists($serviceName)) {
            throw new Error();
        }

        $service = $this->serviceFactory->create($serviceName);

        $methodName = $jobEntity->getMethodName();

        if (!$methodName) {
            throw new Error('Job with empty methodName.');
        }

        if (!method_exists($service, $methodName)) {
            throw new Error("No method '{$methodName}' in service '{$serviceName}'.");
        }

        $service->$methodName($jobEntity->getData(), $jobEntity->getTargetId(), $jobEntity->getTargetType());
    }

    private function setJobRunning(JobEntity $jobEntity): void
    {
        if (!$jobEntity->getStartedAt()) {
            $jobEntity->set('startedAt', DateTimeUtil::getSystemNowString());
        }

        $jobEntity->set('status', Status::RUNNING);
        $jobEntity->set('pid', System::getPid());

        $this->entityManager->saveEntity($jobEntity);
    }
}
