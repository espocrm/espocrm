<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Repositories\User as UserRepository;
use Espo\Core\Acl\Table as AclTable;
use Espo\Entities\Note as NoteEntity;
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;

/**
 * @extends Record<NoteEntity>
 */
class Note extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    protected function processAssignmentCheck(Entity $entity): void
    {
        /** @var NoteEntity $entity */

        if (!$entity->isNew()) {
            return;
        }

        $targetType = $entity->getTargetType();

        if (!$targetType) {
            return;
        }

        $userTeamIdList = $this->user->getTeamIdList();

        $userIdList = $entity->getLinkMultipleIdList('users');
        $portalIdList = $entity->getLinkMultipleIdList('portals');
        $teamIdList = $entity->getLinkMultipleIdList('teams');

        /** @var iterable<UserEntity> $targetUserList */
        $targetUserList = [];

        if ($targetType === NoteEntity::TARGET_USERS) {
            /** @var iterable<UserEntity> $targetUserList */
            $targetUserList = $this->entityManager
                ->getRDBRepository(UserEntity::ENTITY_TYPE)
                ->select(['id', 'type'])
                ->where([
                    'id' => $userIdList,
                ])
                ->find();
        }

        $hasPortalTargetUser = false;
        $allTargetUsersArePortal = true;

        foreach ($targetUserList as $user) {
            if (!$user->isPortal()) {
                $allTargetUsersArePortal = false;
            }

            if ($user->isPortal()) {
                $hasPortalTargetUser = true;
            }
        }

        $messagePermission = $this->acl->getPermissionLevel('message');

        if ($messagePermission === AclTable::LEVEL_NO) {
            if (
                $targetType !== NoteEntity::TARGET_SELF &&
                $targetType !== NoteEntity::TARGET_PORTALS &&
                !(
                    $targetType === NoteEntity::TARGET_USERS &&
                    count($userIdList) === 1 &&
                    $userIdList[0] === $this->user->getId()
                ) &&
                !(
                    $targetType === NoteEntity::TARGET_USERS && $allTargetUsersArePortal
                )
            ) {
                throw new Forbidden('Not permitted to post to anybody except self.');
            }
        }

        if ($targetType === NoteEntity::TARGET_TEAMS) {
            if (empty($teamIdList)) {
                throw new BadRequest("No team IDS.");
            }
        }

        if ($targetType === NoteEntity::TARGET_USERS) {
            if (empty($userIdList)) {
                throw new BadRequest("No user IDs.");
            }
        }

        if ($targetType === NoteEntity::TARGET_PORTALS) {
            if (empty($portalIdList)) {
                throw new BadRequest("No portal IDs.");
            }

            if ($this->acl->getPermissionLevel('portal') !== AclTable::LEVEL_YES) {
                throw new Forbidden('Not permitted to post to portal users.');
            }
        }

        if (
            $targetType === NoteEntity::TARGET_USERS &&
            $this->acl->getPermissionLevel('portal') !== AclTable::LEVEL_YES
        ) {
            if ($hasPortalTargetUser) {
                throw new Forbidden('Not permitted to post to portal users.');
            }
        }

        if ($messagePermission === AclTable::LEVEL_TEAM) {
            if ($targetType === NoteEntity::TARGET_ALL) {
                throw new Forbidden('Not permitted to post to all.');
            }
        }

        if (
            $messagePermission === AclTable::LEVEL_TEAM &&
            $targetType === NoteEntity::TARGET_TEAMS
        ) {
            if (empty($userTeamIdList)) {
                throw new Forbidden('Not permitted to post to foreign teams.');
            }

            foreach ($teamIdList as $teamId) {
                if (!in_array($teamId, $userTeamIdList)) {
                    throw new Forbidden("Not permitted to post to foreign teams.");
                }
            }
        }

        if (
            $messagePermission === AclTable::LEVEL_TEAM &&
            $targetType === NoteEntity::TARGET_USERS
        ) {
            if (empty($userTeamIdList)) {
                throw new Forbidden('Not permitted to post to users from foreign teams.');
            }

            foreach ($targetUserList as $user) {
                if ($user->getId() === $this->user->getId()) {
                    continue;
                }

                if ($user->isPortal()) {
                    continue;
                }

                $inTeam = $this->getUserRepository()->checkBelongsToAnyOfTeams($user->getId(), $userTeamIdList);

                if (!$inTeam) {
                    throw new Forbidden('Not permitted to post to users from foreign teams.');
                }
            }
        }
    }

    private function getUserRepository(): UserRepository
    {
        /** @var UserRepository */
        return $this->entityManager->getRepository(UserEntity::ENTITY_TYPE);
    }
}
