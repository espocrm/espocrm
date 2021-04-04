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

namespace Espo\Core;

use Espo\ORM\Entity;

use Espo\Entities\User;

use Espo\Core\{
    ORM\EntityManager,
    Acl\AclFactory,
    Acl\GlobalRestrictonFactory,
    Acl\GlobalRestricton,
    Acl\OwnerUserFieldProvider,
    Acl as UserAclWrapper,
    Acl\Table as Table,
    Acl\ScopeAcl,
    Acl\EntityAcl,
    Acl\EntityCreateAcl,
    Acl\EntityReadAcl,
    Acl\EntityEditAcl,
    Acl\EntityDeleteAcl,
    Acl\EntityStreamAcl,
    Acl\LevelProvider,
};

use StdClass;
use RuntimeException;

/**
 * Used to check access for a specific user.
 */
class AclManager
{
    protected $implementationHashMap = [];

    private $tableHashMap = [];

    protected $tableClassName = Table::class;

    protected $userAclClassName = UserAclWrapper::class;

    protected const PERMISSION_ASSIGNMENT = 'assignment';

    private $actionInterfaceMap = [
        Table::ACTION_CREATE => EntityCreateAcl::class,
        Table::ACTION_READ => EntityReadAcl::class,
        Table::ACTION_EDIT => EntityEditAcl::class,
        Table::ACTION_DELETE => EntityDeleteAcl::class,
        Table::ACTION_STREAM => EntityStreamAcl::class,
    ];

    protected $injectableFactory;

    protected $entityManager;

    protected $aclFactory;

    protected $globalRestricton;

    protected $ownerUserFieldProvider;

    public function __construct(
        InjectableFactory $injectableFactory,
        EntityManager $entityManager,
        AclFactory $aclFactory,
        GlobalRestrictonFactory $globalRestrictonFactory,
        OwnerUserFieldProvider $ownerUserFieldProvider
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->entityManager = $entityManager;
        $this->aclFactory = $aclFactory;
        $this->ownerUserFieldProvider = $ownerUserFieldProvider;

        $this->globalRestricton = $globalRestrictonFactory->create();
    }

    /**
     * Get an ACL implementation for a scope.
     *
     * @return ScopeAcl|EntityAcl|LevelProvider|
     * EntityCreateAcl|EntityReadAcl|EntityEditAcl|EntityDeleteAcl|EntityStreamAcl
     */
    public function getImplementation(string $scope): object
    {
        if (!array_key_exists($scope, $this->implementationHashMap)) {
            $this->implementationHashMap[$scope] = $this->aclFactory->create($scope);
        }

        return $this->implementationHashMap[$scope];
    }

    protected function getEntityImplementation(string $scope): EntityAcl
    {
        $impl = $this->getImplementation($scope);

        if (!$impl instanceof EntityAcl) {
            throw new RuntimeException("Acl must implement EntityAcl interface.");
        }

        return $impl;
    }

    protected function getTable(User $user): Table
    {
        $key = $user->getId();

        if (!$key) {
            $key = spl_object_hash($user);
        }

        if (!array_key_exists($key, $this->tableHashMap)) {
            $this->tableHashMap[$key] = $this->injectableFactory->createWith($this->tableClassName, [
                'user' => $user,
            ]);
        }

        return $this->tableHashMap[$key];
    }

    public function getMap(User $user): StdClass
    {
        return $this->getTable($user)->getMap();
    }

    /**
     * Get an access level for a specific scope and action.
     */
    public function getLevel(User $user, string $scope, string $action): string
    {
        $data = $this->getTable($user)->getScopeData($scope);

        $impl = $this->getImplementation($scope);

        if ($impl instanceof LevelProvider) {
            return $impl->getLevel($user, $data, $action);
        }

        return $data->get($action);
    }

    /**
     * Get a permission. E.g. 'assignment' permission.
     */
    public function get(User $user, string $permission): ?string
    {
        if (substr($permission, -10) !== 'Permission') {
            $permission .= 'Permission';
        }

        return $this->getTable($user)->get($permission);
    }

    /**
     * Whether there's no 'read' access for a specific scope.
     */
    public function checkReadNo(User $user, string $scope): bool
    {
        return $this->getLevel($user, $scope, Table::ACTION_READ) === Table::LEVEL_NO;
    }

    /**
     * Whether 'read' access is set to 'team' for a specific scope.
     */
    public function checkReadOnlyTeam(User $user, string $scope): bool
    {
        return $this->getLevel($user, $scope, Table::ACTION_READ) === Table::LEVEL_TEAM;
    }

    /**
     * Whether 'read' access is set to 'own' for a specific scope.
     */
    public function checkReadOnlyOwn(User $user, string $scope): bool
    {
        return $this->getLevel($user, $scope, Table::ACTION_READ) === Table::LEVEL_OWN;
    }

