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

namespace Espo\Classes\Select\User\AccessControlFilters;

use Espo\Core\Acl\Permission;
use Espo\ORM\Query\SelectBuilder;
use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Select\AccessControl\Filter;
use Espo\Entities\User;

class Mandatory implements Filter
{
    public function __construct(
        private User $user,
        private AclManager $aclManager
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        if (!$this->user->isAdmin()) {
            $queryBuilder->where([
                'isActive' => true,
                'type!=' => User::TYPE_API,
            ]);
        }

        if ($this->aclManager->getPermissionLevel($this->user, Permission::PORTAL) !== Table::LEVEL_YES) {
            $queryBuilder->where([
                'OR' => [
                    'type!=' => User::TYPE_PORTAL,
                    'id' => $this->user->getId(),
                ]
            ]);
        }

        if (!$this->user->isSuperAdmin()) {
            $queryBuilder->where([
                'type!=' => User::TYPE_SUPER_ADMIN,
            ]);
        }

        $queryBuilder->where([
            'type!=' => User::TYPE_SYSTEM,
        ]);
    }
}
