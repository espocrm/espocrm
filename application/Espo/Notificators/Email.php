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

namespace Espo\Notificators;

use \Espo\ORM\Entity;

class Email extends \Espo\Core\Notificators\Base
{
    const DAYS_THRESHOLD = 2;

    protected function init()
    {
        $this->addDependency('serviceFactory');
        $this->addDependency('aclManager');
    }

    private $streamService = null;

    protected function getAclManager()
    {
        return $this->getInjection('aclManager');
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getInjection('serviceFactory')->create('Stream');
        }
        return $this->streamService;
    }

    public function process(Entity $entity, array $options = [])
    {
        if (!in_array($entity->get('status'), ['Archived', 'Sent', 'Being Imported'])) {
            return;
        }

        if (!empty($options['isJustSent'])) {
            $previousUserIdList = [];
        } else {
            $previousUserIdList = $entity->getFetched('usersIds');
            if (!is_array($previousUserIdList)) {
                $previousUserIdList = [];
            }
        }

        $dateSent = $entity->get('dateSent');
        if (!$dateSent) return;

        $dt = null;
        try {
            $dt = new \DateTime($dateSent);
        } catch (\Exception $e) {}
        if (!$dt) return;

        if ($dt->diff(new \DateTime())->days > self::DAYS_THRESHOLD) return;

        $emailUserIdList = $entity->get('usersIds');

        if (is_null($emailUserIdList) || !is_array($emailUserIdList)) {
            return;
        }

        $userIdList = [];
        foreach ($emailUserIdList as $userId) {
            if (!in_array($userId, $userIdList) && !in_array($userId, $previousUserIdList) && $userId != $this->getUser()->id) {
                $userIdList[] = $userId;
            }
        }

        $data = [
            'emailId' => $entity->id,
            'emailName' => $entity->get('name'),
        ];

        if (!$entity->has('from')) {
            $this->getEntityManager()->getRepository('Email')->loadFromField($entity);
        }

        if (!$entity->has('to')) {
            $this->getEntityManager()->getRepository('Email')->loadToField($entity);
        }
        $person = null;

        $from = $entity->get('from');
        if ($from) {
            $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($from, null, ['User', 'Contact', 'Lead']);
            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->id;
            }
        }

        $userIdFrom = null;
        if ($person && $person->getEntityType() == 'User') {
            $userIdFrom = $person->id;
        }

        if (empty($data['personEntityId'])) {
            $data['fromString'] = \Espo\Services\Email::parseFromName($entity->get('fromString'));
            if (empty($data['fromString']) && $from) {
                $data['fromString'] = $from;
            }
        }

        $parent = null;
        if ($entity->get('parentId') && $entity->get('parentType')) {
            $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
        }
        $account = null;
        if ($entity->get('accountId')) {
            $account = $this->getEntityManager()->getEntity('Account', $entity->get('accountId'));
        }

        foreach ($userIdList as $userId) {
            if (!$userId) continue;
            if ($userIdFrom === $userId) continue;
            if ($entity->getLinkMultipleColumn('users', 'inTrash', $userId)) continue;

            if (!empty($options['isBeingImported']) || !empty($options['isJustSent'])) {
                $folderId = $entity->getLinkMultipleColumn('users', 'folderId', $userId);
                if ($folderId) {
                    if (
                        $this->getEntityManager()->getRepository('EmailFolder')->where([
                            'id' => $folderId,
                            'skipNotifications' => true
                        ])->count()
                    ) {
                        continue;
                    }
                }
            }

            $user = $this->getEntityManager()->getEntity('User', $userId);
            if (!$user) continue;
            if ($user->isPortal()) continue;
            if (!$this->getAclManager()->checkScope($user, 'Email')) {
                continue;
            }
            if ($entity->get('status') == 'Archived' || !empty($options['isBeingImported'])) {
                if ($parent) {
                    if ($this->getStreamService()->checkIsFollowed($parent, $userId)) {
                        continue;
                    }
                }
                if ($account) {
                    if ($this->getStreamService()->checkIsFollowed($account, $userId)) {
                        continue;
                    }
                }
            }
            if (
                $this->getEntityManager()->getRepository('Notification')->where([
                    'type' => 'EmailReceived',
                    'userId' => $userId,
                    'relatedId' => $entity->id,
                    'relatedType' => 'Email'
                ])->select(['id'])->findOne()
            ) continue;

            $notification = $this->getEntityManager()->getEntity('Notification');
            $notification->set([
                'type' => 'EmailReceived',
                'userId' => $userId,
                'data' => $data,
                'relatedId' => $entity->id,
                'relatedType' => 'Email'
            ]);
            $this->getEntityManager()->saveEntity($notification);
        }
    }
}
