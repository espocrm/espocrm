<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
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

    protected function getSubscriberIdList($parentType, $parentId, $isInternal = false)
    {
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
                    user.is_portal_user = 0
            ";
        }
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $userIdList = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if ($this->getUser()->id != $row['userId']) {
                $userIdList[] = $row['userId'];
            }
        }
        return $userIdList;
    }

    public function afterSave(Entity $entity)
    {
        if ($entity->isNew()) {
            $parentType = $entity->get('parentType');
            $parentId = $entity->get('parentId');
            $superParentType = $entity->get('superParentType');
            $superParentId = $entity->get('superParentId');

            $userIdList = [];

            if ($parentType && $parentId) {
				$userIdList = array_merge($userIdList, $this->getSubscriberIdList($parentType, $parentId, $entity->get('isInternal')));
                if ($superParentType && $superParentId) {
                    $userIdList = array_merge($userIdList, $this->getSubscriberIdList($superParentType, $superParentId, $entity->get('isInternal')));
                }
            } else {
                $targetType = $entity->get('targetType');
                if ($targetType === 'users') {
                    $targetUserIdList = $entity->get('usersIds');
                    if (is_array($targetUserIdList)) {
                        foreach ($targetUserIdList as $userId) {
                            if ($userId === $this->getUser()->id) continue;
                            if (in_array($userId, $userIdList)) continue;
                            $userIdList[] = $userId;
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
                                if (in_array($user->id, $userIdList)) continue;
                                $userIdList[] = $user->id;
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
                                if (in_array($user->id, $userIdList)) continue;
                                $userIdList[] = $user->id;
                            }
                        }
                    }
                } else if ($targetType === 'all') {
                    $targetUserList = $this->getEntityManager()->getRepository('User')->find(array(
                        'whereClause' => array(
                            'isActive' => true,
                            'isPortalUser' => false
                        )
                    ));
                    foreach ($targetUserList as $user) {
                        if ($user->id === $this->getUser()->id) continue;
                        $userIdList[] = $user->id;
                    }
                }
            }

            $userIdList = array_unique($userIdList);

            foreach ($userIdList as $i => $userId) {
                if ($entity->isUserIdNotified($userId)) {
                    unset($userIdList[$i]);
                }
            }
            $userIdList = array_values($userIdList);

            if (!empty($userIdList)) {
            	$this->getNotificationService()->notifyAboutNote($userIdList, $entity);
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

