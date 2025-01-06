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

namespace Espo\Classes\RecordHooks\Note;

use Espo\Core\Acl;
use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\Repositories\User as UserRepository;

/**
 * @implements SaveHook<Note>
 */
class AssignmentCheck implements SaveHook
{
    public function __construct(
        private User $user,
        private Acl $acl,
        private EntityManager $entityManager
    ) {}

    public function process(Entity $entity): void
    {
        $targetType = $entity->getTargetType();

        if (!$targetType) {
            return;
        }

        $userTeamIdList = $this->user->getTeamIdList();

        $userIdList = $entity->getLinkMultipleIdList('users');
        $portalIdList = $entity->getLinkMultipleIdList('portals');
        $teamIdList = $entity->getLinkMultipleIdList(Field::TEAMS);

        /** @var iterable<User> $targetUserList */
        $targetUserList = [];

        if ($targetType === Note::TARGET_USERS) {
            /** @var iterable<User> $targetUserList */
            $targetUserList = $this->entityManager
                ->getRDBRepository(User::ENTITY_TYPE)
                ->select([Attribute::ID, 'type'])
                ->where([Attribute::ID => $userIdList])
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

        $messagePermission = $this->acl->getPermissionLevel(Permission::MESSAGE);

        if ($messagePermission === AclTable::LEVEL_NO) {
            if (
                $targetType !== Note::TARGET_SELF &&
                $targetType !== Note::TARGET_PORTALS &&
                !(
                    $targetType === Note::TARGET_USERS &&
                    count($userIdList) === 1 &&
                    $userIdList[0] === $this->user->getId()
                ) &&
                !(
                    $targetType === Note::TARGET_USERS && $allTargetUsersArePortal
                )
            ) {
                throw new Forbidden('Not permitted to post to anybody except self.');
            }
        }

        if ($targetType === Note::TARGET_TEAMS) {
            if (empty($teamIdList)) {
                throw new BadRequest("No team IDS.");
            }
        }

        if ($targetType === Note::TARGET_USERS) {
            if (empty($userIdList)) {
                throw new BadRequest("No user IDs.");
            }
        }

        if ($targetType === Note::TARGET_PORTALS) {
            if (empty($portalIdList)) {
                throw new BadRequest("No portal IDs.");
            }

            if ($this->acl->getPermissionLevel(Permission::PORTAL) !== AclTable::LEVEL_YES) {
                throw new Forbidden('Not permitted to post to portal users.');
            }
        }

        if (
            $targetType === Note::TARGET_USERS &&
            $this->acl->getPermissionLevel(Permission::PORTAL) !== AclTable::LEVEL_YES
        ) {
            if ($hasPortalTargetUser) {
                throw new Forbidden('Not permitted to post to portal users.');
            }
        }

        if ($messagePermission === AclTable::LEVEL_TEAM) {
            if ($targetType === Note::TARGET_ALL) {
                throw new Forbidden('Not permitted to post to all.');
            }
        }

        if (
            $messagePermission === AclTable::LEVEL_TEAM &&
            $targetType === Note::TARGET_TEAMS
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
            $targetType === Note::TARGET_USERS
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
        return $this->entityManager->getRepository(User::ENTITY_TYPE);
    }
}
