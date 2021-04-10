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

namespace Espo\Acl;

use Espo\ORM\Entity;

use Espo\Entities\User as EntityUser;

use Espo\Core\{
    Acl\Acl,
    Acl\ScopeData,
    Acl\Table,
};

class User extends Acl
{
    public function checkIsOwner(EntityUser $user, Entity $entity)
    {
        return $user->getId() === $entity->getId();
    }

    public function checkEntityRead(EntityUser $user, Entity $entity, ScopeData $data): bool
    {
        if (!$user->isAdmin() && $entity->isPortal()) {
            if ($this->aclManager->get($user, 'portal') === Table::LEVEL_YES) {
                return true;
            }
        }

        return $this->checkEntity($user, $entity, $data, Table::ACTION_READ);
    }

    public function checkEntityCreate(EntityUser $user, Entity $entity, ScopeData $data): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        if ($entity->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $this->checkEntity($user, $entity, $data, Table::ACTION_CREATE);
    }

    public function checkEntityDelete(EntityUser $user, Entity $entity, ScopeData $data): bool
    {
        if ($entity->getId() === 'system') {
            return false;
        }

        if (!$user->isAdmin()) {
            return false;
        }

        if ($entity->isSystem()) {
            return false;
        }

        if ($entity->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return parent::checkEntityDelete($user, $entity, $data);
    }

    public function checkEntityEdit(EntityUser $user, Entity $entity, ScopeData $data): bool
    {
        if ($entity->id === 'system') {
            return false;
        }
        if ($entity->isSystem()) {
            return false;
        }

        if (!$user->isAdmin()) {
            if ($user->getId() !== $entity->getId()) {
                return false;
            }
        }

        if ($entity->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $this->checkEntity($user, $entity, $data, Table::ACTION_EDIT);
    }
}
