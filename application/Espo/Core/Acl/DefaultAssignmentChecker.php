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

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Repositories\User as UserRepository;

use Espo\ORM\{
    Entity,
    EntityManager,
    Defs,
};

use Espo\Entities\User;

use Espo\Core\{
    AclManager,
    Acl\AssignmentChecker,
    Acl\Table,
};

class DefaultAssignmentChecker implements AssignmentChecker
{
    protected const FIELD_ASSIGNED_USERS = 'assignedUsers';

    protected const FIELD_TEAMS = 'teams';

    protected const ATTR_ASSIGNED_USER_ID = 'assignedUserId';

    protected const ATTR_TEAMS_IDS = 'teamsIds';

    protected const ATTR_ASSIGNED_USERS_IDS = 'assignedUsersIds';

    private $aclManager;

    private $entityManager;

    private $ormDefs;

    public function __construct(AclManager $aclManager, EntityManager $entityManager, Defs $ormDefs)
    {
        $this->aclManager = $aclManager;
        $this->entityManager = $entityManager;
        $this->ormDefs = $ormDefs;
    }

    public function check(User $user, Entity $entity): bool
    {
        if (!$this->isPermittedAssignedUser($user, $entity)) {
            return false;
        }

        if (!$this->isPermittedTeams($user, $entity)) {
            return false;
        }

        if ($this->hasAssignedUsersField($entity->getEntityType())) {
            if (!$this->isPermittedAssignedUsers($user, $entity)) {
                return false;
            }
        }

        return true;
    }

    private function hasAssignedUsersField(string $entityType): bool
    {
        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->hasField(self::FIELD_ASSIGNED_USERS) &&
            $entityDefs->getField(self::FIELD_ASSIGNED_USERS)->getType() === 'linkMultiple' &&
            $entityDefs->hasRelation(self::FIELD_ASSIGNED_USERS) &&
            $entityDefs->getRelation(self::FIELD_ASSIGNED_USERS)->getForeignEntityType() === 'User';
    }

