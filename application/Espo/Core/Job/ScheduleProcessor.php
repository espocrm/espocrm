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
    ORM\EntityManager,
    Utils\Log,
};

use Espo\{
    Entities\ScheduledJob as ScheduledJobEntity,
};

use Cron\CronExpression;

use Throwable;
use Exception;

/**
 * Creates jobs from scheduled jobs according scheduling.
 */
class ScheduleProcessor
{
    private $asSoonAsPossibleSchedulingList = [
        '*',
        '* *',
        '* * *',
        '* * * *',
        '* * * * *',
        '* * * * * *',
    ];

    private $log;

    private $entityManager;

    private $queueUtil;

    private $scheduleUtil;

    private $jobFactory;

    public function __construct(
        Log $log,
        EntityManager $entityManager,
        QueueUtil $queueUtil,
        ScheduleUtil $scheduleUtil,
        JobFactory $jobFactory
    ) {
        $this->log = $log;
        $this->entityManager = $entityManager;
        $this->queueUtil = $queueUtil;
        $this->scheduleUtil = $scheduleUtil;
        $this->jobFactory = $jobFactory;
    }

    public function process() : void
    {
        $activeScheduledJobList = $this->scheduleUtil->getActiveScheduledJobList();

        $runningScheduledJobIdList = $this->queueUtil->getRunningScheduledJobIdList();

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
        ScheduledJobEntity $scheduledJob,
        array $runningScheduledJobIdList
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

            $jobAlreadyExists = $this->queueUtil->hasScheduledJobOnMinute($id, $executeTime);

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

        $pendingCount = $this->queueUtil->getPendingCountByScheduledJobId($id);

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
            'status' => JobManager::PENDING,
            'scheduledJobId' => $id,
            'executeTime' => $executeTime,
        ]);
    }
}
