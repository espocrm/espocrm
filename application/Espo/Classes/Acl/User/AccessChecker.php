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

namespace Espo\Classes\Acl\User;

use Espo\Entities\User;

use Espo\ORM\Entity;

use Espo\Core\{
    Acl\Table,
    Acl\ScopeData,
    Acl\DefaultAccessChecker,
    Acl\AccessEntityCREDSChecker,
    Acl\Traits\DefaultAccessCheckerDependency,
    AclManager,
};

/**
 * @implements AccessEntityCREDSChecker<User>
 */
class AccessChecker implements AccessEntityCREDSChecker
{
    use DefaultAccessCheckerDependency;

    private $defaultAccessChecker;

    private $aclManager;

    public function __construct(DefaultAccessChecker $defaultAccessChecker, AclManager $aclManager)
    {
        $this->defaultAccessChecker = $defaultAccessChecker;
        $this->aclManager = $aclManager;
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        /** @var User $entity */

        if ($entity->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $this->defaultAccessChecker->checkEntityCreate($user, $entity, $data);
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var User $entity */

        if ($entity->isPortal()) {
            if ($this->aclManager->getPermissionLevel($user, 'portal') === Table::LEVEL_YES) {
                return true;
            }

            return false;
        }

        if ($entity->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $this->defaultAccessChecker->checkEntityRead($user, $entity, $data);
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var User $entity */

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

        return $this->defaultAccessChecker->checkEntityEdit($user, $entity, $data);
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var User $entity */

        if (!$user->isAdmin()) {
            return false;
        }

        if ($entity->isSystem()) {
            return false;
        }

        if ($entity->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $this->defaultAccessChecker->checkEntityDelete($user, $entity, $data);
    }

    public function checkEntityStream(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var User $entity */

        return $this->aclManager->checkUserPermission($user, $entity, 'user');
    }
}
