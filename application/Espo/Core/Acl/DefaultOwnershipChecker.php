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

namespace Espo\Core\Acl;

use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\User;

/**
 * A default implementation for ownership checking.
 */
class DefaultOwnershipChecker implements OwnershipOwnChecker, OwnershipTeamChecker
{
    private const ATTR_CREATED_BY_ID = 'createdById';

    private const ATTR_ASSIGNED_USER_ID = 'assignedUserId';

    private const ATTR_ASSIGNED_TEAMS_IDS = 'teamsIds';

    private const FIELD_TEAMS = 'teams';

    private const FIELD_ASSIGNED_USERS = 'assignedUsers';

    public function checkOwn(User $user, Entity $entity): bool
    {
        if ($entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            if (
                $entity->has(self::ATTR_ASSIGNED_USER_ID) &&
                $user->getId() === $entity->get(self::ATTR_ASSIGNED_USER_ID)
            ) {
                return true;
            }
        }
        else if ($entity->hasAttribute(self::ATTR_CREATED_BY_ID)) {
            if (
                $entity->has(self::ATTR_CREATED_BY_ID) &&
                $user->getId() === $entity->get(self::ATTR_CREATED_BY_ID)
            ) {
                return true;
            }
        }

        if ($entity instanceof CoreEntity && $entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            if ($entity->hasLinkMultipleId(self::FIELD_ASSIGNED_USERS, $user->getId())) {
                return true;
            }
        }

        return false;
    }

    public function checkTeam(User $user, Entity $entity): bool
    {
        assert($entity instanceof CoreEntity);

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
}
