<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

class AssignmentEmailNotification extends \Espo\Core\Hooks\Base
{
    public function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($options['silent']) || !empty($options['noNotifications'])) {
            return;
        }
        if (
            $this->getConfig()->get('assignmentEmailNotifications')
            &&
            (
                $entity->has('assignedUserId')
                ||
                $entity->hasLinkMultipleField('assignedUsers') && $entity->has('assignedUsersIds')
            )
            &&
            in_array($entity->getEntityType(), $this->getConfig()->get('assignmentEmailNotificationsEntityList', []))
        ) {
            if ($entity->has('assignedUsersIds')) {
                $userIdList = $entity->getLinkMultipleIdList('assignedUsers');
                $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds');
                if (!is_array($fetchedAssignedUserIdList)) {
                    $fetchedAssignedUserIdList = [];
                }

                foreach ($userIdList as $userId) {
                    if (in_array($userId, $fetchedAssignedUserIdList)) continue;
                    if (!$this->isNotSelfAssignment($entity, $userId)) continue;
                    $this->createJob($entity, $userId);
                }
            } else {
                $userId = $entity->get('assignedUserId');
                if (!empty($userId) && $entity->isAttributeChanged('assignedUserId') && $this->isNotSelfAssignment($entity, $userId)) {
                    $this->createJob($entity, $userId);
                }
            }
        }
    }

    protected function isNotSelfAssignment(Entity $entity, $assignedUserId)
    {
        if ($entity->hasAttribute('createdById') && $entity->hasAttribute('modifiedById')) {
            if ($entity->isNew()) {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('createdById');
            } else {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('modifiedById');
            }
        } else {
            $isNotSelfAssignment = $assignedUserId !== $this->getUser()->id;
        }
        return $isNotSelfAssignment;
    }

    protected function createJob(Entity $entity, $userId)
    {
        $job = $this->getEntityManager()->getEntity('Job');
        $job->set([
            'serviceName' => 'EmailNotification',
            'methodName' => 'notifyAboutAssignmentJob',
            'data' => [
                'userId' => $userId,
                'assignerUserId' => $this->getUser()->id,
                'entityId' => $entity->id,
                'entityType' => $entity->getEntityType()
            ],
            'executeTime' => date('Y-m-d H:i:s'),
            'queue' => 'e0'
        ]);
        $this->getEntityManager()->saveEntity($job);
    }
}
