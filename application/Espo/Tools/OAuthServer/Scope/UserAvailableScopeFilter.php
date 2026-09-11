<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\OAuthServer\Scope;

use Espo\Core\Acl\Scope;
use Espo\Core\Utils\Acl\UserAclManagerProvider;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class UserAvailableScopeFilter
{
    public function __construct(
        private EntityManager $entityManager,
        private UserAclManagerProvider $aclManagerProvider,
    ) {}

    /**
     * @param string[] $scopes
     * @return string[]
     */
    public function filter(string $userId, array $scopes): array
    {
        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            return [];
        }

        $aclManager = $this->aclManagerProvider->get($user);

        $scopes = array_filter($scopes, function ($scope) use ($aclManager, $user) {
            if ($scope === Scope::GLOBAL) {
                return true;
            }

            return $aclManager->tryCheck($user, $scope);
        });

        return array_values($scopes);
    }
}
