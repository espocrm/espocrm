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
    ORM\EntityManager,
    Utils\System,
    Utils\DateTime as DateTimeUtil,
};

use Espo\Core\Job\Job\Status;

use Espo\Entities\Job as JobEntity;

use Espo\ORM\Collection;

use DateTime;

class QueueUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var EntityManager
     */
    private $entityManager;

    private $scheduleUtil;

    private $metadataProvider;

    private const NOT_EXISTING_PROCESS_PERIOD = 300;

    private const READY_NOT_STARTED_PERIOD = 60;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        ScheduleUtil $scheduleUtil,
        MetadataProvider $metadataProvider
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->scheduleUtil = $scheduleUtil;
        $this->metadataProvider = $metadataProvider;
    }

    public function isJobPending(string $id): bool
    {
        $job = $this->entityManager
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
            ->select(['id', 'status'])
            ->where([
                'id' => $id,
            ])
            ->forUpdate()
            ->findOne();

        if (!$job) {
            return false;
        }

        return $job->get('status') === Status::PENDING;
    }

    /**
     * @return JobEntity[]
     * @phpstan-return Collection&iterable<JobEntity>
     */
    public function getPendingJobList(?string $queue = null, ?string $group = null, int $limit = 0): Collection
    {
        $builder = $this->entityManager
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
            ->select([
                'id',
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

        /** @var Collection&iterable<JobEntity> $collection */
        $collection = $builder->find();

        return $collection;
    }

    public function isScheduledJobRunning(
        string $scheduledJobId,
        ?string $targetId = null,
        ?string $targetType = null,
        ?string $targetGroup = null
    ): bool {

        $where = [
            'scheduledJobId' => $scheduledJobId,
            'status' => [Status::RUNNING, Status::READY],
        ];

        if ($targetId && $targetType) {
            $where['targetId'] = $targetId;
            $where['targetType'] = $targetType;
        }

        if ($targetGroup) {
            $where['targetGroup'] = $targetGroup;
        }

        return (bool) $this->entityManager
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
            ->select(['id'])
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
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
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
            $list[] = $job->get('scheduledJobId');
        }

        return $list;
    }

    public function hasScheduledJobOnMinute(string $scheduledJobId, string $time): bool
    {
        $dateObj = new DateTime($time);

        $fromString = $dateObj->format('Y-m-d H:i:00');
        $toString = $dateObj->format('Y-m-d H:i:59');

        $job = $this->entityManager
            ->getRDBRepository('Job')
            ->select(['id'])
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
        $countPending = $this->entityManager
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
            ->where([
                'scheduledJobId' => $scheduledJobId,
                'status' => Status::PENDING,
            ])
            ->count();

        return $countPending;
    }

    public function markJobsFailed(): void
    {
        $this->markJobsFailedByNotExistingProcesses();
        $this->markJobsFailedReadyNotStarted();
        $this->markJobsFailedByPeriod(true);
        $this->markJobsFailedByPeriod();
    }

    protected function markJobsFailedByNotExistingProcesses(): void
    {
        $timeThreshold = time() - $this->config->get(
            'jobPeriodForNotExistingProcess',
            self::NOT_EXISTING_PROCESS_PERIOD
        );

        $dateTimeThreshold = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $timeThreshold);

        $runningJobList = $this->entityManager
            ->getRDBRepository('Job')
            ->select([
                'id',
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
            if ($job->get('pid') && !System::isProcessActive($job->get('pid'))) {
                $failedJobList[] = $job;
            }
        }

        $this->markJobListFailed($failedJobList);
    }

    protected function markJobsFailedReadyNotStarted(): void
    {
        $timeThreshold = time() -
            $this->config->get('jobPeriodForReadyNotStarted', SELF::READY_NOT_STARTED_PERIOD);

        $dateTimeThreshold = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $timeThreshold);

        $failedJobList = $this->entityManager
            ->getRDBRepository('Job')
            ->select([
                'id',
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
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
            ->select([
                'id',
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

            if (!$job->get('pid') || !System::isProcessActive($job->get('pid'))) {
                $failedJobList[] = $job;
            }
        }

        $this->markJobListFailed($failedJobList);
    }

    protected function markJobListFailed(iterable $jobList): void
    {
        if (!count($jobList)) {
            return;
        }

        $jobIdList = [];

        foreach ($jobList as $job) {
            $jobIdList[] = $job->getId();
        }

        $updateQuery = $this->entityManager->getQueryBuilder()
            ->update()
            ->in(JobEntity::ENTITY_TYPE)
            ->set([
                'status' => Status::FAILED,
                'attempts' => 0,
            ])
            ->where([
                'id' => $jobIdList,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);

        foreach ($jobList as $job) {
            if (!$job->get('scheduledJobId')) {
                continue;
            }

            $this->scheduleUtil->addLogRecord(
                $job->get('scheduledJobId'),
                Status::FAILED,
                $job->get('startedAt'),
                $job->get('targetId'),
                $job->get('targetType')
            );
        }
    }

    /**
     * Remove pending duplicate jobs, no need to run twice the same job.
     */
    public function removePendingJobDuplicates(): void
    {
        $duplicateJobList = $this->entityManager
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
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
            if (!$duplicateJob->get('scheduledJobId')) {
                continue;
            }

            $scheduledJobIdList[] = $duplicateJob->get('scheduledJobId');
        }

        foreach ($scheduledJobIdList as $scheduledJobId) {
            $toRemoveJobList = $this->entityManager
                ->getRDBRepository(JobEntity::ENTITY_TYPE)
                ->select(['id'])
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
                ->where([
                    'id' => $jobIdList,
                ])
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
            ->getRDBRepository(JobEntity::ENTITY_TYPE)
            ->select([
                'id',
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
            $failedAttempts = $job->get('failedAttempts') ?? 0;
            $attempts = $job->get('attempts');

            $job->set([
                'status' => Status::PENDING,
                'attempts' => $attempts - 1,
                'failedAttempts' => $failedAttempts + 1,
            ]);

            $this->entityManager->saveEntity($job);
        }
    }
}
