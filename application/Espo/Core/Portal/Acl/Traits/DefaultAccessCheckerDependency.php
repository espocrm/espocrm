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

namespace Espo\Core\Portal\Acl\Traits;

use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Core\Acl\ScopeData;
use Espo\Core\Portal\Acl\DefaultAccessChecker;

trait DefaultAccessCheckerDependency
{
    private DefaultAccessChecker $defaultAccessChecker;

    public function check(User $user, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->check($user, $data);
    }

    public function checkCreate(User $user, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkCreate($user, $data);
    }

    public function checkRead(User $user, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkRead($user, $data);
    }

    public function checkEdit(User $user, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkEdit($user, $data);
    }

    public function checkDelete(User $user, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkDelete($user, $data);
    }

    public function checkStream(User $user, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkStream($user, $data);
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkEntityCreate($user, $entity, $data);
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkEntityRead($user, $entity, $data);
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkEntityEdit($user, $entity, $data);
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkEntityDelete($user, $entity, $data);
    }

    public function checkEntityStream(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->defaultAccessChecker->checkEntityStream($user, $entity, $data);
    }
}
