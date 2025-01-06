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

namespace Espo\Core;

use Espo\Core\Acl\Exceptions\NotImplemented;
use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;

use Espo\ORM\Entity;
use Espo\Entities\User;

use stdClass;

/**
 * A wrapper for `AclManager` for a current user. A central access point for access checking.
 */
class Acl
{
    public function __construct(
        protected AclManager $aclManager,
        protected User $user
    ) {}

    /**
     * Get a full access data map.
     */
    public function getMapData(): stdClass
    {
        return $this->aclManager->getMapData($this->user);
    }

    /**
     * Get an access level for a specific scope and action.
     *
     * @param Table::ACTION_* $action
     * @noinspection PhpDocSignatureInspection
     */
    public function getLevel(string $scope, string $action): string
    {
        return $this->aclManager->getLevel($this->user, $scope, $action);
    }

    /**
     * Get a permission. E.g. 'assignment' permission.
     */
    public function getPermissionLevel(string $permission): string
    {
        return $this->aclManager->getPermissionLevel($this->user, $permission);
    }

    /**
     * Whether there's no 'read' access for a specific scope.
     */
    public function checkReadNo(string $scope): bool
    {
        return $this->aclManager->checkReadNo($this->user, $scope);
    }

    /**
     * Whether 'read' access is set to 'team' for a specific scope.
     */
    public function checkReadOnlyTeam(string $scope): bool
    {
        return $this->aclManager->checkReadOnlyTeam($this->user, $scope);
    }

    /**
     * Whether 'read' access is set to 'own' for a specific scope.
     */
    public function checkReadOnlyOwn(string $scope): bool
    {
        return $this->aclManager->checkReadOnlyOwn($this->user, $scope);
    }

    /**
     * Whether 'read' access is set to 'all' for a specific scope.
     */
    public function checkReadAll(string $scope): bool
    {
        return $this->aclManager->checkReadAll($this->user, $scope);
    }

    /**
     * Check a scope or entity. If $action is omitted, it will check
     * whether a scope level is set to 'enabled'.
     *
     * @param string|Entity $subject An entity type or entity.
     * @param Table::ACTION_*|null $action Action to check. Constants are available in the `Table` class.
     * @throws NotImplemented
     * @noinspection PhpDocSignatureInspection
     */
    public function check($subject, ?string $action = null): bool
    {
        return $this->aclManager->check($this->user, $subject, $action);
    }

    /**
     * The same as `check` but does not throw NotImplemented exception.
     *
     * @param string|Entity $subject An entity type or entity.
     * @param Table::ACTION_*|null $action Action to check. Constants are available in the `Table` class.
     * @noinspection PhpDocSignatureInspection
     */
    public function tryCheck($subject, ?string $action = null): bool
    {
        return $this->aclManager->tryCheck($this->user, $subject, $action);
    }

    /**
     * Check access to scope. If $action is omitted, it will check
     * whether a scope level is set to 'enabled'.
     *
     * @throws NotImplemented
     */
    public function checkScope(string $scope, ?string $action = null): bool
    {
        return $this->aclManager->checkScope($this->user, $scope, $action);
    }

    /**
     * Check access to a specific entity.
     *
     * @param Entity $entity An entity to check.
     * @param Table::ACTION_* $action Action to check. Constants are available in the `Table` class.
     * @noinspection PhpDocSignatureInspection
     */
    public function checkEntity(Entity $entity, string $action = Table::ACTION_READ): bool
    {
        return $this->aclManager->checkEntity($this->user, $entity, $action);
    }

