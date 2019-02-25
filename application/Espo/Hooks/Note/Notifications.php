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

namespace Espo\Hooks\Note;

use Espo\ORM\Entity;

class Notifications extends \Espo\Core\Hooks\Base
{
    protected $notificationService = null;

    public static $order = 14;

    protected function init()
    {
        $this->addDependency('serviceFactory');
        $this->addDependency('container');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function getInternalAclManager()
    {
        return $this->getInjection('container')->get('internalAclManager');
    }

    protected function getPortalAclManager()
    {
        return $this->getInjection('container')->get('portalAclManager');
    }

    protected function getMentionedUserIdList($entity)
    {
        $mentionedUserList = array();
        $data = $entity->get('data');
        if (($data instanceof \stdClass) && ($data->mentions instanceof \stdClass)) {
            $mentions = get_object_vars($data->mentions);
            foreach ($mentions as $d) {
                $mentionedUserList[] = $d->id;
            }
        }
        return $mentionedUserList;
    }

    protected function getSubscriberList($parentType, $parentId, $isInternal = false)
    {
        if (!$this->getMetadata()->get(['scopes', $parentType, 'stream'])) return [];

        $pdo = $this->getEntityManager()->getPDO();

        if (!$isInternal) {
            $sql = "
                SELECT user_id AS userId
                FROM subscription
                WHERE entity_id = " . $pdo->quote($parentId) . " AND entity_type = " . $pdo->quote($parentType) . "
            ";
        } else {
            $sql = "
                SELECT subscription.user_id AS userId
                FROM subscription
                JOIN user ON user.id = subscription.user_id
                WHERE
                    entity_id = " . $pdo->quote($parentId) . " AND entity_type = " . $pdo->quote($parentType) . " AND
                    user.type <> 'portal'
            ";
        }

        $userList = $this->getEntityManager()->getRepository('User')->where([
            'isActive' => true
        ])->select(['id', 'type'])->find([
            'customWhere' => "AND user.id IN (".$sql.")"
        ]);

        return $userList;
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew() || !empty($options['forceProcessNotifications'])) {
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

                    $level = $this->getInternalAclManager()->getLevel($user, $targetType, 'read');

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
                            if ($userId === $this->getUser()->id) continue;
                            if (in_array($userId, $notifyUserIdList)) continue;
                            $notifyUserIdList[] = $userId;
                        }
                    }
                } else if ($targetType === 'teams') {
                    $targetTeamIdList = $entity->get('teamsIds');
                    if (is_array($targetTeamIdList)) {
                        foreach ($targetTeamIdList as $teamId) {
                            $team = $this->getEntityManager()->getEntity('Team', $teamId);
                            if (!$team) continue;
                            $targetUserList = $this->getEntityManager()->getRepository('Team')->findRelated($team, 'users', array(
                                'whereClause' => array(
                                    'isActive' => true
                                )
                            ));
                            foreach ($targetUserList as $user) {
                                if ($user->id === $this->getUser()->id) continue;
                                if (in_array($user->id, $notifyUserIdList)) continue;
                                $notifyUserIdList[] = $user->id;
                            }
                        }
                    }
                } else if ($targetType === 'portals') {
                    $targetPortalIdList = $entity->get('portalsIds');
                    if (is_array($targetPortalIdList)) {
                        foreach ($targetPortalIdList as $portalId) {
                            $portal = $this->getEntityManager()->getEntity('Portal', $portalId);
                            if (!$portal) continue;
                            $targetUserList = $this->getEntityManager()->getRepository('Portal')->findRelated($portal, 'users', array(
                                'whereClause' => array(
                                    'isActive' => true
                                )
                            ));
                            foreach ($targetUserList as $user) {
                                if ($user->id === $this->getUser()->id) continue;
                                if (in_array($user->id, $notifyUserIdList)) continue;
                                $notifyUserIdList[] = $user->id;
                            }
                        }
                    }
                } else if ($targetType === 'all') {
                    $targetUserList = $this->getEntityManager()->getRepository('User')->find([
                        'whereClause' => [
                            'isActive' => true,
                            'type' => ['regular', 'admin']
                        ]
                    ]);
                    foreach ($targetUserList as $user) {
                        if ($user->id === $this->getUser()->id) continue;
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
                        $this->getEntityManager()->getRepository('Notification')->select(['id'])->where([
                            'type' => 'Note',
                            'relatedType' => 'Note',
                            'relatedId' => $entity->id,
                            'userId' => $userId
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
    }

    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->getServiceFactory()->create('Notification');
        }
        return $this->notificationService;
    }
}
