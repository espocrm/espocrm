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

namespace Espo\Core\Job\Preparator;

use Espo\Core\Job\Job\Status;
use Espo\Core\Utils\DateTime;
use Espo\Entities\Job;
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
        $running = $this->entityManager
            ->getRDBRepository(Job::ENTITY_TYPE)
            ->select(Attribute::ID)
            ->where([
                'scheduledJobId' => $data->getId(),
                'status' => [
                    Status::RUNNING,
                    Status::READY,
                ],
                'targetType' => $entity->getEntityType(),
                'targetId' => $entity->getId(),
            ])
            ->findOne();

        if ($running) {
            return;
        }

        $countPending = $this->entityManager
            ->getRDBRepository(Job::ENTITY_TYPE)
            ->where([
                'scheduledJobId' => $data->getId(),
                'status' => Status::PENDING,
                'targetType' => $entity->getEntityType(),
                'targetId' => $entity->getId(),
            ])
            ->count();

        if ($countPending > 1) {
            return;
        }

        $job = $this->entityManager->getNewEntity(Job::ENTITY_TYPE);

        $job->set([
            'name' => $data->getName(),
            'scheduledJobId' => $data->getId(),
            'executeTime' => $executeTime->format(DateTime::SYSTEM_DATE_TIME_FORMAT),
            'targetType' => $entity->getEntityType(),
            'targetId' => $entity->getId(),
        ]);

        $this->entityManager->saveEntity($job);
    }
}
