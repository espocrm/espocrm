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

namespace Espo\Classes\AclPortal\Email;

use Espo\Entities\User;

use Espo\ORM\Entity;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\{
    Acl\Table,
    Acl\ScopeData,
    Acl\AccessEntityCREDSChecker,
    Portal\Acl\DefaultAccessChecker,
    Portal\Acl\Traits\DefaultAccessCheckerDependency,
};

class AccessChecker implements AccessEntityCREDSChecker
{
    use DefaultAccessCheckerDependency;

    private $defaultAccessChecker;

    public function __construct(
        DefaultAccessChecker $defaultAccessChecker
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($this->defaultAccessChecker->checkEntityRead($user, $entity, $data)) {
            return true;
        }

        if ($data->isFalse()) {
            return false;
        }

        if ($data->getRead() === Table::LEVEL_NO) {
            return false;
        }

        assert($entity instanceof CoreEntity);

        $userIdList = $entity->getLinkMultipleIdLIst('users');

        if (is_array($userIdList) && in_array($user->getId(), $userIdList)) {
            return true;
        }

        return false;
    }
}
