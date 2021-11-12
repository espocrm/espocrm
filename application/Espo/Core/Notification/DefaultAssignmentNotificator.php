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

namespace Espo\Core\Notification;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Entities\User;
use Espo\Entities\Notification;

use Espo\Core\Notification\AssignmentNotificator\Params;

class DefaultAssignmentNotificator implements AssignmentNotificator
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var UserEnabledChecker
     */
    protected $userChecker;

    public function __construct(User $user, EntityManager $entityManager, UserEnabledChecker $userChecker)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->userChecker = $userChecker;
    }

    public function process(Entity $entity, Params $params): void
    {
        assert($entity instanceof CoreEntity);

        if ($entity->hasLinkMultipleField('assignedUsers')) {
            $userIdList = $entity->getLinkMultipleIdList('assignedUsers');
            $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds');

            if (!is_array($fetchedAssignedUserIdList)) {
                $fetchedAssignedUserIdList = [];
            }

            foreach ($userIdList as $userId) {
                if (in_array($userId, $fetchedAssignedUserIdList)) {
                    continue;
                }

                $this->processForUser($entity, $userId);
            }

            return;
        }

        if (!$entity->get('assignedUserId')) {
            return;
        }

        if (!$entity->isAttributeChanged('assignedUserId')) {
            return;
        }

        $assignedUserId = $entity->get('assignedUserId');

        $this->processForUser($entity, $assignedUserId);
    }

    protected function processForUser(Entity $entity, string $assignedUserId): void
    {
        if (!$this->userChecker->checkAssignment($entity->getEntityType(), $assignedUserId)) {
            return;
        }

        if ($entity->hasAttribute('createdById') && $entity->hasAttribute('modifiedById')) {
            if ($entity->isNew()) {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('createdById');
            }
            else {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('modifiedById');
            }
        }
        else {
            $isNotSelfAssignment = $assignedUserId !== $this->user->getId();
        }

        if (!$isNotSelfAssignment) {
            return;
        }

        $this->entityManager->createEntity(Notification::ENTITY_TYPE, [
            'type' => Notification::TYPE_ASSIGN,
            'userId' => $assignedUserId,
            'data' => [
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'entityName' => $entity->get('name'),
                'isNew' => $entity->isNew(),
                'userId' => $this->user->getId(),
                'userName' => $this->user->get('name'),
            ],
            'relatedType' => $entity->getEntityType(),
            'relatedId' => $entity->getId(),
        ]);
    }
}
