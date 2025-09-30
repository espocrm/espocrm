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

namespace Espo\Classes\Acl\Email\LinkCheckers;

use Espo\Core\Acl\LinkChecker;
use Espo\Core\AclManager;
use Espo\Entities\Email;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * @implements LinkChecker<Email, Team>
 * @noinspection PhpUnused
 */
class TeamsLinkChecker implements LinkChecker
{
    public function __construct(
        private AclManager $aclManager
    ) {}

    public function check(User $user, Entity $entity, Entity $foreignEntity): bool
    {
        if ($this->aclManager->checkEntityRead($user, $foreignEntity)) {
            return true;
        }

        $replied = $entity->getReplied();

        if (!$replied) {
            return false;
        }

        if ($replied->getTeams()->hasId($foreignEntity->getId())) {
            return true;
        }

        return false;
    }
}
