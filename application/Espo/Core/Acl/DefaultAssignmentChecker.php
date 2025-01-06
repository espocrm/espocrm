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

namespace Espo\Core\Acl;

use Espo\Core\Acl\AssignmentChecker\Helper;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Entity;
use Espo\Entities\User;

/**
 * @implements AssignmentChecker<CoreEntity>
 */
class DefaultAssignmentChecker implements AssignmentChecker
{
    protected const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;
    private const FIELD_COLLABORATORS = Field::COLLABORATORS;

    public function __construct(
        private Helper $helper,
    ) {}

    public function check(User $user, Entity $entity): bool
    {
        if (!$this->isPermittedAssignedUser($user, $entity)) {
            return false;
        }

        if (!$this->isPermittedTeams($user, $entity)) {
            return false;
        }

        if ($this->helper->hasAssignedUsersField($entity->getEntityType())) {
            if (!$this->isPermittedAssignedUsers($user, $entity)) {
                return false;
            }
        }

        if ($this->helper->hasCollaboratorsField($entity->getEntityType())) {
            if (!$this->helper->checkUsers($user, $entity, self::FIELD_COLLABORATORS)) {
                return false;
            }
        }

        return true;
    }

    protected function isPermittedAssignedUser(User $user, Entity $entity): bool
    {
        return $this->helper->checkAssignedUser($user, $entity);
    }

    protected function isPermittedTeams(User $user, Entity $entity): bool
    {
        return $this->helper->checkTeams($user, $entity);
    }

    /**
     * Left for backward compatibility.
     */
    protected function isPermittedAssignedUsers(User $user, Entity $entity): bool
    {
        return $this->helper->checkUsers($user, $entity, self::FIELD_ASSIGNED_USERS);
    }
}
