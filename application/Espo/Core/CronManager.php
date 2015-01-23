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
 ************************************************************************/

namespace Espo\Core;

use \PDO;

class CronManager
{
    private $container;
    private $config;
    private $fileManager;
    private $entityManager;

    private $scheduledJobCron;
    private $serviceCron;

    const PENDING = 'Pending';
    const RUNNING = 'Running';
    const SUCCESS = 'Success';
    const FAILED = 'Failed';

    protected $lastRunTime = 'data/cache/application/cronLastRunTime.php';

    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;

        $this->config = $this->container->get('config');
        $this->fileManager = $this->container->get('fileManager');
        $this->entityManager = $this->container->get('entityManager');

        $this->scheduledJobCron = $this->container->get('scheduledJob');
        $this->serviceCron = new \Espo\Core\Cron\Service( $this->container->get('serviceFactory'));
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getScheduledJobCron()
    {
        return $this->scheduledJobCron;
    }

    protected function getServiceCron()
    {
        return $this->serviceCron;
    }

    protected function getLastRunTime()
    {
        $lastRunTime = $this->getFileManager()->getPhpContents($this->lastRunTime);
        if (!is_int($lastRunTime)) {
            $lastRunTime = time() - (intval($this->getConfig()->get('cron.minExecutionTime')) + 60);
        }

        return $lastRunTime;
    }

    protected function setLastRunTime($time)
    {
        return $this->getFileManager()->putPhpContents($this->lastRunTime, $time);
    }

    protected function checkLastRunTime()
    {
        $currentTime = time();
        $lastRunTime = $this->getLastRunTime();
        $minTime = $this->getConfig()->get('cron.minExecutionTime');

        if ($currentTime > ($lastRunTime + $minTime) ) {
            return true;
        }

        return false;
    }

    public function run()
    {
        if (!$this->checkLastRunTime()) {
            $GLOBALS['log']->info('Cron Manager: Stop cron running, too frequent execution.');
            return;
        }

        $this->setLastRunTime(time());

        $entityManager = $this->getEntityManager();

        $jobRepository = $entityManager->getRepository('Job');
        $scheduledJobRepository = $entityManager->getRepository('ScheduledJob');

        //Check scheduled jobs and create related jobs
        $this->createJobsFromScheduledJobs();

        $pendingJobs = $jobRepository->getPendingJobs();

        foreach ($pendingJobs as $job) {

            $jobEntity = $jobRepository->get($job['id']);

            if (!isset($jobEntity)) {
                $GLOBALS['log']->error('CronManager: empty Job entity ['.$job['id'].'].');
                continue;
            }

            $jobEntity->set('status', self::RUNNING);
            $entityManager->saveEntity($jobEntity);

            $isSuccess = true;

            try {
                if (!empty($job['scheduled_job_id'])) {
                    $this->getScheduledJobCron()->run($job);
                } else {
                    $this->getServiceCron()->run($job);
                }
            } catch (\Exception $e) {
                $isSuccess = false;
                $GLOBALS['log']->error('CronManager: Failed job running, job ['.$job['id'].']. Error Details: '.$e->getMessage());
            }

            $status = $isSuccess ? self::SUCCESS : self::FAILED;

            $jobEntity->set('status', $status);
            $entityManager->saveEntity($jobEntity);

            //set status in the schedulerJobLog
            if (!empty($job['scheduled_job_id'])) {
                $scheduledJobRepository->addLogRecord($job['scheduled_job_id'], $status);
            }
        }

    }

    /**
     * Check scheduled jobs and create related jobs
     *
     * @return array  List of created Jobs
     */
    protected function createJobsFromScheduledJobs()
    {
        $entityManager = $this->getEntityManager();

        $activeScheduledJobs = $entityManager->getRepository('ScheduledJob')->getActiveJobs();

        $jobRepository = $entityManager->getRepository('Job');
        $runningScheduledJobs = $jobRepository->getActiveJobs('scheduled_job_id', self::RUNNING, PDO::FETCH_COLUMN);

        $createdJobs = array();
        foreach ($activeScheduledJobs as $scheduledJob) {

            if (in_array($scheduledJob['id'], $runningScheduledJobs)) {
                continue;
            }

            $scheduling = $scheduledJob['scheduling'];

            $cronExpression = \Cron\CronExpression::factory($scheduling);

            try {
                $prevDate = $cronExpression->getPreviousRunDate()->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $GLOBALS['log']->error('CronManager: ScheduledJob ['.$scheduledJob['id'].']: CronExpression - Impossible CRON expression ['.$scheduling.']');
                continue;
            }

            if ($cronExpression->isDue()) {
                $prevDate = date('Y-m-d H:i:00');
            }

            $existsJob = $jobRepository->getJobByScheduledJob($scheduledJob['id'], $prevDate);

            if (!isset($existsJob) || empty($existsJob)) {
                //create a new job
                $jobEntity = $jobRepository->get();
                $jobEntity->set(array(
                    'name' => $scheduledJob['name'],
                    'status' => self::PENDING,
                    'scheduledJobId' => $scheduledJob['id'],
                    'executeTime' => $prevDate,
                    'method' => $scheduledJob['job'],
                ));
                $jobEntityId = $jobRepository->save($jobEntity);
                if (!empty($jobEntityId)) {
                    $createdJobs[] = $jobEntityId;
                }
            }
        }

        return $createdJobs;
    }
}

