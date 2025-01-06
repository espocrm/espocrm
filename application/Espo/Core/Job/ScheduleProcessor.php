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

use DateTimeZone;
use Espo\Core\Job\Preparator\Data as PreparatorData;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Log;
use Espo\Core\Job\Job\Status;
use Espo\Entities\Job as JobEntity;
use Espo\Entities\ScheduledJob as ScheduledJobEntity;

use Cron\CronExpression;

use Throwable;
use Exception;
use DateTimeImmutable;

/**
 * Creates jobs from scheduled jobs according scheduling.
 */
class ScheduleProcessor
{
    /** @var string[] */
    private $asSoonAsPossibleSchedulingList = [
        '*',
        '* *',
        '* * *',
        '* * * *',
        '* * * * *',
        '* * * * * *',
    ];

    public function __construct(
        private Log $log,
        private EntityManager $entityManager,
        private QueueUtil $queueUtil,
        private ScheduleUtil $scheduleUtil,
        private PreparatorFactory $preparatorFactory,
        private MetadataProvider $metadataProvider,
        private ConfigDataProvider $configDataProvider
    ) {}

    public function process(): void
    {
        $activeScheduledJobList = $this->scheduleUtil->getActiveScheduledJobList();
        $runningScheduledJobIdList = $this->queueUtil->getRunningScheduledJobIdList();

        foreach ($activeScheduledJobList as $scheduledJob) {
            try {
                $isRunning = in_array($scheduledJob->getId(), $runningScheduledJobIdList);

                $this->createJobsFromScheduledJob($scheduledJob, $isRunning);
            } catch (Throwable $e) {
                $id = $scheduledJob->getId();

                $this->log->error("Scheduled Job '$id': " . $e->getMessage());
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

        if ($jobName && $this->metadataProvider->isJobPreparable($jobName)) {
            $preparator = $this->preparatorFactory->create($jobName);

            $data = new PreparatorData($scheduledJob->getId(), $scheduledJob->getName() ?? $jobName);

            /** @var DateTimeImmutable $executeTimeObj */
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

        if ($scheduling === null) {
            return null;
        }

        $id = $scheduledJob->getId();

        $asSoonAsPossible = in_array($scheduling, $this->asSoonAsPossibleSchedulingList);

        if ($asSoonAsPossible) {
            return DateTimeUtil::getSystemNowString();
        }

        try {
            $cronExpression = CronExpression::factory($scheduling);
        } catch (Exception $e) {
            $this->log->error(
                "Scheduled Job '$id': Scheduling expression error: " .
                $e->getMessage() . '.');

            return null;
        }

        $timeZone = $this->configDataProvider->getTimeZone();

        try {
            $next = $cronExpression->getNextRunDate(timeZone: $timeZone)
                ->setTimezone(new DateTimeZone('UTC'));
        } catch (Exception) {
            $this->log->error("Scheduled Job '$id': Unsupported scheduling expression '$scheduling'.");

            return null;
        }

        return $next->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
    }
}
