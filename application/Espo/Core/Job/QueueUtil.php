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

use Countable;
use Espo\Core\Job\QueueProcessor\Params;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\System;
use Espo\Core\Job\Job\Status;
use Espo\Entities\Job as JobEntity;

use DateTime;
use Espo\ORM\Collection;
use Espo\ORM\Name\Attribute;
use Exception;
use LogicException;

class QueueUtil
{
    private const NOT_EXISTING_PROCESS_PERIOD = 300;
    private const READY_NOT_STARTED_PERIOD = 60;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private ScheduleUtil $scheduleUtil,
        private MetadataProvider $metadataProvider
    ) {}

    public function isJobPending(string $id): bool
    {
        /** @var ?JobEntity $job */
        $job = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([Attribute::ID, 'status'])
            ->where([Attribute::ID => $id])
            ->forUpdate()
            ->findOne();

        if (!$job) {
            return false;
        }

        return $job->getStatus() === Status::PENDING;
    }

    /**
     * @return Collection<JobEntity>&Countable
     */
    public function getPendingJobs(Params $params): Collection
    {
        $queue = $params->getQueue();
        $group = $params->getGroup();
        $limit = $params->getLimit();

        $builder = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([
                Attribute::ID,
                'scheduledJobId',
                'scheduledJobJob',
                'executeTime',
                'targetId',
                'targetType',
                'targetGroup',
                'methodName',
                'serviceName',
                'className',
                'job',
                'data',
            ])
            ->where([
                'status' => Status::PENDING,
                'executeTime<=' => DateTimeUtil::getSystemNowString(),
                'queue' => $queue,
                'group' => $group,
            ])
            ->order('number');

        if ($limit) {
            $builder->limit(0, $limit);
        }

        return $builder->sth()->find();
    }

    public function isScheduledJobRunning(
        string $scheduledJobId,
        ?string $targetId = null,
        ?string $targetType = null,
        ?string $targetGroup = null
    ): bool {

        $where = [
            'scheduledJobId' => $scheduledJobId,
            'status' => [
                Status::RUNNING,
                Status::READY,
            ],
        ];

        if ($targetId && $targetType) {
            $where['targetId'] = $targetId;
            $where['targetType'] = $targetType;
        }

        if ($targetGroup) {
            $where['targetGroup'] = $targetGroup;
        }

        return (bool) $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([Attribute::ID])
            ->where($where)
            ->findOne();
    }

    /**
     * @return string[]
     */
    public function getRunningScheduledJobIdList(): array
    {
        $list = [];

        $jobList = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select(['scheduledJobId'])
            ->leftJoin('scheduledJob')
            ->where([
                'status' => [
                    Status::RUNNING,
                    Status::READY,
                ],
                'scheduledJobId!=' => null,
                'scheduledJob.job!=' => $this->metadataProvider->getPreparableJobNameList(),
            ])
            ->order('executeTime')
            ->find();

        foreach ($jobList as $job) {
            $scheduledJobId = $job->getScheduledJobId();

            if (!$scheduledJobId) {
                continue;
            }

            $list[] = $scheduledJobId;
        }

        return $list;
    }

    public function hasScheduledJobOnMinute(string $scheduledJobId, string $time): bool
    {
        try {
            $dateObj = new DateTime($time);
        } catch (Exception $e) {
            throw new LogicException($e->getMessage());
        }

        $fromString = $dateObj->format('Y-m-d H:i:00');
        $toString = $dateObj->format('Y-m-d H:i:59');

        $job = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([Attribute::ID])
            ->where([
                'scheduledJobId' => $scheduledJobId,
                'status' => [ // This forces usage of an appropriate index.
                    Status::PENDING,
                    Status::READY,
                    Status::RUNNING,
                    Status::SUCCESS,
                ],
                'executeTime>=' => $fromString,
                'executeTime<=' => $toString,
            ])
            ->findOne();

        return (bool) $job;
    }

    public function getPendingCountByScheduledJobId(string $scheduledJobId): int
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->where([
                'scheduledJobId' => $scheduledJobId,
                'status' => Status::PENDING,
            ])
            ->count();
    }

    public function markJobsFailed(): void
    {
        $this->markJobsFailedByNotExistingProcesses();
        $this->markJobsFailedReadyNotStarted();
        $this->markJobsFailedByPeriod(true);
        $this->markJobsFailedByPeriod();
    }

    private function markJobsFailedByNotExistingProcesses(): void
    {
        $timeThreshold = time() - $this->config->get(
            'jobPeriodForNotExistingProcess',
            self::NOT_EXISTING_PROCESS_PERIOD
        );

        $dateTimeThreshold = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $timeThreshold);

        $runningJobList = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([
                Attribute::ID,
                'scheduledJobId',
                'executeTime',
                'targetId',
                'targetType',
                'pid',
                'startedAt',
            ])
            ->where([
                'status' => Status::RUNNING,
                'startedAt<' => $dateTimeThreshold,
            ])
            ->find();

        $failedJobList = [];

        foreach ($runningJobList as $job) {
            $pid = $job->getPid();

            if ($pid && !System::isProcessActive($pid)) {
                $failedJobList[] = $job;
            }
        }

        $this->markJobListFailed($failedJobList);
    }

    private function markJobsFailedReadyNotStarted(): void
    {
        $timeThreshold = time() -
            $this->config->get('jobPeriodForReadyNotStarted', self::READY_NOT_STARTED_PERIOD);

        $dateTimeThreshold = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $timeThreshold);

        $failedJobList = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([
                Attribute::ID,
                'scheduledJobId',
                'executeTime',
                'targetId',
                'targetType',
                'pid',
                'startedAt',
            ])
            ->where([
                'status' => Status::READY,
                'startedAt<' => $dateTimeThreshold,
            ])
            ->find();

        $this->markJobListFailed($failedJobList);
    }

    protected function markJobsFailedByPeriod(bool $isForActiveProcesses = false): void
    {
        $period = 'jobPeriod';

        if ($isForActiveProcesses) {
            $period = 'jobPeriodForActiveProcess';
        }

        $timeThreshold = time() - $this->config->get($period, 7800);

        $dateTimeThreshold = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $timeThreshold);

        $runningJobList = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([
                Attribute::ID,
                'scheduledJobId',
                'executeTime',
                'targetId',
                'targetType',
                'pid',
                'startedAt'
            ])
            ->where([
                'status' => Status::RUNNING,
                'executeTime<' => $dateTimeThreshold,
            ])
            ->find();

        $failedJobList = [];

        foreach ($runningJobList as $job) {
            if ($isForActiveProcesses) {
                $failedJobList[] = $job;

                continue;
            }

            $pid = $job->getPid();

            if (!$pid || !System::isProcessActive($pid)) {
                $failedJobList[] = $job;
            }
        }

        $this->markJobListFailed($failedJobList);
    }

    /**
     * @param iterable<JobEntity> $jobList
     */
    protected function markJobListFailed(iterable $jobList): void
    {
        if (is_countable($jobList) && !count($jobList)) {
            return;
        }

        $jobIdList = [];

        foreach ($jobList as $job) {
            $jobIdList[] = $job->getId();
        }

        $updateQuery = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(JobEntity::ENTITY_TYPE)
            ->set([
                'status' => Status::FAILED,
                'attempts' => 0,
            ])
            ->where([
                Attribute::ID => $jobIdList,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);

        foreach ($jobList as $job) {
            $scheduledJobId = $job->getScheduledJobId();

            if (!$scheduledJobId) {
                continue;
            }

            $this->scheduleUtil->addLogRecord(
                $scheduledJobId,
                Status::FAILED,
                $job->getStartedAt(),
                $job->getTargetId(),
                $job->getTargetType()
            );
        }
    }

    /**
     * Remove pending duplicate jobs, no need to run twice the same job.
     */
    public function removePendingJobDuplicates(): void
    {
        $duplicateJobList = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select(['scheduledJobId'])
            ->leftJoin('scheduledJob')
            ->where([
                'scheduledJobId!=' => null,
                'status' => Status::PENDING,
                'executeTime<=' => DateTimeUtil::getSystemNowString(),
                'scheduledJob.job!=' => $this->metadataProvider->getPreparableJobNameList(),
                'targetId' => null,
            ])
            ->group(['scheduledJobId'])
            ->having([
                'COUNT:id>' => 1,
            ])
            ->order('MAX:executeTime')
            ->find();

        $scheduledJobIdList = [];

        foreach ($duplicateJobList as $duplicateJob) {
            $scheduledJobId = $duplicateJob->getScheduledJobId();

            if (!$scheduledJobId) {
                continue;
            }

            $scheduledJobIdList[] = $scheduledJobId;
        }

        foreach ($scheduledJobIdList as $scheduledJobId) {
            $toRemoveJobList = $this->entityManager
                ->getRDBRepositoryByClass(JobEntity::class)
                ->select([Attribute::ID])
                ->where([
                    'scheduledJobId' => $scheduledJobId,
                    'status' => Status::PENDING,
                ])
                ->order('executeTime')
                ->limit(0, 1000)
                ->find();

            $jobIdList = [];

            foreach ($toRemoveJobList as $job) {
                $jobIdList[] = $job->getId();
            }

            if (!count($jobIdList)) {
                continue;
            }

            $delete = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from(JobEntity::ENTITY_TYPE)
                ->where([Attribute::ID => $jobIdList])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }
    }

    /**
     * Handle job attempts. Change failed to pending if attempts left.
     */
    public function updateFailedJobAttempts(): void
    {
        $jobCollection = $this->entityManager
            ->getRDBRepositoryByClass(JobEntity::class)
            ->select([
                Attribute::ID,
                'attempts',
                'failedAttempts',
            ])
            ->where([
                'status' => Status::FAILED,
                'executeTime<=' => DateTimeUtil::getSystemNowString(),
                'attempts>' => 0,
            ])
            ->find();

         foreach ($jobCollection as $job) {
            $failedAttempts = $job->getFailedAttempts();
            $attempts = $job->getAttempts();

            $job->set([
                'status' => Status::PENDING,
                'attempts' => $attempts - 1,
                'failedAttempts' => $failedAttempts + 1,
            ]);

            $this->entityManager->saveEntity($job);
        }
    }
}