    /**
     * Check 'read' access to a specific entity.
     */
    public function checkEntityRead(Entity $entity): bool
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return $this->checkEntity($entity, Table::ACTION_READ);
    }

    /**
     * Check 'create' access to a specific entity.
     */
    public function checkEntityCreate(Entity $entity): bool
    {
        return $this->checkEntity($entity, Table::ACTION_CREATE);
    }

    /**
     * Check 'edit' access to a specific entity.
     */
    public function checkEntityEdit(Entity $entity): bool
    {
        return $this->checkEntity($entity, Table::ACTION_EDIT);
    }

    /**
     * Check 'delete' access to a specific entity.
     */
    public function checkEntityDelete(Entity $entity): bool
    {
        return $this->checkEntity($entity, Table::ACTION_DELETE);
    }

    /**
     * Check 'stream' access to a specific entity.
     */
    public function checkEntityStream(Entity $entity): bool
    {
        return $this->checkEntity($entity, Table::ACTION_STREAM);
    }

    /**
     * Check whether a user is an owner of an entity.
     */
    public function checkOwnershipOwn(Entity $entity): bool
    {
        return $this->aclManager->checkOwnershipOwn($this->user, $entity);
    }

    /**
     * Check whether an entity belongs to a user team.
     */
    public function checkOwnershipTeam(Entity $entity): bool
    {
        return $this->aclManager->checkOwnershipTeam($this->user, $entity);
    }

    /**
     * Check whether an entity is shared with a user.
     *
     * @param Table::ACTION_* $action
     * @since 9.0.0
     * @noinspection PhpDocSignatureInspection
     */
    public function checkOwnershipShared(Entity $entity, string $action): bool
    {
        return $this->aclManager->checkOwnershipShared($this->user, $entity, $action);
    }

    /**
     * Get attributes forbidden for a user.
     *
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     * @noinspection PhpDocSignatureInspection
     */
    public function getScopeForbiddenAttributeList(
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        return $this->aclManager
            ->getScopeForbiddenAttributeList($this->user, $scope, $action, $thresholdLevel);
    }

    /**
     * Get fields forbidden for a user.
     *
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     * @noinspection PhpDocSignatureInspection
     */
    public function getScopeForbiddenFieldList(
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        return $this->aclManager
            ->getScopeForbiddenFieldList($this->user, $scope, $action, $thresholdLevel);
    }

    /**
     * Check access to a field.
     *
     * @param string $scope A scope (entity type).
     * @param string $field A field to check.
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @return bool
     * @noinspection PhpDocSignatureInspection
     */
    public function checkField(string $scope, string $field, string $action = Table::ACTION_READ): bool
    {
        return $this->aclManager->checkField($this->user, $scope, $field, $action);
    }

    /**
     * Get links forbidden for a user.
     *
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     * @noinspection PhpDocSignatureInspection
     */
    public function getScopeForbiddenLinkList(
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        return $this->aclManager->getScopeForbiddenLinkList($this->user, $scope, $action, $thresholdLevel);
    }

    /**
     * Whether a user has access to another user over a specific permission.
     *
     * @param User|string $target User entity or user ID.
     */
    public function checkUserPermission($target, string $permissionType = Permission::USER): bool
    {
        return $this->aclManager->checkUserPermission($this->user, $target, $permissionType);
    }

    /**
     * Whether a user can assign to another user.
     *
     * @param User|string $target User entity or user ID.
     */
    public function checkAssignmentPermission($target): bool
    {
        return $this->aclManager->checkAssignmentPermission($this->user, $target);
    }

    /**
     * Get a restricted field list for a specific scope by a restriction type.
     *
     * @param GlobalRestriction::TYPE_*|array<int,GlobalRestriction::TYPE_*> $type
     * @return string[]
     */
    public function getScopeRestrictedFieldList(string $scope, $type): array
    {
        return $this->aclManager->getScopeRestrictedFieldList($scope, $type);
    }

    /**
     * Get a restricted attribute list for a specific scope by a restriction type.
     *
     * @param GlobalRestriction::TYPE_*|array<int,GlobalRestriction::TYPE_*> $type
     * @return string[]
     */
    public function getScopeRestrictedAttributeList(string $scope, $type): array
    {
        return $this->aclManager->getScopeRestrictedAttributeList($scope, $type);
    }

    /**
     * Get a restricted link list for a specific scope by a restriction type.
     *
     * @param GlobalRestriction::TYPE_*|array<int,GlobalRestriction::TYPE_*> $type
     * @return string[]
     */
    public function getScopeRestrictedLinkList(string $scope, $type): array
    {
        return $this->aclManager->getScopeRestrictedLinkList($scope, $type);
    }

    /**
     * @deprecated Use `getPermissionLevel` instead.
     */
    public function get(string $permission): string
    {
        return $this->getPermissionLevel($permission);
    }

    /**
     * @deprecated Use `checkOwnershipOwn` instead.
     */
    public function checkIsOwner(Entity $entity): bool
    {
        return $this->aclManager->checkOwnershipOwn($this->user, $entity);
    }

    /**
     * @deprecated Use `checkOwnershipTeam` instead.
     */
    public function checkInTeam(Entity $entity): bool
    {
        return $this->aclManager->checkOwnershipTeam($this->user, $entity);
    }

    /**
     * @deprecated Use `checkUserPermission` instead.
     */
    public function checkUser(string $permission, User $entity): bool
    {
        /** @noinspection PhpDeprecationInspection */
        return $this->aclManager->checkUser($this->user, $permission, $entity);
    }
}
