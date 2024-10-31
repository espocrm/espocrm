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

namespace Espo\Core\Acl;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Repositories\User as UserRepository;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Core\AclManager;

/**
 * @implements AssignmentChecker<CoreEntity>
 */
class DefaultAssignmentChecker implements AssignmentChecker
{
    protected const FIELD_ASSIGNED_USERS = 'assignedUsers';
    protected const FIELD_TEAMS = 'teams';
    protected const ATTR_ASSIGNED_USER_ID = 'assignedUserId';
    private const FIELD_COLLABORATORS = 'collaborators';

    public function __construct(
        private AclManager $aclManager,
        private EntityManager $entityManager,
        private Defs $ormDefs,
        private Metadata $metadata,
    ) {}

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

        if ($this->hasCollaboratorsField($entity->getEntityType())) {
            if (!$this->isPermittedUsers($user, $entity, self::FIELD_COLLABORATORS)) {
                return false;
            }
        }

        return true;
    }

    private function hasCollaboratorsField(string $entityType): bool
    {
        if (!$this->metadata->get("scopes.$entityType.collaborators")) {
            return false;
        }

        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->tryGetField(self::FIELD_COLLABORATORS)?->getType() === FieldType::LINK_MULTIPLE &&
            $entityDefs->tryGetRelation(self::FIELD_COLLABORATORS)?->tryGetForeignEntityType() === User::ENTITY_TYPE;
    }

    private function hasAssignedUsersField(string $entityType): bool
    {
        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->hasField(self::FIELD_ASSIGNED_USERS) &&
            $entityDefs->getField(self::FIELD_ASSIGNED_USERS)->getType() === FieldType::LINK_MULTIPLE &&
            $entityDefs->hasRelation(self::FIELD_ASSIGNED_USERS) &&
            $entityDefs->getRelation(self::FIELD_ASSIGNED_USERS)->getForeignEntityType() === User::ENTITY_TYPE;
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

        $assignmentPermission = $this->aclManager->getPermissionLevel($user, Permission::ASSIGNMENT);

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
        } else {
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
            if ($user->getId() !== $assignedUserId) {
                return false;
            }
        } else if ($assignmentPermission === Table::LEVEL_TEAM) {
            $teamIdList = $user->getTeamIdList();

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
        $assignmentPermission = $this->aclManager->getPermissionLevel($user, Permission::ASSIGNMENT);

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
        } else {
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
        $assignmentPermission = $this->aclManager->getPermissionLevel($user, Permission::ASSIGNMENT);

        if ($assignmentPermission !== Table::LEVEL_TEAM) {
            return true;
        }

        if ($entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            $assignedUserIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

            if (empty($assignedUserIdList)) {
                return false;
            }
        } else if ($entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            if (!$entity->get(self::ATTR_ASSIGNED_USER_ID)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Left for backward compatibility.
     */
    protected function isPermittedAssignedUsers(User $user, Entity $entity): bool
    {
        return $this->isPermittedUsers($user, $entity, self::FIELD_ASSIGNED_USERS);
    }

    private function isPermittedUsers(User $user, Entity $entity, string $field): bool
    {
        if (!$entity instanceof CoreEntity) {
            return true;
        }

        $idsAttr = $field . 'Ids';

        if (!$entity->hasLinkMultipleField($field)) {
            return true;
        }

        if ($user->isPortal()) {
            if (!$entity->isAttributeChanged($idsAttr)) {
                return true;
            }

            return false;
        }

        $assignmentPermission = $this->aclManager->getPermissionLevel($user, Permission::ASSIGNMENT);

        if ($assignmentPermission === Table::LEVEL_ALL) {
            if (!$this->hasOnlyInternalUsers($user, $entity, $field)) {
                return false;
            }

            return true;
        }

        $toProcess = false;

        if (!$entity->isNew()) {
            // Might be on purpose.
            $entity->getLinkMultipleIdList($field);

            if ($entity->isAttributeChanged($idsAttr)) {
                $toProcess = true;
            }
        } else {
            $toProcess = true;
        }

        $userIdList = $entity->getLinkMultipleIdList($field);

        if (!$toProcess) {
            return true;
        }

        if ($userIdList === []) {
            if ($assignmentPermission === Table::LEVEL_NO && !$user->isApi()) {
                return false;
            }

            return true;
        }

        if ($assignmentPermission === Table::LEVEL_NO) {
            return $this->isPermittedUsersLevelNo($user, $entity, $field);
        }

        if ($assignmentPermission === Table::LEVEL_TEAM) {
            return $this->isPermittedUsersLevelTeam($user, $entity, $field);
        }

        /** @phpstan-ignore-next-line */
        return true;
    }

    private function isPermittedUsersLevelNo(User $user, CoreEntity $entity, string $field): bool
    {
        $idsAttr = $field . 'Ids';

        $userIdList = $entity->getLinkMultipleIdList($field);
        $fetchedAssignedUserIdList = $entity->getFetched($idsAttr);

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

    private function isPermittedUsersLevelTeam(User $user, CoreEntity $entity, string $field): bool
    {
        $idsAttr = $field . 'Ids';

        $userIdList = $entity->getLinkMultipleIdList($field);
        $fetchedAssignedUserIdList = $entity->getFetched($idsAttr);
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

    private function hasOnlyInternalUsers(User $user, CoreEntity $entity, string $field): bool
    {
        $ids = array_values(array_diff($entity->getLinkMultipleIdList($field), [$user->getId()]));

        $count = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'type!=' => [
                    User::TYPE_REGULAR,
                    User::TYPE_ADMIN,
                ],
                'id' => $ids,
            ])
            ->count();

        return $count === 0;
    }
}
