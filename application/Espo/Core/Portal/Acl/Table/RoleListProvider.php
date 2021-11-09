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

namespace Espo\Core\Portal\Acl\Table;

use Espo\ORM\EntityManager;

use Espo\Entities\{
    User,
    Portal,
    PortalRole,
};

use Espo\Core\{
    Acl\Table\RoleListProvider as RoleListProviderInterface,
    Acl\Table\RoleEntityWrapper,
    Acl\Table\Role,
};

class RoleListProvider implements RoleListProviderInterface
{
    private $user;

    private $portal;

    private $entityManager;

    public function __construct(User $user, Portal $portal, EntityManager $entityManager)
    {
        $this->user = $user;
        $this->portal = $portal;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Role[]
     */
    public function get(): array
    {
        $roleList = [];

        /** @var iterable<PortalRole> */
        $userRoleList = $this->entityManager
            ->getRDBRepository('User')
            ->getRelation($this->user, 'portalRoles')
            ->find();

        foreach ($userRoleList as $role) {
            $roleList[] = $role;
        }

        /** @var iterable<PortalRole> */
        $portalRoleList = $this->entityManager
            ->getRDBRepository('Portal')
            ->getRelation($this->portal, 'portalRoles')
            ->find();

        foreach ($portalRoleList as $role) {
            $roleList[] = $role;
        }

        return array_map(
            function (PortalRole $role): RoleEntityWrapper {
                return new RoleEntityWrapper($role);
            },
            $roleList
        );
    }
}
