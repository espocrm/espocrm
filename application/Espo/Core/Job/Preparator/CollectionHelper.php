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

namespace Espo\Core\Job\Preparator;

use Espo\Core\Field\DateTime;
use Espo\Core\Job\Job\Status;
use Espo\Entities\Job;
use Espo\Entities\ScheduledJob;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use DateTimeImmutable;
use Espo\ORM\Name\Attribute;

/**
 * Creates jobs for each entity of a collection.
 * To be used by Preparator implementations.
 *
 * @template TEntity of Entity
 */
class CollectionHelper
{
    public function __construct(private EntityManager $entityManager)
    {}

    /**
     * @param Collection<TEntity> $collection
     */
    public function prepare(Collection $collection, Data $data, DateTimeImmutable $executeTime): void
    {
        foreach ($collection as $entity) {
            $this->prepareItem($entity, $data, $executeTime);
        }
    }

    /**
     * @param TEntity $entity
     */
    private function prepareItem(Entity $entity, Data $data, DateTimeImmutable $executeTime): void
    {
        $this->entityManager->getTransactionManager()
            ->run(fn () => $this->prepareItemInternal($entity, $data, $executeTime));
    }

    /**
     * @param TEntity $entity
     */
    private function prepareItemInternal(Entity $entity, Data $data, DateTimeImmutable $executeTime): void
    {
        $this->entityManager->getRDBRepositoryByClass(ScheduledJob::class)
            ->select(Attribute::ID)
            ->forUpdate()
            ->where([Attribute::ID => $data->getId()])
            ->findOne();

        $running = $this->entityManager
            ->getRDBRepositoryByClass(Job::class)
            ->select(Attribute::ID)
            ->where([
                Job::FIELD_STATUS => [
                    Status::RUNNING,
                    Status::READY,
                ],
                Job::ATTR_SCHEDULED_JOB_ID => $data->getId(),
                Job::ATTR_TARGET_TYPE => $entity->getEntityType(),
                Job::ATTR_TARGET_ID => $entity->getId(),
            ])
            ->findOne();

        if ($running) {
            return;
        }

        $countPending = $this->entityManager
            ->getRDBRepositoryByClass(Job::class)
            ->where([
                Job::ATTR_SCHEDULED_JOB_ID => $data->getId(),
                Job::FIELD_STATUS => Status::PENDING,
                Job::ATTR_TARGET_TYPE => $entity->getEntityType(),
                Job::ATTR_TARGET_ID => $entity->getId(),
            ])
            ->count();

        if ($countPending > 1) {
            return;
        }

        $job = $this->entityManager->getRDBRepositoryByClass(Job::class)->getNew()
            ->setName($data->getName())
            ->setExecuteTime(DateTime::fromDateTime($executeTime))
            ->setTargetId($entity->getId())
            ->setTargetType($entity->getEntityType())
            ->setScheduleJobId($data->getId());

        $this->entityManager->saveEntity($job);
    }
}
