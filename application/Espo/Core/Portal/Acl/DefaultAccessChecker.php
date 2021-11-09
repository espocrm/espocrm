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

namespace Espo\Core\Portal\Acl;

use Espo\Entities\User;

use Espo\ORM\Entity;

use Espo\Core\{
    Portal\AclManager as PortalAclManager,
    Portal\Acl\AccessChecker\ScopeChecker,
    Portal\Acl\AccessChecker\ScopeCheckerData,
    Acl\ScopeData,
    Acl\AccessEntityCreateChecker,
    Acl\AccessEntityReadChecker,
    Acl\AccessEntityEditChecker,
    Acl\AccessEntityDeleteChecker,
    Acl\AccessEntityStreamChecker,
};

/**
 * A default implementation for access checking for portal.
 */
class DefaultAccessChecker implements

    AccessEntityCreateChecker,
    AccessEntityReadChecker,
    AccessEntityEditChecker,
    AccessEntityDeleteChecker,
    AccessEntityStreamChecker
{
    private $aclManager;

    private $scopeChecker;

    public function __construct(
        PortalAclManager $aclManager,
        ScopeChecker $scopeChecker
    ) {
        $this->aclManager = $aclManager;
        $this->scopeChecker = $scopeChecker;
    }

    private function checkEntity(User $user, Entity $entity, ScopeData $data, string $action): bool
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwnChecker(
                function () use ($user, $entity): bool {
                    return $this->aclManager->checkOwnershipOwn($user, $entity);
                }
            )
            ->setInAccountChecker(
                function () use ($user, $entity): bool {
                    return $this->aclManager->checkOwnershipAccount($user, $entity);
                }
            )
            ->setInContactChecker(
                function () use ($user, $entity): bool {
                    return $this->aclManager->checkOwnershipContact($user, $entity);
                }
            )
            ->build();

        return $this->scopeChecker->check($data, $action, $checkerData);
    }

    private function checkScope(User $user, ScopeData $data, ?string $action = null): bool
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwn(true)
            ->setInAccount(true)
            ->setInContact(true)
            ->build();

        return $this->scopeChecker->check($data, $action, $checkerData);
    }

    public function check(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data);
    }

    public function checkCreate(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_CREATE);
    }

    public function checkRead(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_READ);
    }

    public function checkEdit(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_EDIT);
    }

    public function checkDelete(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_DELETE);
    }

    public function checkStream(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_STREAM);
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_CREATE);
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_READ);
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_EDIT);
    }

    public function checkEntityStream(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_STREAM);
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_DELETE);
    }
}
