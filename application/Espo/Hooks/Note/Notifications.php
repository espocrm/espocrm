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

namespace Espo\Hooks\Note;

use Espo\ORM\Entity;

use Espo\Core\{
    ServiceFactory,
    AclManager as InternalAclManager,
    Utils\Metadata,
    ORM\EntityManager,
};

use Espo\Entities\User;

class Notifications
{
    protected $notificationService = null;

    protected $streamService = null;

    public static $order = 14;

    protected $metadata;
    protected $entityManager;
    protected $serviceFactory;
    protected $user;
    protected $internalAclManager;

    public function __construct(
        Metadata $metadata,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        User $user,
        InternalAclManager $internalAclManager
    ) {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->user = $user;
        $this->internalAclManager = $internalAclManager;
    }

    protected function getMentionedUserIdList($entity)
    {
        $mentionedUserList = [];
        $data = $entity->get('data');
        if (($data instanceof \StdClass) && ($data->mentions instanceof \StdClass)) {
            $mentions = get_object_vars($data->mentions);
            foreach ($mentions as $d) {
                $mentionedUserList[] = $d->id;
            }
        }
        return $mentionedUserList;
    }

    protected function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false)
    {
        return $this->getStreamService()->getSubscriberList($parentType, $parentId, $isInternal);
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        if (!$entity->isNew() && empty($options['forceProcessNotifications'])) {
            return;
        }

        $parentType = $entity->get('parentType');
        $parentId = $entity->get('parentId');
        $superParentType = $entity->get('superParentType');
        $superParentId = $entity->get('superParentId');

        $notifyUserIdList = [];

        if ($parentType && $parentId) {
			$userList =  $this->getSubscriberList($parentType, $parentId, $entity->get('isInternal'));
            $userIdMetList = [];
            foreach ($userList as $user) {
                $userIdMetList[] = $user->id;
            }
            if ($superParentType && $superParentId) {
                $additionalUserList = $this->getSubscriberList($superParentType, $superParentId, $entity->get('isInternal'));
                foreach ($additionalUserList as $user) {
                    if ($user->isPortal()) continue;
                    if (in_array($user->id, $userIdMetList)) continue;
                    $userIdMetList[] = $user->id;
                    $userList[] = $user;
                }
            }

            if ($entity->get('relatedType')) {
                $targetType = $entity->get('relatedType');
            } else {
                $targetType = $parentType;
            }

            $skipAclCheck = false;
            if (!$entity->isAclProcessed()) {
                $skipAclCheck = true;
            } else {
                $teamIdList = $entity->getLinkMultipleIdList('teams');
                $userIdList = $entity->getLinkMultipleIdList('users');
            }

            foreach ($userList as $user) {
                if ($skipAclCheck) {
                    $notifyUserIdList[] = $user->id;
                    continue;
                }
                if ($user->isAdmin()) {
                    $notifyUserIdList[] = $user->id;
                    continue;
                }

                if ($user->isPortal()) {
                    if ($entity->get('relatedType')) {
                        continue;
                    } else {
                        $notifyUserIdList[] = $user->id;
                    }
                    continue;
                }

                $level = $this->internalAclManager->getLevel($user, $targetType, 'read');

                if ($level === 'all') {
                    $notifyUserIdList[] = $user->id;
                    continue;
                } else if ($level === 'team') {
                    if (in_array($user->id, $userIdList)) {
                        $notifyUserIdList[] = $user->id;
                        continue;
                    }

                    if (!empty($teamIdList)) {
                        $userTeamIdList = $user->getLinkMultipleIdList('teams');
                        foreach ($teamIdList as $teamId) {
                            if (in_array($teamId, $userTeamIdList)) {
                                $notifyUserIdList[] = $user->id;
                                break;
                            }
                        }
                    }
                    continue;
                } else if ($level === 'own') {
                    if (in_array($user->id, $userIdList)) {
                        $notifyUserIdList[] = $user->id;
                        continue;
                    }
                }
            }

        } else {
            $targetType = $entity->get('targetType');
            if ($targetType === 'users') {
                $targetUserIdList = $entity->get('usersIds');
                if (is_array($targetUserIdList)) {
                    foreach ($targetUserIdList as $userId) {
                        if ($userId === $this->user->id) continue;
                        if (in_array($userId, $notifyUserIdList)) continue;
                        $notifyUserIdList[] = $userId;
                    }
                }
            } else if ($targetType === 'teams') {
                $targetTeamIdList = $entity->get('teamsIds');
                if (is_array($targetTeamIdList)) {
                    foreach ($targetTeamIdList as $teamId) {
                        $team = $this->entityManager->getEntity('Team', $teamId);
                        if (!$team) continue;
                        $targetUserList = $this->entityManager->getRepository('Team')->findRelated($team, 'users', array(
                            'whereClause' => array(
                                'isActive' => true
                            )
                        ));
                        foreach ($targetUserList as $user) {
                            if ($user->id === $this->user->id) continue;
                            if (in_array($user->id, $notifyUserIdList)) continue;
                            $notifyUserIdList[] = $user->id;
                        }
                    }
                }
            } else if ($targetType === 'portals') {
                $targetPortalIdList = $entity->get('portalsIds');
                if (is_array($targetPortalIdList)) {
                    foreach ($targetPortalIdList as $portalId) {
                        $portal = $this->entityManager->getEntity('Portal', $portalId);
                        if (!$portal) continue;
                        $targetUserList = $this->entityManager->getRepository('Portal')->findRelated($portal, 'users', array(
                            'whereClause' => array(
                                'isActive' => true
                            )
                        ));
                        foreach ($targetUserList as $user) {
                            if ($user->id === $this->user->id) continue;
                            if (in_array($user->id, $notifyUserIdList)) continue;
                            $notifyUserIdList[] = $user->id;
                        }
                    }
                }
            } else if ($targetType === 'all') {
                $targetUserList = $this->entityManager->getRepository('User')->find([
                    'whereClause' => [
                        'isActive' => true,
                        'type' => ['regular', 'admin']
                    ]
                ]);
                foreach ($targetUserList as $user) {
                    if ($user->id === $this->user->id) continue;
                    $notifyUserIdList[] = $user->id;
                }
            }
        }

        $notifyUserIdList = array_unique($notifyUserIdList);

        foreach ($notifyUserIdList as $i => $userId) {
            if ($entity->isUserIdNotified($userId)) {
                unset($notifyUserIdList[$i]);
                continue;
            }
            if (!$entity->isNew()) {
                if (
                    $this->entityManager->getRepository('Notification')->select(['id'])->where([
                        'type' => 'Note',
                        'relatedType' => 'Note',
                        'relatedId' => $entity->id,
                        'userId' => $userId,
                    ])->findOne()
                ) {
                    unset($notifyUserIdList[$i]);
                    continue;
                }
            }
        }

        $notifyUserIdList = array_values($notifyUserIdList);

        if (!empty($notifyUserIdList)) {
            $this->getNotificationService()->notifyAboutNote($notifyUserIdList, $entity);
        }
    }

    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->serviceFactory->create('Notification');
        }

        return $this->notificationService;
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->serviceFactory->create('Stream');
        }

        return $this->streamService;
    }
}
