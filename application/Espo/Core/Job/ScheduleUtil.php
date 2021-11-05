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

use Espo\ORM\{
    Collection,
    EntityManager,
};

use Espo\Core\{
    Utils\DateTime as DateTimeUtil,
};

use Espo\Entities\{
    ScheduledJob as ScheduledJobEntity,
    ScheduledJobLogRecord as ScheduledJobLogRecordEntity,
};

class ScheduleUtil
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get active scheduled job list.
     *
     * @phpstan-return iterable<ScheduledJobEntity>&Collection
     */
    public function getActiveScheduledJobList(): Collection
    {
        /** @var iterable<ScheduledJobEntity>&Collection $collection */
        $collection = $this->entityManager
            ->getRDBRepository(ScheduledJobEntity::ENTITY_TYPE)
            ->select([
                'id',
                'scheduling',
                'job',
                'name',
            ])
            ->where([
                'status' => ScheduledJobEntity::STATUS_ACTIVE,
            ])
            ->find();

        return $collection;
    }

    /**
     * Add record to ScheduledJobLogRecord about executed job.
     */
    public function addLogRecord(
        string $scheduledJobId,
        string $status,
        ?string $runTime = null,
        ?string $targetId = null,
        ?string $targetType = null
    ): void {

        if (!isset($runTime)) {
            $runTime = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        /** @var ScheduledJobEntity|null $scheduledJob */
        $scheduledJob = $this->entityManager->getEntity(ScheduledJobEntity::ENTITY_TYPE, $scheduledJobId);

        if (!$scheduledJob) {
            return;
        }

        $scheduledJob->set('lastRun', $runTime);

        $this->entityManager->saveEntity($scheduledJob, ['silent' => true]);

        $scheduledJobLog = $this->entityManager->getEntity(ScheduledJobLogRecordEntity::ENTITY_TYPE);

        $scheduledJobLog->set([
            'scheduledJobId' => $scheduledJobId,
            'name' => $scheduledJob->getName(),
            'status' => $status,
            'executionTime' => $runTime,
            'targetId' => $targetId,
            'targetType' => $targetType,
        ]);

        $this->entityManager->saveEntity($scheduledJobLog);
    }
}