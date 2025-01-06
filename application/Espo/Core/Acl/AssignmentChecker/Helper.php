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

namespace Espo\Core\Acl\AssignmentChecker;

use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\Repositories\User as UserRepository;

class Helper
{
    private const ATTR_ASSIGNED_USER_ID = Field::ASSIGNED_USER . 'Id';
    private const FIELD_TEAMS = Field::TEAMS;
    private const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;
    private const FIELD_COLLABORATORS = Field::COLLABORATORS;

    public function __construct(
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private Defs $ormDefs,
        private Metadata $metadata,
    ) {}

    public function checkAssignedUser(User $user, Entity $entity): bool
    {
        if (!$entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            return true;
        }

        if ($user->isPortal()) {
            return !$entity->isAttributeChanged(self::ATTR_ASSIGNED_USER_ID);
        }

        $assignmentPermission = $this->aclManager->getPermissionLevel($user, Permission::ASSIGNMENT);

        if ($assignmentPermission === Table::LEVEL_ALL) {
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

        $assignedUserId = $entity->get(self::ATTR_ASSIGNED_USER_ID);

        if (!$assignedUserId) {
            if ($assignmentPermission === Table::LEVEL_NO && !$user->isApi()) {
                return false;
            }

            return true;
        }

        if ($assignmentPermission === Table::LEVEL_NO) {
            return $user->getId() === $assignedUserId;
        }

        if ($assignmentPermission === Table::LEVEL_TEAM) {
            $teamIdList = $user->getTeamIdList();

            return $this->getUserRepository()->checkBelongsToAnyOfTeams($assignedUserId, $teamIdList);
        }

        return false;
    }

    public function checkTeams(User $user, Entity $entity): bool
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

        if ($teamIdList === []) {
            return $this->isPermittedTeamsEmpty($user, $entity);
        }

        $newIdList = [];

        if (!$entity->isNew()) {
            $existingIdList = [];

            $teamCollection = $this->entityManager
                ->getRelation($entity, self::FIELD_TEAMS)
                ->select(Attribute::ID)
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

        if ($newIdList === []) {
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

    public function checkUsers(User $user, Entity $entity, string $field): bool
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

        $toProcess = $entity->isNew() || $entity->isAttributeChanged($idsAttr);

        if (!$toProcess) {
            return true;
        }

        $userIds = $entity->getLinkMultipleIdList($field);

        if ($userIds === []) {
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

        return false;
    }

    private function isPermittedUsersLevelNo(User $user, CoreEntity $entity, string $field): bool
    {
        $userIds = $this->getAddedLinkMultipleIds($entity, $field);

        foreach ($userIds as $userId) {
            if ($user->getId() !== $userId) {
                return false;
            }
        }

        return true;
    }

    private function isPermittedUsersLevelTeam(User $user, CoreEntity $entity, string $field): bool
    {
        $teamIds = $user->getLinkMultipleIdList(self::FIELD_TEAMS);
        $userIds = $this->getAddedLinkMultipleIds($entity, $field);

        foreach ($userIds as $userId) {
            if (!$this->getUserRepository()->checkBelongsToAnyOfTeams($userId, $teamIds)) {
                return false;
            }
        }

        return true;
    }

    private function hasOnlyInternalUsers(User $user, CoreEntity $entity, string $field): bool
    {
        $ids = array_diff($this->getAddedLinkMultipleIds($entity, $field), [$user->getId()]);
        $ids = array_values($ids);

        $count = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'type!=' => [
                    User::TYPE_REGULAR,
                    User::TYPE_ADMIN,
                ],
                Attribute::ID => $ids,
            ])
            ->count();

        return $count === 0;
    }

    /**
     * @return string[]
     */
    private function getAddedLinkMultipleIds(CoreEntity $entity, string $field): array
    {
        /** @var string[] $previousIds */
        $previousIds = $entity->getFetched(self::FIELD_COLLABORATORS . 'Ids') ?? [];

        return array_values(array_diff($entity->getLinkMultipleIdList($field), $previousIds));
    }

    private function isPermittedTeamsEmpty(User $user, CoreEntity $entity): bool
    {
        $assignmentPermission = $this->aclManager->getPermissionLevel($user, Permission::ASSIGNMENT);

        if ($assignmentPermission !== Table::LEVEL_TEAM) {
            return true;
        }

        if ($entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            $assignedUserIdList = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);

            if ($assignedUserIdList === []) {
                return false;
            }
        } else if ($entity->hasAttribute(self::ATTR_ASSIGNED_USER_ID)) {
            if (!$entity->get(self::ATTR_ASSIGNED_USER_ID)) {
                return false;
            }
        }

        return true;
    }

    public function hasAssignedUsersField(string $entityType): bool
    {
        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->hasField(self::FIELD_ASSIGNED_USERS) &&
            $entityDefs->getField(self::FIELD_ASSIGNED_USERS)->getType() === FieldType::LINK_MULTIPLE &&
            $entityDefs->hasRelation(self::FIELD_ASSIGNED_USERS) &&
            $entityDefs->getRelation(self::FIELD_ASSIGNED_USERS)->getForeignEntityType() === User::ENTITY_TYPE;
    }

    public function hasCollaboratorsField(string $entityType): bool
    {
        if (!$this->metadata->get("scopes.$entityType.collaborators")) {
            return false;
        }

        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->tryGetField(self::FIELD_COLLABORATORS)?->getType() === FieldType::LINK_MULTIPLE &&
            $entityDefs->tryGetRelation(self::FIELD_COLLABORATORS)?->tryGetForeignEntityType() === User::ENTITY_TYPE;
    }

    private function getUserRepository(): UserRepository
    {
        /** @var UserRepository */
        return $this->entityManager->getRepository(User::ENTITY_TYPE);
    }
}
