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

namespace Espo\Core\Job\Preparator\Preparators;

use Espo\Core\Utils\DateTime;
use Espo\Core\Job\Job\Status;
use Espo\Core\Job\Preparator;
use Espo\Core\Job\Preparator\Data;

use Espo\ORM\EntityManager;

use Espo\Entities\Job as JobEntity;

use DateTimeImmutable;
use Espo\ORM\Name\Attribute;

class ProcessJobGroupPreparator implements Preparator
{
    public function __construct(private EntityManager $entityManager)
    {}

    public function prepare(Data $data, DateTimeImmutable $executeTime): void
    {
        $groupList = [];

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select('group')
            ->from(JobEntity::ENTITY_TYPE)
            ->where([
                'status' => Status::PENDING,
                'queue' => null,
                'group!=' => null,
                'executeTime<=' => $executeTime->format(DateTime::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->group('group')
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($query);

        while ($row = $sth->fetch()) {
            $group = $row['group'];

            if ($group === null) {
                continue;
            }

            $groupList[] = $group;
        }

        if (!count($groupList)) {
            return;
        }

        foreach ($groupList as $group) {
            $existingJob = $this->entityManager
                ->getRDBRepository(JobEntity::ENTITY_TYPE)
                ->select(Attribute::ID)
                ->where([
                    'scheduledJobId' => $data->getId(),
                    'targetGroup' => $group,
                    'status' => [
                        Status::RUNNING,
                        Status::READY,
                        Status::PENDING,
                    ],
                ])
                ->findOne();

            if ($existingJob) {
                continue;
            }

            $name = $data->getName() . ' :: ' . $group;

            $this->entityManager->createEntity(JobEntity::ENTITY_TYPE, [
                'scheduledJobId' => $data->getId(),
                'executeTime' => $executeTime->format(DateTime::SYSTEM_DATE_TIME_FORMAT),
                'name' => $name,
                'data' => [
                    'group' => $group,
                ],
                'targetGroup' => $group,
            ]);
        }
    }
}
