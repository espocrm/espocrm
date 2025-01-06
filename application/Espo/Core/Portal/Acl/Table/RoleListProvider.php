<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Portal\Acl\Table;

use Espo\ORM\EntityManager;

use Espo\Entities\Portal;
use Espo\Entities\PortalRole;
use Espo\Entities\User;
use Espo\Core\Acl\Table\Role;
use Espo\Core\Acl\Table\RoleEntityWrapper;
use Espo\Core\Acl\Table\RoleListProvider as RoleListProviderInterface;

class RoleListProvider implements RoleListProviderInterface
{
    public function __construct(
        private User $user,
        private Portal $portal,
        private EntityManager $entityManager
    ) {}

    /**
     * @return Role[]
     */
    public function get(): array
    {
        $roleList = [];

        /** @var iterable<PortalRole> $userRoleList */
        $userRoleList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->getRelation($this->user, 'portalRoles')
            ->find();

        foreach ($userRoleList as $role) {
            $roleList[] = $role;
        }

        /** @var iterable<PortalRole> $portalRoleList */
        $portalRoleList = $this->entityManager
            ->getRDBRepository(Portal::ENTITY_TYPE)
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
