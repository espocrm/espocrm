<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Job\Preparator\Preparators;

use Espo\Core\Field\DateTime;
use Espo\Core\Job\Job\Data as JobData;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Job\Job\Status;
use Espo\Core\Job\Preparator;
use Espo\Core\Job\Preparator\Data;
use Espo\Entities\ScheduledJob;
use Espo\ORM\EntityManager;
use Espo\Entities\Job;
use Espo\ORM\Name\Attribute;
use DateTimeImmutable;

/**
 * @noinspection PhpUnused
 */
class ProcessJobGroupPreparator implements Preparator
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function prepare(Data $data, DateTimeImmutable $executeTime): void
    {
        $groupList = [];

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select(Job::FIELD_GROUP)
            ->from(Job::ENTITY_TYPE)
            ->where([
                Job::FIELD_STATUS => Status::PENDING,
                Job::FIELD_QUEUE => null,
                Job::FIELD_GROUP . '!=' => null,
                Job::FIELD_EXECUTION_TIME . '<=' => $executeTime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->group(Job::FIELD_GROUP)
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($query);

        while ($row = $sth->fetch()) {
            $group = $row[Job::FIELD_GROUP];

            if ($group === null) {
                continue;
            }

            $groupList[] = $group;
        }

        if (!count($groupList)) {
            return;
        }

        foreach ($groupList as $group) {
            $this->processGroup($group, $data, $executeTime);
        }
    }

    private function processGroup(string $group, Data $data, DateTimeImmutable $executeTime): void
    {
        $this->entityManager->getTransactionManager()
            ->run(fn () => $this->processGroupInternal($group, $data, $executeTime));
    }

    private function processGroupInternal(string $group, Data $data, DateTimeImmutable $executeTime): void
    {
        $this->entityManager->getRDBRepositoryByClass(ScheduledJob::class)
            ->select(Attribute::ID)
            ->forUpdate()
            ->where([Attribute::ID => $data->getId()])
            ->findOne();

        $existingJob = $this->entityManager
            ->getRDBRepositoryByClass(Job::class)
            ->select(Attribute::ID)
            ->where([
                Job::FIELD_STATUS => [
                    Status::RUNNING,
                    Status::READY,
                    Status::PENDING,
                ],
                Job::ATTR_SCHEDULED_JOB_ID => $data->getId(),
                Job::FIELD_TARGET_GROUP => $group,
            ])
            ->findOne();

        if ($existingJob) {
            return;
        }

        $name = $data->getName() . ' :: ' . $group;

        $job = $this->entityManager->getRDBRepositoryByClass(Job::class)->getNew()
            ->setName($name)
            ->setScheduleJobId($data->getId())
            ->setExecuteTime(DateTime::fromDateTime($executeTime))
            ->setTargetGroup($group)
            ->setData(JobData::create(['group' => $group]));

        $this->entityManager->saveEntity($job);
    }
}
