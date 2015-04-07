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

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Email extends \Espo\Core\ORM\Repositories\RDB
{
    protected function prepareAddressess(Entity $entity, $type)
    {
        if (!$entity->has($type)) {
            return;
        }

        $eaRepositoty = $this->getEntityManager()->getRepository('EmailAddress');

        $address = $entity->get($type);
        $ids = [];
        if (!empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $arr = array_map(function ($e) {
                return trim($e);
            }, explode(';', $address));

            $ids = $eaRepositoty->getIds($arr);
            foreach ($ids as $id) {
                $this->setUsersIdsByEmailAddressId($entity, $id);
            }
        }
        $entity->set($type . 'EmailAddressesIds', $ids);
    }

    protected function setUsersIdsByEmailAddressId(Entity $entity, $emailAddressId)
    {
        $user = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($emailAddressId, 'User');
        if ($user) {
            $usersIds = $entity->get('usersIds');
            if (empty($usersIds)) {
                $usersIds = array();
            }
            if (!in_array($user->id, $usersIds)) {
                $usersIds[] = $user->id;
            }
            $entity->set('usersIds', $usersIds);
        }
    }

    protected function beforeSave(Entity $entity, array $options)
    {
        $eaRepositoty = $this->getEntityManager()->getRepository('EmailAddress');


        if ($entity->has('from') || $entity->has('to') || $entity->has('cc') || $entity->has('bcc')) {

            if (!$entity->has('usersIds')) {
                $entity->loadLinkMultipleField('users');
            }

            if ($entity->has('from')) {
                $from = trim($entity->get('from'));
                if (!empty($from)) {
                    $ids = $eaRepositoty->getIds(array($from));
                    if (!empty($ids)) {
                        $entity->set('fromEmailAddressId', $ids[0]);
                        $this->setUsersIdsByEmailAddressId($entity, $ids[0]);
                    }
                } else {
                    $entity->set('fromEmailAddressId', null);
                }
            }

            if ($entity->has('to')) {
                $this->prepareAddressess($entity, 'to');
            }
            if ($entity->has('cc')) {
                $this->prepareAddressess($entity, 'cc');
            }
            if ($entity->has('bcc')) {
                $this->prepareAddressess($entity, 'bcc');
            }

            $usersIds = $entity->get('usersIds');
            $assignedUserId = $entity->get('assignedUserId');

            if (!empty($assignedUserId) && !in_array($assignedUserId, $usersIds)) {
                $usersIds[] = $assignedUserId;
            }
            $entity->set('usersIds', $usersIds);

        }

        parent::beforeSave($entity, $options);


        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if (!empty($parentId) || !empty($parentType)) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if (!empty($parent)) {
                if ($parent->getEntityName() == 'Account') {
                    $accountId = $parent->id;
                } else if ($parent->has('accountId')) {
                    $accountId = $parent->get('accountId');
                }
                if (!empty($accountId)) {
                    $entity->set('accountId', $accountId);
                }
            }
        } else {
            // TODO find account by from address
        }
    }
}

