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

namespace Espo\Acl;

use \Espo\Entities\User as EntityUser;
use \Espo\ORM\Entity;

class Email extends \Espo\Core\Acl\Base
{
    protected $ownerUserIdAttribute = 'usersIds';

    public function checkEntityRead(EntityUser $user, Entity $entity, $data)
    {
        if ($this->checkEntity($user, $entity, $data, 'read')) {
            return true;
        }

        if ($data === false) {
            return false;
        }
        if (is_object($data)) {
            if ($data->read === false || $data->read === 'no') {
                return false;
            }
        }

        if (!$entity->has('usersIds')) {
            $entity->loadLinkMultipleField('users');
        }
        $userIdList = $entity->get('usersIds');
        if (is_array($userIdList) && in_array($user->id, $userIdList)) {
            return true;
        }
        return false;
    }

    public function checkIsOwner(EntityUser $user, Entity $entity)
    {
        if ($user->id === $entity->get('assignedUserId')) {
            return true;
        }

        if ($user->id === $entity->get('createdById')) {
            return true;
        }

        if ($entity->hasLinkMultipleId('assignedUsers', $user->id)) {
            return true;
        }

        return false;
    }

    public function checkEntityDelete(EntityUser $user, Entity $entity, $data)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($data === false) {
            return false;
        }

        if ($data->delete === 'own') {
            if ($user->id === $entity->get('assignedUserId')) {
                return true;
            }

            if ($user->id === $entity->get('createdById')) {
                return true;
            }

            $assignedUserIdList = $entity->getLinkMultipleIdList('assignedUsers');
            if (count($assignedUserIdList) === 1 && $entity->hasLinkMultipleId('assignedUsers', $user->id)) {
                return true;
            }
            return false;
        }

        if ($this->checkEntity($user, $entity, $data, 'delete')) {
            return true;
        }

        if ($data->edit !== 'no' || $data->create !== 'no') {
            if ($entity->get('createdById') === $user->id) {
                if ($entity->get('status') !== 'Sent' && $entity->get('status') !== 'Archived') {
                    return true;
                }
            }
        }

        return false;
    }
}
