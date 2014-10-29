<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/
namespace Espo\Services;

use Espo\Core\CronManager;
use PDO;

class Job extends
    Record
{

    public function getPendingJobs()
    {
        /** Mark Failed old jobs and remove pending duplicates */
        $this->markFailedJobs();
        $this->removePendingJobDuplicates();
        $jobList = $this->getActiveJobs();
        $runningScheduledJobs = $this->getActiveJobs('scheduled_job_id', CronManager::RUNNING, PDO::FETCH_COLUMN);
        $list = array();
        foreach ($jobList as $row) {
            if (!in_array($row['scheduled_job_id'], $runningScheduledJobs)) {
                $list[] = $row;
            }
        }
        return $list;
    }

    /**
     * Mark pending jobs (all jobs that exceeded jobPeriod)
     *
     * @return void
     */
    protected function markFailedJobs()
    {
        $jobConfigs = $this->getConfig()->get('cron');
        $currentTime = time();
        $periodTime = $currentTime - intval($jobConfigs['jobPeriod']);
        $update = "UPDATE job SET `status` = '" . CronManager::FAILED . "' WHERE
                    (`status` = '" . CronManager::PENDING . "' OR `status` = '" . CronManager::RUNNING . "')
                    AND execute_time < '" . date('Y-m-d H:i:s', $periodTime) . "' ";
        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($update);
        $sth->execute();
    }

    /**
     * Remove pending duplicate jobs, no need to run twice the same job
     *
     * @return void
     */
    protected function removePendingJobDuplicates()
    {
        $duplicateJobs = $this->getActiveJobs('DISTINCT scheduled_job_id');
        $pdo = $this->getEntityManager()->getPDO();
        foreach ($duplicateJobs as $row) {
            if (!empty($row['scheduled_job_id'])) {
                /* no possibility to use limit in update or subqueries */
                $query = "SELECT id FROM `job` WHERE scheduled_job_id = '" . $row['scheduled_job_id'] . "'
                            AND `status` = '" . CronManager::PENDING . "'
                            ORDER BY execute_time
                            DESC LIMIT 1, 100000 ";
                $sth = $pdo->prepare($query);
                $sth->execute();
                $jobIds = $sth->fetchAll(PDO::FETCH_COLUMN);
                $update = "UPDATE job SET deleted = 1 WHERE
                            id IN ('" . implode("', '", $jobIds) . "') ";
                $sth = $pdo->prepare($update);
                $sth->execute();
            }
        }
    }

    /**
     * Get active Jobs, which execution date in jobPeriod time
     *
     * @param  string $displayColumns
     * @param string  $status
     *
     * @param int     $fetchMode
     *
     * @return array
     */
    protected function getActiveJobs(
        $displayColumns = '*',
        $status = CronManager::PENDING,
        $fetchMode = PDO::FETCH_ASSOC
    ){
        $jobConfigs = $this->getConfig()->get('cron');
        $currentTime = time();
        $periodTime = $currentTime - intval($jobConfigs['jobPeriod']);
        $limit = empty($jobConfigs['maxJobNumber']) ? '' : 'LIMIT ' . $jobConfigs['maxJobNumber'];
        $query = "SELECT " . $displayColumns . " FROM job WHERE
                    `status` = '" . $status . "'
                    AND execute_time BETWEEN '" . date('Y-m-d H:i:s', $periodTime) . "' AND '" . date('Y-m-d H:i:s',
                $currentTime) . "'
                    AND deleted = 0
                    ORDER BY execute_time DESC " . $limit;
        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();
        $rows = $sth->fetchAll($fetchMode);
        return $rows;
    }

    public function getJobByScheduledJob($scheduledJobId, $date)
    {
        $query = "SELECT * FROM job WHERE
                    scheduled_job_id = '" . $scheduledJobId . "'
                    AND execute_time = '" . $date . "'
                    AND deleted = 0
                    LIMIT 1";
        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();
        $scheduledJob = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $scheduledJob;
    }
}

