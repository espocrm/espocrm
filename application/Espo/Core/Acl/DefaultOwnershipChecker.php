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

use Espo\Core\Name\Field;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\User;

/**
 * A default implementation for ownership checking.
 *
 * @implements OwnershipOwnChecker<CoreEntity>
 * @implements OwnershipTeamChecker<CoreEntity>
 * @implements OwnershipSharedChecker<CoreEntity>
 */
class DefaultOwnershipChecker implements OwnershipOwnChecker, OwnershipTeamChecker, OwnershipSharedChecker
{
    private const ATTR_CREATED_BY_ID = Field::CREATED_BY . 'Id';
    private const ATTR_ASSIGNED_USER_ID = Field::ASSIGNED_USER . 'Id';
    private const ATTR_ASSIGNED_TEAMS_IDS = Field::TEAMS . 'Ids';
    private const FIELD_TEAMS = Field::TEAMS;
    private const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;
    private const FIELD_COLLABORATORS = Field::COLLABORATORS;

    public function checkOwn(User $user, Entity $entity): bool
    {
        if ($entity instanceof CoreEntity && $entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            if ($entity->hasLinkMultipleId(self::FIELD_ASSIGNED_USERS, $user->getId())) {
                return true;
            }

            return false;
        }

        if ($entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            if (
                $entity->has(self::ATTR_ASSIGNED_USER_ID) &&
                $user->getId() === $entity->get(self::ATTR_ASSIGNED_USER_ID)
            ) {
                return true;
            }

            return false;
        }

        if ($entity->hasAttribute(self::ATTR_CREATED_BY_ID)) {
            if (
                $entity->has(self::ATTR_CREATED_BY_ID) &&
                $user->getId() === $entity->get(self::ATTR_CREATED_BY_ID)
            ) {
                return true;
            }
        }

        return false;
    }

    public function checkTeam(User $user, Entity $entity): bool
    {
        if (!$entity instanceof CoreEntity) {
            return false;
        }

        $userTeamIdList = $user->getLinkMultipleIdList(self::FIELD_TEAMS);

        if (
            !$entity->hasRelation(self::FIELD_TEAMS) ||
            !$entity->hasAttribute(self::ATTR_ASSIGNED_TEAMS_IDS)
        ) {
            return false;
        }

        $entityTeamIdList = $entity->getLinkMultipleIdList(self::FIELD_TEAMS);

        if (empty($entityTeamIdList)) {
            return false;
        }

        foreach ($userTeamIdList as $id) {
            if (in_array($id, $entityTeamIdList)) {
                return true;
            }
        }

        return false;
    }

    public function checkShared(User $user, Entity $entity, string $action): bool
    {
        if (!$entity instanceof CoreEntity) {
            return false;
        }

        if ($action !== Table::ACTION_READ && $action !== Table::ACTION_STREAM) {
            return false;
        }

        if (
            !$entity->hasRelation(self::FIELD_COLLABORATORS) ||
            !$entity->hasLinkMultipleField(self::FIELD_COLLABORATORS)
        ) {
            return false;
        }

        return in_array($user->getId(), $entity->getLinkMultipleIdList(self::FIELD_COLLABORATORS));
    }
}
