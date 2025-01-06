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

namespace Espo\Tools\EmailNotification;

use Espo\Core\Name\Field;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Config;
use Espo\Core\ApplicationState;
use Espo\Core\Job\QueueName;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Tools\EmailNotification\Jobs\NotifyAboutAssignment;

class HookProcessor
{
    public function __construct(
        private Config $config,
        private ApplicationState $applicationState,
        private JobSchedulerFactory $jobSchedulerFactory
    ) {}

    public function afterSave(Entity $entity): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        if (!$this->checkToProcess($entity)) {
            return;
        }

        if ($entity->has('assignedUsersIds')) {
            $this->processMultiple($entity);

            return;
        }

        $userId = $entity->get('assignedUserId');

        if (
            !$userId ||
            !$entity->isAttributeChanged('assignedUserId') ||
            !$this->isNotSelfAssignment($entity, $userId)
        ) {
            return;
        }

        $this->createJob($entity, $userId);
    }

    private function processMultiple(CoreEntity $entity): void
    {
        $userIdList = $entity->getLinkMultipleIdList(Field::ASSIGNED_USERS);
        $fetchedAssignedUserIdList = $entity->getFetched(Field::ASSIGNED_USERS . 'Ids') ?? [];

        foreach ($userIdList as $userId) {
            if (
                in_array($userId, $fetchedAssignedUserIdList) ||
                !$this->isNotSelfAssignment($entity, $userId)
            ) {
                continue;
            }

            $this->createJob($entity, $userId);
        }
    }

    private function checkToProcess(CoreEntity $entity): bool
    {
        if (!$this->config->get('assignmentEmailNotifications')) {
            return false;
        }

        $hasAssignedUserField =
            $entity->has('assignedUserId') ||
            $entity->hasLinkMultipleField(Field::ASSIGNED_USERS) &&
            $entity->has('assignedUsersIds');

        if (!$hasAssignedUserField) {
            return false;
        }

        return in_array(
            $entity->getEntityType(),
            $this->config->get('assignmentEmailNotificationsEntityList') ?? []
        );
    }

    private function isNotSelfAssignment(Entity $entity, string $assignedUserId): bool
    {
        if ($entity->hasAttribute('createdById') && $entity->hasAttribute('modifiedById')) {
            if ($entity->isNew()) {
                return $assignedUserId !== $entity->get('createdById');
            }

            return $assignedUserId !== $entity->get('modifiedById');
        }

        return $assignedUserId !== $this->applicationState->getUserId();
    }

    private function createJob(Entity $entity, string $userId): void
    {
        $this->jobSchedulerFactory
            ->create()
            ->setClassName(NotifyAboutAssignment::class)
            ->setQueue(QueueName::E0)
            ->setData([
                'userId' => $userId,
                'assignerUserId' => $this->applicationState->getUserId(),
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->schedule();
    }
}
