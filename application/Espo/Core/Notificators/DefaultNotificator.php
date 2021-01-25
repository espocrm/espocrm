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

namespace Espo\Core\Notificators;

use Espo\ORM\Entity;

use Espo\Entities\User;

use Espo\Core\{
    ORM\EntityManager,
};

class DefaultNotificator implements Notificator
{
    private $userIdEnabledMap = [];

    protected $entityType;

    public static $order = 9;

    protected $user;
    protected $entityManager;

    public function __construct(User $user, EntityManager $entityManager)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
    }

    public function process(Entity $entity, array $options = [])
    {
        if ($entity->hasLinkMultipleField('assignedUsers')) {
            $userIdList = $entity->getLinkMultipleIdList('assignedUsers');
            $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds');
            if (!is_array($fetchedAssignedUserIdList)) {
                $fetchedAssignedUserIdList = [];
            }

            foreach ($userIdList as $userId) {
                if (in_array($userId, $fetchedAssignedUserIdList)) continue;
                $this->processForUser($entity, $userId);
            }
        } else {
            if (!$entity->get('assignedUserId')) return;
            if (!$entity->isAttributeChanged('assignedUserId')) return;
            $assignedUserId = $entity->get('assignedUserId');
            $this->processForUser($entity, $assignedUserId);
        }
    }

    protected function processForUser(Entity $entity, string $assignedUserId)
    {
        if (!$this->isNotificationsEnabledForUser($assignedUserId)) return;

        if ($entity->hasAttribute('createdById') && $entity->hasAttribute('modifiedById')) {
            if ($entity->isNew()) {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('createdById');
            } else {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('modifiedById');
            }
        } else {
            $isNotSelfAssignment = $assignedUserId !== $this->user->id;
        }
        if (!$isNotSelfAssignment) return;

        $notification = $this->entityManager->getEntity('Notification');
        $notification->set([
            'type' => 'Assign',
            'userId' => $assignedUserId,
            'data' => [
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->id,
                'entityName' => $entity->get('name'),
                'isNew' => $entity->isNew(),
                'userId' => $this->user->id,
                'userName' => $this->user->get('name'),
            ]
        ]);
        $this->entityManager->saveEntity($notification);
    }

    protected function isNotificationsEnabledForUser(string $userId) : bool
    {
        if (!array_key_exists($userId, $this->userIdEnabledMap)) {
            $preferences = $this->entityManager->getEntity('Preferences', $userId);
            $isEnabled = false;
            if ($preferences) {
                $isEnabled = true;
                $ignoreList = $preferences->get('assignmentNotificationsIgnoreEntityTypeList') ?? [];
                if (in_array($this->entityType, $ignoreList)) {
                    $isEnabled = false;
                }
            }
            $this->userIdEnabledMap[$userId] = $isEnabled;
        }

        return $this->userIdEnabledMap[$userId];
    }

    /**
     * For backward compatibility.
     * @todo Remove.
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * For backward compatibility.
     * @todo Remove.
     */
    protected function getUser()
    {
        return $this->user;
    }
}
