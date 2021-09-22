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

use Espo\Core\Job\Preparator\Data as PreparatorData;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Log,
    Utils\DateTime as DateTimeUtil,
};

use Espo\Core\Job\Job\Status;

use Espo\Entities\{
    ScheduledJob as ScheduledJobEntity,
    Job as JobEntity,
};

use Cron\CronExpression;

use Throwable;
use Exception;
use DateTimeImmutable;

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

    private $metadataProvider;

    private $preparatorFactory;

    public function __construct(
        Log $log,
        EntityManager $entityManager,
        QueueUtil $queueUtil,
        ScheduleUtil $scheduleUtil,
        PreparatorFactory $preparatorFactory,
        MetadataProvider $metadataProvider
    ) {
        $this->log = $log;
        $this->entityManager = $entityManager;
        $this->queueUtil = $queueUtil;
        $this->scheduleUtil = $scheduleUtil;
        $this->preparatorFactory = $preparatorFactory;
        $this->metadataProvider = $metadataProvider;
    }

    public function process(): void
    {
        $activeScheduledJobList = $this->scheduleUtil->getActiveScheduledJobList();

        $runningScheduledJobIdList = $this->queueUtil->getRunningScheduledJobIdList();

        foreach ($activeScheduledJobList as $scheduledJob) {
            try {
                $isRunning = in_array($scheduledJob->getId(), $runningScheduledJobIdList);

                $this->createJobsFromScheduledJob($scheduledJob, $isRunning);
            }
            catch (Throwable $e) {
                $id = $scheduledJob->getId();

                $this->log->error("Scheduled Job '{$id}': " . $e->getMessage());
            }
        }
    }

    private function createJobsFromScheduledJob(ScheduledJobEntity $scheduledJob, bool $isRunning): void
    {
        $id = $scheduledJob->getId();

        $executeTime = $this->findExecuteTime($scheduledJob);

        if ($executeTime === null) {
            return;
        }

        $asSoonAsPossible = $this->checkAsSoonAsPossible($scheduledJob);

        if (!$asSoonAsPossible) {
            if ($this->queueUtil->hasScheduledJobOnMinute($id, $executeTime)) {
                return;
            }
        }

        $jobName = $scheduledJob->getJob();

        if ($this->metadataProvider->isJobPreparable($jobName)) {
            $preparator = $this->preparatorFactory->create($jobName);

            $data = new PreparatorData($scheduledJob->getId(), $scheduledJob->getName());

            $executeTimeObj = DateTimeImmutable
                ::createFromFormat(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $executeTime);

            $preparator->prepare($data, $executeTimeObj);

            return;
        }

        if ($isRunning) {
            return;
        }

        $pendingCount = $this->queueUtil->getPendingCountByScheduledJobId($id);

        $pendingLimit = $asSoonAsPossible ? 0 : 1;

        if ($pendingCount > $pendingLimit) {
            return;
        }

        $this->entityManager->createEntity(JobEntity::ENTITY_TYPE, [
            'name' => $scheduledJob->getName(),
            'status' => Status::PENDING,
            'scheduledJobId' => $id,
            'executeTime' => $executeTime,
        ]);
    }

    private function checkAsSoonAsPossible(ScheduledJobEntity $scheduledJob): bool
    {
        return in_array($scheduledJob->getScheduling(), $this->asSoonAsPossibleSchedulingList);
    }

    private function findExecuteTime(ScheduledJobEntity $scheduledJob): ?string
    {
        $scheduling = $scheduledJob->getScheduling();

        $id = $scheduledJob->getId();

        $asSoonAsPossible = in_array($scheduling, $this->asSoonAsPossibleSchedulingList);

        if ($asSoonAsPossible) {
            return DateTimeUtil::getSystemNowString();
        }

        try {
            $cronExpression = CronExpression::factory($scheduling);
        }
        catch (Exception $e) {
            $this->log->error(
                "Scheduled Job '{$id}': Scheduling expression error: " .
                $e->getMessage() . '.'
            );

            return null;
        }

        try {
            return $cronExpression->getNextRunDate()->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }
        catch (Exception $e) {
            $this->log->error(
                "Scheduled Job '{$id}': Unsupported scheduling expression '{$scheduling}'."
            );

            return null;
        }
    }
}
