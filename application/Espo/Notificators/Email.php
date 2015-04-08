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
    public function process(Entity $entity)
    {
        if (!$entity->isNew()) {
            return;
        }

        $userIdList = [];
        if ($entity->has('assignedUserId') && $entity->get('assignedUserId')) {
            $assignedUserId = $entity->get('assignedUserId');
            if ($assignedUserId != $this->getUser()->id && $entity->isFieldChanged('assignedUserId')) {
                $userIdList[] = $assignedUserId;
            }
        }
        $emailUserIdList = $entity->get('usersIds');
        if (is_null($emailUserIdList)) {
            $entity->loadLinkMultipleField('from');
            $emailUserIdList = $entity->get('usersIds');
        }
        if (!is_array($emailUserIdList)) {
            $emailUserIdList = [];
        }
        foreach ($emailUserIdList as $userId) {
            if (!in_array($userId, $userIdList)) {
                $userIdList[] = $userId;
            }
        }

        $data = array(
            'emailId' => $entity->id,
            'emailName' => $entity->get('name'),
        );

        $from = $entity->get('from');
        if ($from) {
            $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($from);
            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->id;
            }
        }

        foreach ($userIdList as $userId) {
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

