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

namespace Espo\Core\Job\JobScheduler\Creators;

use Espo\Core\Field\DateTime;
use Espo\Core\Job\JobScheduler;
use Espo\Core\Job\JobSchedulerCreator;
use Espo\Entities\Job;
use Espo\ORM\EntityManager;

class EntityJobSchedulerCreator implements JobSchedulerCreator
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function create(JobScheduler\Data $data): void
    {
        $time = $data->time ?? DateTime::createNow();

        $this->entityManager->createEntity(Job::ENTITY_TYPE, [
            'name' => $data->className,
            'className' => $data->className,
            'queue' => $data->queue,
            'group' => $data->group,
            'targetType' => $data->data->getTargetType(),
            'targetId' => $data->data->getTargetId(),
            'data' => $data->data->getRaw(),
            'executeTime' => $time->toString(),
        ]);
    }
}