    protected function isPermittedAssignedUser(User $user, Entity $entity): bool
    {
        if (!$entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            return true;
        }

        $assignedUserId = $entity->get(self::ATTR_ASSIGNED_USER_ID);

        if ($user->isPortal()) {
            if (!$entity->isAttributeChanged(self::ATTR_ASSIGNED_USER_ID)) {
                return true;
            }

            return false;
        }

        $assignmentPermission = $this->aclManager->getPermissionLevel($user, 'assignmentPermission');

        if (
            $assignmentPermission === Table::LEVEL_YES ||
            !in_array($assignmentPermission, [Table::LEVEL_TEAM, Table::LEVEL_NO])
        ) {
            return true;
        }

        $toProcess = false;

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged(self::ATTR_ASSIGNED_USER_ID)) {
                $toProcess = true;
            }
        }
        else {
            $toProcess = true;
        }

        if (!$toProcess) {
            return true;
        }

        if (empty($assignedUserId)) {
            if ($assignmentPermission === Table::LEVEL_NO && !$user->isApi()) {
                return false;
            }

            return true;
        }

        if ($assignmentPermission === Table::LEVEL_NO) {
            if ($user->id !== $assignedUserId) {
                return false;
            }
        }
        else if ($assignmentPermission === Table::LEVEL_TEAM) {
            $teamIdList = $user->get(self::ATTR_TEAMS_IDS);

            if (
                !$this->getUserRepository()->checkBelongsToAnyOfTeams($assignedUserId, $teamIdList)
            ) {
                return false;
            }
        }

        return true;
    }

    private function getUserRepository(): UserRepository
    {
        /** @var UserRepository */
        return $this->entityManager->getRepository('User');
    }

    protected function isPermittedTeams(User $user, Entity $entity): bool
    {
        $assignmentPermission = $this->aclManager->getPermissionLevel($user, 'assignmentPermission');

        if (!in_array($assignmentPermission, [Table::LEVEL_TEAM, Table::LEVEL_NO])) {
            return true;
        }

        if (!$entity instanceof CoreEntity) {
            return true;
        }

        if (!$entity->hasLinkMultipleField(self::FIELD_TEAMS)) {
            return true;
        }

        $teamIdList = $entity->getLinkMultipleIdList(self::FIELD_TEAMS);

        if (empty($teamIdList)) {
            return $this->isPermittedTeamsEmpty($user, $entity);
        }

        $newIdList = [];

        if (!$entity->isNew()) {
            $existingIdList = [];

            $teamCollection = $this->entityManager
                ->getRDBRepository($entity->getEntityType())
                ->getRelation($entity, self::FIELD_TEAMS)
                ->select('id')
                ->find();

            foreach ($teamCollection as $team) {
                $existingIdList[] = $team->getId();
            }

            foreach ($teamIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $newIdList[] = $id;
                }
            }
        }
        else {
            $newIdList = $teamIdList;
        }

        if (empty($newIdList)) {
            return true;
        }

        $userTeamIdList = $user->getLinkMultipleIdList(self::FIELD_TEAMS);

        foreach ($newIdList as $id) {
            if (!in_array($id, $userTeamIdList)) {
                return false;
            }
        }

        return true;
    }

    private function isPermittedTeamsEmpty(User $user, CoreEntity $entity): bool
    {
        $assignmentPermission = $this->aclManager->getPermissionLevel($user, 'assignmentPermission');

        if ($assignmentPermission !== Table::LEVEL_TEAM) {
            return true;
        }

        if ($entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            $assignedUserIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

            if (empty($assignedUserIdList)) {
                return false;
            }
        }
        else if ($entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            if (!$entity->get(self::ATTR_ASSIGNED_USER_ID)) {
                return false;
            }
        }

        return true;
    }

    protected function isPermittedAssignedUsers(User $user, Entity $entity): bool
    {
        if (!$entity instanceof CoreEntity) {
            return true;
        }

        if (!$entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            return true;
        }

        if ($user->isPortal()) {
            if (!$entity->isAttributeChanged(self::ATTR_ASSIGNED_USERS_IDS)) {
                return true;
            }

            return false;
        }

        $assignmentPermission = $this->aclManager->getPermissionLevel($user, 'assignmentPermission');

        if (
            $assignmentPermission === Table::LEVEL_YES ||
            !in_array($assignmentPermission, [Table::LEVEL_TEAM, Table::LEVEL_NO])
        ) {
            return true;
        }

        $toProcess = false;

        if (!$entity->isNew()) {
            $userIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

            if ($entity->isAttributeChanged(self::ATTR_ASSIGNED_USERS_IDS)) {
                $toProcess = true;
            }
        }
        else {
            $toProcess = true;
        }

        $userIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

        if (!$toProcess) {
            return true;
        }

        if (empty($userIdList)) {
            if ($assignmentPermission === Table::LEVEL_NO && !$user->isApi()) {
                return false;
            }

            return true;
        }

        if ($assignmentPermission === Table::LEVEL_NO) {
            return $this->isPermittedAssignedUsersLevelNo($user, $entity);
        }

        if ($assignmentPermission === Table::LEVEL_TEAM) {
            return $this->isPermittedAssignedUsersLevelTeam($user, $entity);
        }

        return true;
    }

    private function isPermittedAssignedUsersLevelNo(User $user, CoreEntity $entity): bool
    {
        $userIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

        $fetchedAssignedUserIdList = $entity->getFetched(self::ATTR_ASSIGNED_USERS_IDS);

        foreach ($userIdList as $userId) {
            if (!$entity->isNew() && in_array($userId, $fetchedAssignedUserIdList)) {
                continue;
            }

            if ($user->getId() !== $userId) {
                return false;
            }
        }

        return true;
    }

    private function isPermittedAssignedUsersLevelTeam(User $user, CoreEntity $entity): bool
    {
        $userIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

        $fetchedAssignedUserIdList = $entity->getFetched(self::ATTR_ASSIGNED_USERS_IDS);

        $teamIdList = $user->getLinkMultipleIdList(self::FIELD_TEAMS);

        foreach ($userIdList as $userId) {
            if (!$entity->isNew() && in_array($userId, $fetchedAssignedUserIdList)) {
                continue;
            }

            if (
                !$this->getUserRepository()->checkBelongsToAnyOfTeams($userId, $teamIdList)
            ) {
                return false;
            }
        }

        return true;
    }
}
