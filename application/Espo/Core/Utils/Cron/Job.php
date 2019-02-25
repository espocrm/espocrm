<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use PDO;
use Espo\Core\CronManager;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\System;

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

        $this->cronScheduledJob = new ScheduledJob($this->config, $this->entityManager);
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getCronScheduledJob()
    {
        return $this->cronScheduledJob;
    }

    public function isJobPending($id)
    {
        return !!$this->getEntityManager()->getRepository('Job')->select(['id'])->where([
            'id' => $id,
            'status' => CronManager::PENDING
        ])->findOne();
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

        return $this->getEntityManager()->getRepository('Job')->find($selectParams);
    }

    public function isScheduledJobRunning($scheduledJobId, $targetId = null, $targetType = null)
    {
        $where = [
            'scheduledJobId' => $scheduledJobId,
            'status' => [CronManager::RUNNING, CronManager::READY],
        ];
        if ($targetId && $targetType) {
            $where['targetId'] = $targetId;
            $where['targetType'] = $targetType;
        }
        return !!$this->getEntityManager()->getRepository('Job')->select(['id'])->where($where)->findOne();
    }

    public function getRunningScheduledJobIdList()
    {
        $list = [];

        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT scheduled_job_id FROM job
            WHERE
                (`status` = 'Running' OR `status` = 'Ready') AND
                scheduled_job_id IS NOT NULL AND
                target_id IS NULL AND
                deleted = 0
            ORDER BY execute_time
        ";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowList as $row) {
            $list[] = $row['scheduled_job_id'];
        }

        return $list;
    }

    /**
     *
     * @param  string $scheduledJobId
     * @param  string $time
     *
     * @return array
     */
    public function getJobByScheduledJobIdOnMinute($scheduledJobId, $time)
    {
        $dateObj = new \DateTime($time);
        $timeWithoutSeconds = $dateObj->format('Y-m-d H:i:');

        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT * FROM job
            WHERE
                scheduled_job_id = ".$pdo->quote($scheduledJobId)."
                AND execute_time LIKE ". $pdo->quote($timeWithoutSeconds . '%') . "
                AND deleted = 0
            LIMIT 1
        ";

        $sth = $pdo->prepare($query);
        $sth->execute();

        $scheduledJob = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $scheduledJob;
    }

    public function getPendingCountByScheduledJobId($scheduledJobId)
    {
        $countPending = $this->getEntityManager()->getRepository('Job')->where([
            'scheduledJobId' => $scheduledJobId,
            'status' => CronManager::PENDING
        ])->count();

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
        $timeThreshold = time() - $this->getConfig()->get('jobPeriodForNotExistingProcess', self::NOT_EXISTING_PROCESS_PERIOD);
        $dateTimeThreshold = date('Y-m-d H:i:s', $timeThreshold);

        $runningJobList = $this->getEntityManager()->getRepository('Job')->select([
            'id',
            'scheduledJobId',
            'executeTime',
            'targetId',
            'targetType',
            'pid',
            'startedAt'
        ])->where([
            'status' => CronManager::RUNNING,
            'startedAt<' => $dateTimeThreshold
        ])->find();

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
        $timeThreshold = time() - $this->getConfig()->get('jobPeriodForReadyNotStarted', SELF::READY_NOT_STARTED_PERIOD);
        $dateTimeThreshold = date('Y-m-d H:i:s', $timeThreshold);

        $failedJobList = $this->getEntityManager()->getRepository('Job')->select([
            'id',
            'scheduledJobId',
            'executeTime',
            'targetId',
            'targetType',
            'pid',
            'startedAt',
        ])->where([
            'status' => CronManager::READY,
            'startedAt<' => $dateTimeThreshold
        ])->find();

        $this->markJobListFailed($failedJobList);
    }

    protected function markJobsFailedByPeriod($isForActiveProcesses = false)
    {
        $period = 'jobPeriod';
        if ($isForActiveProcesses) {
            $period = 'jobPeriodForActiveProcess';
        }

        $timeThreshold = time() - $this->getConfig()->get($period, 7800);
        $dateTimeThreshold = date('Y-m-d H:i:s', $timeThreshold);

        $runningJobList = $this->getEntityManager()->getRepository('Job')->select([
            'id',
            'scheduledJobId',
            'executeTime',
            'targetId',
            'targetType',
            'pid',
            'startedAt'
        ])->where([
            'status' => CronManager::RUNNING,
            'executeTime<' => $dateTimeThreshold
        ])->find();

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

        $quotedIdList = [];
        foreach ($jobIdList as $id) {
            $quotedIdList[] = $this->getEntityManager()->getPDO()->quote($id);
        }

        $sql = "
            UPDATE job
            SET `status` = '" . CronManager::FAILED . "', attempts = 0
            WHERE id IN (".implode(", ", $quotedIdList).")
        ";

        $this->getEntityManager()->getPDO()->query($sql);

        foreach ($jobList as $job) {
            if ($job->get('scheduledJobId')) {
                $this->getCronScheduledJob()->addLogRecord(
                    $job->get('scheduledJobId'),
                    CronManager::FAILED,
                    $job->get('startedAt'),
                    $job->get('targetId'),
                    $job->get('targetType')
                );
            }
        }
    }

    /**
     * Remove pending duplicate jobs, no need to run twice the same job
     *
     * @return void
     */
    public function removePendingJobDuplicates()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT scheduled_job_id
            FROM job
            WHERE
                scheduled_job_id IS NOT NULL AND
                `status` = '".CronManager::PENDING."' AND
                execute_time <= '".date('Y-m-d H:i:s')."' AND
                target_id IS NULL AND
                deleted = 0
            GROUP BY scheduled_job_id
            HAVING count( * ) > 1
            ORDER BY MAX(execute_time) ASC
        ";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $duplicateJobList = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($duplicateJobList as $row) {
            if (!empty($row['scheduled_job_id'])) {

                $query = "
                    SELECT id FROM `job`
                    WHERE
                        scheduled_job_id = ".$pdo->quote($row['scheduled_job_id'])."
                        AND `status` = '" . CronManager::PENDING ."'
                        ORDER BY execute_time
                        DESC LIMIT 1, 100000
                    ";
                $sth = $pdo->prepare($query);
                $sth->execute();
                $jobIdList = $sth->fetchAll(PDO::FETCH_COLUMN);

                if (empty($jobIdList)) {
                    continue;
                }

                $quotedJobIdList = [];
                foreach ($jobIdList as $jobId) {
                    $quotedJobIdList[] = $pdo->quote($jobId);
                }

                $update = "
                    UPDATE job
                    SET deleted = 1
                    WHERE
                        id IN (".implode(", ", $quotedJobIdList).")
                ";

                $sth = $pdo->prepare($update);
                $sth->execute();
            }
        }
    }

    /**
     * Mark job attempts
     *
     * @return void
     */
    public function updateFailedJobAttempts()
    {
        $query = "
            SELECT * FROM job
            WHERE
                `status` = '" . CronManager::FAILED . "' AND
                deleted = 0 AND
                execute_time <= '".date('Y-m-d H:i:s')."' AND
                attempts > 0
        ";

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            foreach ($rows as $row) {
                $row['failed_attempts'] = isset($row['failed_attempts']) ? $row['failed_attempts'] : 0;

                $attempts = $row['attempts'] - 1;
                $failedAttempts = $row['failed_attempts'] + 1;

                $update = "
                    UPDATE job
                    SET
                        `status` = '" . CronManager::PENDING ."',
                        attempts = ".$pdo->quote($attempts).",
                        failed_attempts = ".$pdo->quote($failedAttempts)."
                    WHERE
                        id = ".$pdo->quote($row['id'])."
                ";
                $pdo->prepare($update)->execute();
            }
        }
    }
}
