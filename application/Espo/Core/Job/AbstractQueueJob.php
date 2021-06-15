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

use Espo\Core\Utils\DateTime;

use Espo\ORM\EntityManager;

use Espo\Entities\Job as JobEntity;

use DateTimeImmutable;
use RuntimeException;

abstract class AbstractQueueJob implements JobPreperable
{
    protected $queue = null;

    private $jobManager;

    private $portionNumberProvider;

    private $entityManager;

    private const SHIFT_PERIOD = '5 seconds';

    public function __construct(
        JobManager $jobManager,
        QueuePortionNumberProvider $portionNumberProvider,
        EntityManager $entityManager
    ) {
        $this->jobManager = $jobManager;
        $this->portionNumberProvider = $portionNumberProvider;
        $this->entityManager = $entityManager;
    }

    public function run(JobData $data): void
    {
        if (!$this->queue) {
            throw new RuntimeException("No queue name.");
        }

        $limit = $this->portionNumberProvider->get($this->queue);

        $group = $data->get('group');

        $this->jobManager->processQueue($this->queue, $group, $limit);
    }

    public function prepare(ScheduledJobData $data, DateTimeImmutable $executeTime): void
    {
        $groupList = [];

        $shiftPeriod = self::SHIFT_PERIOD;

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select('group')
            ->from(JobEntity::ENTITY_TYPE)
            ->where([
                'status' => JobStatus::PENDING,
                'queue' => $this->queue,
                'executeTime<=' => $executeTime
                    ->modify($shiftPeriod)
                    ->format(DateTime::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->groupBy('group')
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($query);

        while ($row = $sth->fetch()) {
            $groupList[] = $row['group'] ?? null;
        }

        if (!count($groupList)) {
            return;
        }

        foreach ($groupList as $group) {
            $existingJob = $this->entityManager
                ->getRDBRepository(JobEntity::ENTITY_TYPE)
                ->select('id')
                ->where([
                    'scheduledJobId' => $data->getId(),
                    'targetGroup' => $group,
                    'status' => [
                        JobStatus::RUNNING,
                        JobStatus::READY,
                        JobStatus::PENDING,
                    ],
                ])
                ->findOne();

            if ($existingJob) {
                continue;
            }

            $name = $data->getName();

            if ($group) {
                $name .= ' :: ' . $group;
            }

            $this->entityManager->createEntity(JobEntity::ENTITY_TYPE, [
                'scheduledJobId' => $data->getId(),
                'executeTime' => $executeTime->format(DateTime::SYSTEM_DATE_TIME_FORMAT),
                'name' => $data->getName(),
                'data' => [
                    'group' => $group,
                ],
                'targetGroup' => $group,
            ]);
        }
    }
}
