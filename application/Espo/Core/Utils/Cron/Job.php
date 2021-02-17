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

namespace Espo\Core\Utils\Cron;

use Espo\Core\{
    CronManager,
    Utils\Config,
    ORM\EntityManager,
    Utils\System,
};

use DateTime;

class Job
{
    private $config;

    private $entityManager;

    private $cronScheduledJob;

    const NOT_EXISTING_PROCESS_PERIOD = 300;

    const READY_NOT_STARTED_PERIOD = 60;

    public function __construct(Config $config, EntityManager $entityManager)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;

        $this->cronScheduledJob = new ScheduledJob($this->entityManager);
    }

    public function isJobPending(string $id) : bool
    {
        $job = $this->entityManager
            ->getRepository('Job')
            ->select(['id', 'status'])
            ->where([
                'id' => $id,
            ])
            ->forUpdate()
            ->findOne();

        if (!$job) {
            return false;
        }

        return $job->get('status') === CronManager::PENDING;
    }

    public function getPendingJobList($queue = null, $limit = 0)
    {
        $selectParams = [
            'select' => [
                'id',
                'scheduledJobId',
                'scheduledJobJob',
                'executeTime',
                'targetId',
                'targetType',
                'methodName',
                'serviceName',
                'job',
                'data',
            ],
            'whereClause' => [
                'status' => CronManager::PENDING,
                'executeTime<=' => date('Y-m-d H:i:s'),
                'queue' => $queue,
            ],
            'orderBy' => 'number',
        ];

        if ($limit) {
            $selectParams['offset'] = 0;
            $selectParams['limit'] = $limit;
        }

        return $this->entityManager->getRepository('Job')->find($selectParams);
    }

    public function isScheduledJobRunning(
        string $scheduledJobId, ?string $targetId = null, ?string $targetType = null
    ) : bool {

        $where = [
            'scheduledJobId' => $scheduledJobId,
            'status' => [CronManager::RUNNING, CronManager::READY],
        ];

        if ($targetId && $targetType) {
            $where['targetId'] = $targetId;
            $where['targetType'] = $targetType;
        }

        return (booL) $this->entityManager
            ->getRepository('Job')
            ->select(['id'])
            ->where($where)
            ->findOne();
    }

    public function getRunningScheduledJobIdList() : array
    {
        $list = [];

        $jobList = $this->entityManager
            ->getRepository('Job')
            ->select(['scheduledJobId'])
            ->where([
                'status' => ['Running', 'Ready'],
                'scheduledJobId!=' => null,
                'targetId=' => null,
            ])
            ->order('executeTime')
            ->find();

        foreach ($jobList as $job) {
            $list[] = $job->get('scheduledJobId');
        }

        return $list;
    }

    public function hasScheduledJobOnMinute(string $scheduledJobId, string $time) : bool
    {
        $dateObj = new DateTime($time);
        $timeWithoutSeconds = $dateObj->format('Y-m-d H:i:');

        $job = $this->entityManager
            ->getRepository('Job')
            ->select(['id'])
            ->where([
                'scheduledJobId' => $scheduledJobId,
                'executeTime*' => $timeWithoutSeconds . '%',
            ])
            ->findOne();

        return (bool) $job;
    }

    public function getPendingCountByScheduledJobId(string $scheduledJobId) : int
    {
        $countPending = $this->entityManager
            ->getRepository('Job')
            ->where([
                'scheduledJobId' => $scheduledJobId,
                'status' => CronManager::PENDING,
            ])
            ->count();

        return $countPending;
    }

    public function markJobsFailed()
    {
        $this->markJobsFailedByNotExistingProcesses();
        $this->markJobsFailedReadyNotStarted();
        $this->markJobsFailedByPeriod(true);
        $this->markJobsFailedByPeriod();
    }

    protected function markJobsFailedByNotExistingProcesses()
    {
        $timeThreshold = time() - $this->config->get(
            'jobPeriodForNotExistingProcess',
            self::NOT_EXISTING_PROCESS_PERIOD
        );

        $dateTimeThreshold = date('Y-m-d H:i:s', $timeThreshold);

        $runningJobList = $this->entityManager
            ->getRepository('Job')
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
                'status' => CronManager::RUNNING,
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

    protected function markJobsFailedReadyNotStarted()
    {
        $timeThreshold = time() -
            $this->config->get('jobPeriodForReadyNotStarted', SELF::READY_NOT_STARTED_PERIOD);

        $dateTimeThreshold = date('Y-m-d H:i:s', $timeThreshold);

        $failedJobList = $this->entityManager
            ->getRepository('Job')
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
                'status' => CronManager::READY,
                'startedAt<' => $dateTimeThreshold,
            ])
            ->find();

        $this->markJobListFailed($failedJobList);
    }

    protected function markJobsFailedByPeriod($isForActiveProcesses = false)
    {
        $period = 'jobPeriod';

        if ($isForActiveProcesses) {
            $period = 'jobPeriodForActiveProcess';
        }

        $timeThreshold = time() - $this->config->get($period, 7800);

        $dateTimeThreshold = date('Y-m-d H:i:s', $timeThreshold);

        $runningJobList = $this->entityManager
            ->getRepository('Job')
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
                'status' => CronManager::RUNNING,
                'executeTime<' => $dateTimeThreshold,
            ])
            ->find();

        $failedJobList = [];

        foreach ($runningJobList as $job) {
            if (!$isForActiveProcesses) {
                if (!$job->get('pid') || !System::isProcessActive($job->get('pid'))) {
                    $failedJobList[] = $job;
                }
            } else {
                $failedJobList[] = $job;
            }
        }

        $this->markJobListFailed($failedJobList);
    }

    protected function markJobListFailed($jobList)
    {
        if (!count($jobList)) return;

        $jobIdList = [];

        foreach ($jobList as $job) {
            $jobIdList[] = $job->id;
        }

        $updateQuery = $this->entityManager->getQueryBuilder()
            ->update()
            ->in('Job')
            ->set([
                'status' => CronManager::FAILED,
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

            $this->cronScheduledJob->addLogRecord(
                $job->get('scheduledJobId'),
                CronManager::FAILED,
                $job->get('startedAt'),
                $job->get('targetId'),
                $job->get('targetType')
            );
        }
    }

    /**
     * Remove pending duplicate jobs, no need to run twice the same job.
     */
    public function removePendingJobDuplicates()
    {
        $duplicateJobList = $this->entityManager
            ->getRepository('Job')
            ->select(['scheduledJobId'])
            ->where([
                'scheduledJobId!=' => null,
                'status' => CronManager::PENDING,
                'executeTime<=' => date('Y-m-d H:i:s'),
                'targetId' => null,
            ])
            ->groupBy(['scheduledJobId'])
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
                ->getRepository('Job')
                ->select(['id'])
                ->where([
                    'scheduledJobId' => $scheduledJobId,
                    'status' => CronManager::PENDING,
                ])
                ->order('executeTime')
                ->limit(0, 1000)
                ->find();

            $jobIdList = [];

            foreach ($toRemoveJobList as $job) {
                $jobIdList[] = $job->id;
            }

            if (!count($jobIdList)) {
                continue;
            }

            $delete = $this->entityManager->getQueryBuilder()
                ->delete()
                ->from('Job')
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
    public function updateFailedJobAttempts()
    {
        $jobList = $this->entityManager->getRepository('Job')
            ->select(['id', 'attempts', 'failedAttempts'])
            ->where([
                'status' => CronManager::FAILED,
                'executeTime<=' => date('Y-m-d H:i:s'),
                'attempts>' => 0,
            ])
            ->find();

        foreach ($jobList as $job) {
            $failedAttempts = $job->get('failedAttempts') ?? 0;
            $attempts = $job->get('attempts');

            $attempts = $attempts - 1;
            $failedAttempts = $failedAttempts + 1;

            $job->set([
                'status' => CronManager::PENDING,
                'attempts' => $attempts,
                'failedAttempts' => $failedAttempts,
            ]);

            $this->entityManager->saveEntity($job);
        }
    }
}