    /**
     * Whether 'read' access is set to 'all' for a specific scope.
     */
    public function checkReadAll(User $user, string $scope): bool
    {
        return $this->getLevel($user, $scope, Table::ACTION_READ) === Table::LEVEL_ALL;
    }

    /**
     * Check a scope or entity. If $action is omitted, it will check whether a scope level is set to 'enabled'.
     *
     * @param string|Entity $subject An entity type or entity.
     */
    public function check(User $user, $subject, ?string $action = null): bool
    {
        if (is_string($subject)) {
            return $this->checkScope($user, $subject, $action);
        }

        $entity = $subject;

        if ($entity instanceof Entity) {
            $action = $action ?? Table::ACTION_READ;

            return $this->checkEntity($user, $entity, $action);
        }

        return false;
    }

    /**
     * Check access to a specific entity.
     */
    public function checkEntity(User $user, Entity $entity, string $action = Table::ACTION_READ): bool
    {
        $scope = $entity->getEntityType();

        $data = $this->getTable($user)->getScopeData($scope);

        $impl = $this->getImplementation($scope);

        if (!$action) {
            $action = Table::ACTION_READ;
        }

        $methodName = 'checkEntity' . ucfirst($action);

        $interface = $this->actionInterfaceMap[$action] ?? null;

        if ($interface && $impl instanceof $interface) {
            return $impl->$methodName($user, $entity, $data);
        }

        if (!$impl instanceof EntityAcl) {
            throw new RuntimeException("Acl must implement EntityAcl interface.");
        }

        if (method_exists($impl, $methodName)) {
            // For backward compatibility.
            return $impl->$methodName($user, $entity, $data);
        }

        return $impl->checkEntity($user, $entity, $data, $action);
    }

