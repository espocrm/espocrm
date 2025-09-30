<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Acl\Table;

use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Entities\Team;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\Role as RoleEntity;

class DefaultRoleListProvider implements RoleListProvider
{
    private const PARAM_BASELINE_ROLE_ID = 'baselineRoleId';

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private Config $config,
    ) {}

    /**
     * @return Role[]
     */
    public function get(): array
    {
        $roleList = [];

        $baselineRole = $this->getBaselineRole();

        if ($baselineRole) {
            $roleList[] = $baselineRole;
        }

        /** @var iterable<RoleEntity> $userRoleList */
        $userRoleList = $this->entityManager
            ->getRelation($this->user, User::LINK_ROLES)
            ->find();

        foreach ($userRoleList as $role) {
            $roleList[] = $role;
        }

        /** @var iterable<Team> $teamList */
        $teamList = $this->entityManager
            ->getRelation($this->user, Field::TEAMS)
            ->find();

        foreach ($teamList as $team) {
            /** @var iterable<RoleEntity> $teamRoleList */
            $teamRoleList = $this->entityManager
                ->getRelation($team, Team::LINK_ROLES)
                ->find();

            foreach ($teamRoleList as $role) {
                $roleList[] = $role;
            }
        }

        return array_map(
            fn (RoleEntity $role) => new RoleEntityWrapper($role),
            $roleList
        );
    }

    private function getBaselineRole(): ?RoleEntity
    {
        $roleId = $this->config->get(self::PARAM_BASELINE_ROLE_ID);

        if (!$roleId) {
            return null;
        }

        return $this->entityManager->getRDBRepositoryByClass(RoleEntity::class)->getById($roleId);
    }
}
