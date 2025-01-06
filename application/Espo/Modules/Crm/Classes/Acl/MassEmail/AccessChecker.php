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

namespace Espo\Modules\Crm\Classes\Acl\MassEmail;

use Espo\Core\Acl\AccessEntityCREDChecker;
use Espo\Core\Acl\DefaultAccessChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\Acl\Traits\DefaultAccessCheckerDependency;
use Espo\Core\AclManager;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\ORM\Entity;

/**
 * @implements AccessEntityCREDChecker<MassEmail>
 */
class AccessChecker implements AccessEntityCREDChecker
{
    use DefaultAccessCheckerDependency;

    public function __construct(
        DefaultAccessChecker $defaultAccessChecker,
        private AclManager $aclManager,
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
    }

    public function checkCreate(User $user, ScopeData $data): bool
    {
        return $this->checkEdit($user, $data);
    }

    public function checkDelete(User $user, ScopeData $data): bool
    {
        return $this->checkEdit($user, $data) || $this->defaultAccessChecker->checkDelete($user, $data);
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($this->defaultAccessChecker->checkEntityCreate($user, $entity, $data)) {
            return true;
        }

        $campaign = $entity->getCampaign();

        if ($campaign && $this->aclManager->checkEntityEdit($user, $campaign)) {
            return true;
        }

        return false;
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($this->defaultAccessChecker->checkEntityDelete($user, $entity, $data)) {
            return true;
        }

        $campaign = $entity->getCampaign();

        if ($campaign && $this->aclManager->checkEntityEdit($user, $campaign)) {
            return true;
        }

        return false;
    }
}
