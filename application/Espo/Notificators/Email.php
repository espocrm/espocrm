<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace Espo\Notificators;

use \Espo\ORM\Entity;

class Email extends \Espo\Core\Notificators\Base
{
    protected function init()
    {
        $this->addDependency('serviceFactory');
    }

    private $streamService = null;

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getInjection('serviceFactory')->create('Stream');
        }
        return $this->streamService;
    }

    public function process(Entity $entity)
    {
        if ($entity->get('status') != 'Archived' && $entity->get('status') != 'Sent') {
            return;
        }

        $previousUserIdList = $entity->getFetched('usersIds');
        if (!is_array($previousUserIdList)) {
            $previousUserIdList = [];
        }

        $emailUserIdList = $entity->get('usersIds');

        if (is_null($emailUserIdList) || !is_array($emailUserIdList)) {
            return;
        }

        $userIdList = [];
        foreach ($emailUserIdList as $userId) {
            if (!in_array($userId, $userIdList) && $userId != $this->getUser()->id) {
                $userIdList[] = $userId;
            }
        }

        $data = array(
            'emailId' => $entity->id,
            'emailName' => $entity->get('name'),
        );

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
            if ($userIdFrom == $userId) {
                continue;
            }
            if ($entity->get('status') == 'Archived') {
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
            $notification = $this->getEntityManager()->getEntity('Notification');
            $notification->set(array(
                'type' => 'EmailReceived',
                'userId' => $userId,
                'data' => $data
            ));
            $this->getEntityManager()->saveEntity($notification);
        }
    }

}

