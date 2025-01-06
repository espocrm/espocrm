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

use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\Entities\ScheduledJob as ScheduledJobEntity;
use Espo\Entities\ScheduledJobLogRecord as ScheduledJobLogRecordEntity;
use Espo\ORM\Name\Attribute;

class ScheduleUtil
{
    public function __construct(private EntityManager $entityManager)
    {}

    /**
     * Get active scheduled job list.
     *
     * @return Collection<ScheduledJobEntity>
     */
    public function getActiveScheduledJobList(): Collection
    {
        /** @var Collection<ScheduledJobEntity> $collection */
        $collection = $this->entityManager
            ->getRDBRepository(ScheduledJobEntity::ENTITY_TYPE)
            ->select([
                Attribute::ID,
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
        $scheduledJob = $this->entityManager->getEntityById(ScheduledJobEntity::ENTITY_TYPE, $scheduledJobId);

        if (!$scheduledJob) {
            return;
        }

        $scheduledJob->set('lastRun', $runTime);

        $this->entityManager->saveEntity($scheduledJob, [SaveOption::SILENT => true]);

        $scheduledJobLog = $this->entityManager->getNewEntity(ScheduledJobLogRecordEntity::ENTITY_TYPE);

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
