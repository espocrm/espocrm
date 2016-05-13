<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
use \PDO;
use \Espo\Core\CronManager;
use \Espo\Core\Utils\Config;
use \Espo\Core\ORM\EntityManager;

class Job
{
    private $config;

    private $entityManager;

    private $cronScheduledJob;

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

    /**
     * Get Pending Jobs
     *
     * @return array
     */
    public function getPendingJobList()
    {
        $jobConfigs = $this->getConfig()->get('cron');
        $limit = !empty($jobConfigs['maxJobNumber']) ? intval($jobConfigs['maxJobNumber']) : 0;

        $jobList = $this->getJobList(CronManager::PENDING, $limit);

        $runningScheduledJobIdList = $this->getRunningScheduledJobIdList();

        $list = [];
        foreach ($jobList as $row) {
            if (!in_array($row['scheduled_job_id'], $runningScheduledJobIdList)) {
                $list[] = $row;
            }
        }

        return $list;
    }

    public function getRunningScheduledJobIdList()
    {
        $list = [];

        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT scheduled_job_id FROM job
            WHERE
                `status` = 'Running' AND
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
     * Get job list, which execution date in jobPeriod time
     *
     * @param  string $status
     * @param  int $limit
     *
     * @return array
     */
    public function getJobList($status = CronManager::PENDING, $limit = null)
    {
        $currentTime = time();

        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT * FROM job
            WHERE
                `status` = " . $pdo->quote($status) . "
                AND execute_time <= '".date('Y-m-d H:i:s', $currentTime)."'
                AND deleted = 0
            ORDER BY execute_time ASC
        ";

        if ($limit) {
            $query .= " LIMIT " . $limit ;
        }

        $sth = $pdo->prepare($query);
        $sth->execute();

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * Get Jobs by ScheduledJobId and date
     *
     * @param  string $scheduledJobId
     * @param  string $time
     *
     * @return array
     */
    public function getJobByScheduledJob($scheduledJobId, $time)
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

    /**
     * Mark pending jobs (all jobs that exceeded jobPeriod)
     *
     * @return void
     */
    public function markFailedJobs()
    {
        $jobConfigs = $this->getConfig()->get('cron');

        $currentTime = time();
        $periodTime = $currentTime - intval($jobConfigs['jobPeriod']);

        $pdo = $this->getEntityManager()->getPDO();

        $select = "
            SELECT id, scheduled_job_id, execute_time, target_id, target_type FROM `job`
            WHERE
            `status` = '" . CronManager::RUNNING ."' AND execute_time < '".date('Y-m-d H:i:s', $periodTime)."'
        ";
        $sth = $pdo->prepare($select);
        $sth->execute();

        $jobData = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
           $jobData[$row['id']] = $row;
        }

        $update = "
            UPDATE job
            SET `status` = '". CronManager::FAILED ."'
            WHERE id IN ('".implode("', '", array_keys($jobData))."')
        ";
        $sth = $pdo->prepare($update);
        $sth->execute();

        //add status 'Failed' to SchediledJobLog
        $cronScheduledJob = $this->getCronScheduledJob();
        foreach ($jobData as $jobId => $job) {
            if (!empty($job['scheduled_job_id'])) {
                $cronScheduledJob->addLogRecord($job['scheduled_job_id'], CronManager::FAILED, $job['execute_time'], $job['target_id'], $job['target_type']);
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
            ORDER BY execute_time ASC
        ";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $duplicateJobList = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($duplicateJobList as $row) {
            if (!empty($row['scheduled_job_id'])) {

                /* no possibility to use limit in update or subqueries */
                $query = "
                    SELECT id FROM `job`
                    WHERE
                        scheduled_job_id = '" . $row['scheduled_job_id'] . "' AND
                        `status` = '" . CronManager::PENDING ."'
                        ORDER BY execute_time
                        DESC LIMIT 1, 100000
                    ";
                $sth = $pdo->prepare($query);
                $sth->execute();
                $jobIds = $sth->fetchAll(PDO::FETCH_COLUMN);

                $update = "
                    UPDATE job
                    SET deleted = 1
                    WHERE
                        id IN ('". implode("', '", $jobIds)."')
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
                        attempts = '".$attempts."',
                        failed_attempts = '".$failedAttempts."'
                    WHERE
                        id = '".$row['id']."'
                ";
                $pdo->prepare($update)->execute();
            }
        }
    }
}