    /**
     * Check 'read' access to a specific entity.
     */
    public function checkEntityRead(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_READ);
    }

    /**
     * Check 'create' access to a specific entity.
     */
    public function checkEntityCreate(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_CREATE);
    }

    /**
     * Check 'edit' access to a specific entity.
     */
    public function checkEntityEdit(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_EDIT);
    }

    /**
     * Check 'delete' access to a specific entity.
     */
    public function checkEntityDelete(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_DELETE);
    }

    /**
     * Check 'stream' access to a specific entity.
     */
    public function checkEntityStream(User $user, Entity $entity): bool
    {
        return $this->checkEntity($entity, Table::ACTION_STREAM);
    }

    /**
     * Whether a user is owned of an entity (record). Usually 'assignedUser' field is used for checking.
     */
    public function checkIsOwner(User $user, Entity $entity): bool
    {
        return (bool) $this->getEntityImplementation($entity->getEntityType())->checkIsOwner($user, $entity);
    }

    /**
     * Whether a user team list overlaps with teams set in an entity.
     */
    public function checkInTeam(User $user, Entity $entity): bool
    {
        return (bool) $this->getEntityImplementation($entity->getEntityType())->checkInTeam($user, $entity);
    }

    /**
     * Check access to scope. If $action is omitted, it will check whether a scope level is set to 'enabled'.
     */
    public function checkScope(User $user, string $scope, ?string $action = null): bool
    {
        $data = $this->getTable($user)->getScopeData($scope);

        return $this->getImplementation($scope)->checkScope($user, $data, $action);
    }

    /**
     * @deprecated Use checkUserPermission instead.
     */
    public function checkUser(User $user, string $permission, User $target): bool
    {
        if ($this->get($user, $permission) === Table::LEVEL_ALL) {
            return true;
        }

        if ($this->get($user, $permission) === Table::LEVEL_NO) {
            if ($target->id === $user->id) {
                return true;
            }

            return false;
        }

        if ($this->get($user, $permission) === Table::LEVEL_TEAM) {
            if ($target->id === $user->id) {
                return true;
            }

            $targetTeamIdList = $target->getTeamIdList();

            $inTeam = false;

            foreach ($user->getTeamIdList() as $id) {
                if (in_array($id, $targetTeamIdList)) {
                    $inTeam = true;

                    break;
                }
            }

            if ($inTeam) {
                return true;
            }

            return false;
        }

        return false;
    }

    protected function getGlobalRestrictionTypeList(User $user, string $action = Table::ACTION_READ): array
    {
        $typeList = [
            GlobalRestricton::TYPE_FORBIDDEN,
        ];

        if ($action === Table::ACTION_READ) {
            $typeList[] = GlobalRestricton::TYPE_INTERNAL;
        }

        if (!$user->isAdmin()) {
            $typeList[] = GlobalRestricton::TYPE_ONLY_ADMIN;
        }

        if ($action === Table::ACTION_EDIT) {
            $typeList[] = GlobalRestricton::TYPE_READ_ONLY;

            if (!$user->isAdmin()) {
                $typeList[] = GlobalRestricton::TYPE_NON_ADMIN_READ_ONLY;
            }
        }

        return $typeList;
    }

    /**
     * Get attributes forbidden for a user.
     *
     * @return array<string>
     */
    public function getScopeForbiddenAttributeList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        $list = [];

        if (!$user->isAdmin()) {
            $list = $this->getTable($user)->getScopeForbiddenAttributeList($scope, $action, $thresholdLevel);
        }

        if ($thresholdLevel === Table::LEVEL_NO) {
            $list = array_merge(
                $list,
                $this->getScopeRestrictedAttributeList(
                    $scope,
                    $this->getGlobalRestrictionTypeList($user, $action)
                )
            );

            $list = array_values($list);
        }

        return $list;
    }

    /**
     * Get fields forbidden for a user.
     *
     * @return array<string>
     */
    public function getScopeForbiddenFieldList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        $list = [];

        if (!$user->isAdmin()) {
            $list = $this->getTable($user)->getScopeForbiddenFieldList($scope, $action, $thresholdLevel);
        }

        if ($thresholdLevel === Table::LEVEL_NO) {
            $list = array_merge(
                $list,
                $this->getScopeRestrictedFieldList(
                    $scope,
                    $this->getGlobalRestrictionTypeList($user, $action)
                )
            );

            $list = array_values($list);
        }

        return $list;
    }

    /**
     * Get links forbidden for a user.
     *
     * @return array<string>
     */
    public function getScopeForbiddenLinkList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        $list = [];

        if ($thresholdLevel === Table::LEVEL_NO) {
            $list = array_merge(
                $list,
                $this->getScopeRestrictedLinkList(
                    $scope,
                    $this->getGlobalRestrictionTypeList($user, $action)
                )
            );

            $list = array_values($list);
        }

        return $list;
    }

    /**
     * Whether a user has an access to another user over a specific permission.
     *
     * @param User|string $target User entity or user ID.
     */
    public function checkUserPermission(User $user, $target, string $permissionType = 'user'): bool
    {
        $permission = $this->get($user, $permissionType);

        if (is_object($target)) {
            $userId = $target->id;
        }
        else {
            $userId = $target;
        }

        if ($user->id === $userId) {
            return true;
        }

        if ($permission === Table::LEVEL_NO) {
            return false;
        }

        if ($permission === Table::LEVEL_YES) {
            return true;
        }

        if ($permission === Table::LEVEL_TEAM) {
            $teamIdList = $user->getLinkMultipleIdList('teams');

            if (
                !$this->entityManager
                    ->getRepository('User')
                    ->checkBelongsToAnyOfTeams($userId, $teamIdList)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a user can assign to another user.
     *
     * @param User|string $target User entity or user ID.
     */
    public function checkAssignmentPermission(User $user, $target): bool
    {
        return $this->checkUserPermission($user, $target, self::PERMISSION_ASSIGNMENT);
    }

    /**
     * Create a wrapper for a specific user.
     */
    public function createUserAcl(User $user): UserAclWrapper
    {
        $className = $this->userAclClassName;

        $acl = new $className($this, $user);

        return $acl;
    }

    /**
     * Get a restricted field list for a specific scope by a restriction type.
     *
     * @param string|array<string> $type
     * @return array<string>
     */
    public function getScopeRestrictedFieldList(string $scope, $type): array
    {
        if (is_array($type)) {
            $typeList = $type;

            $list = [];

            foreach ($typeList as $type) {
                $list = array_merge($list, $this->globalRestricton->getScopeRestrictedFieldList($scope, $type));
            }

            return array_values($list);
        }

        return $this->globalRestricton->getScopeRestrictedFieldList($scope, $type);
    }

    /**
     * Get a restricted attribute list for a specific scope by a restriction type.
     *
     * @param string|array<string> $type
     * @return array<string>
     */
    public function getScopeRestrictedAttributeList(string $scope, $type): array
    {
        if (is_array($type)) {
            $typeList = $type;

            $list = [];

            foreach ($typeList as $type) {
                $list = array_merge($list, $this->globalRestricton->getScopeRestrictedAttributeList($scope, $type));
            }

            return array_values($list);
        }

        return $this->globalRestricton->getScopeRestrictedAttributeList($scope, $type);
    }

    /**
     * Get a restricted link list for a specific scope by a restriction type.
     *
     * @param string|array<string> $type
     * @return array<string>
     */
    public function getScopeRestrictedLinkList(string $scope, $type): array
    {
        if (is_array($type)) {
            $typeList = $type;

            $list = [];

            foreach ($typeList as $type) {
                $list = array_merge($list, $this->globalRestricton->getScopeRestrictedLinkList($scope, $type));
            }

            return array_values($list);
        }

        return $this->globalRestricton->getScopeRestrictedLinkList($scope, $type);
    }

    /**
     * Get an entity field that stores an owner-user (or multiple users).
     * Must be link or linkMulitple field. NULL means no owner.
     */
    public function getReadOwnerUserField(string $entityType): ?string
    {
        return $this->ownerUserFieldProvider->get($entityType);
    }
}
