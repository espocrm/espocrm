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

namespace Espo\Acl;

use \Espo\Entities\User;
use \Espo\ORM\Entity;

class Email extends \Espo\Core\Acl\Base
{

    public function checkEntityRead(User $user, Entity $entity, $data)
    {
        if ($this->checkEntity($user, $entity, $data, 'read')) {
            return true;
        }

        if ($data === false) {
            return false;
        }
        if (is_array($data)) {
            if (empty($data['read']) || $data['read'] == 'no') {
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

    public function checkIsOwner(User $user, Entity $entity)
    {
        if ($entity->has('assignedUserId')) {
            if ($user->id === $entity->get('assignedUserId')) {
                return true;
            }
        }

        if ($user->id === $entity->get('createdById')) {
            return true;
        }

        return false;
    }

}

