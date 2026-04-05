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

namespace Espo\Classes\Acl\PipelineStage;

use Espo\Core\Acl\AccessEntityCREDChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\AclManager;
use Espo\Entities\PipelineStage;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * @implements AccessEntityCREDChecker<PipelineStage>
 */
class AccessChecker implements AccessEntityCREDChecker
{
    public function __construct(
        private AclManager $aclManager,
    ) {}

    public function check(User $user, ScopeData $data): bool
    {
        return $data->isTrue();
    }

    public function checkCreate(User $user, ScopeData $data): bool
    {
        return $user->isAdmin();
    }

    public function checkRead(User $user, ScopeData $data): bool
    {
        return $data->isTrue();
    }

    public function checkEdit(User $user, ScopeData $data): bool
    {
        return $user->isAdmin();
    }

    public function checkDelete(User $user, ScopeData $data): bool
    {
        return $user->isAdmin();
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        return $user->isAdmin();
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        return $user->isAdmin();
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        return $user->isAdmin();
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$data->isFalse()) {
            return false;
        }

        $pipeline = $entity->getPipeline();

        if (!$this->aclManager->checkScope($user, $pipeline->getTargetEntityType())) {
            return false;
        }

        if ($pipeline->isAvailableForAll()) {
            return true;
        }

        return array_intersect($user->getTeamIdList(), $pipeline->getTeams()->getIdList()) !== [];
    }
}
