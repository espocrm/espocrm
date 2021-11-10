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

use Espo\ORM\EntityManager;

use Espo\Core\{
    Acl,
    Acl\GlobalRestricton,
    Acl\OwnerUserFieldProvider,
    Acl\Table\TableFactory,
    Acl\Table,
    Acl\Map\Map,
    Acl\Map\MapFactory,
    Acl\OwnershipChecker\OwnershipCheckerFactory,
    Acl\OwnershipChecker,
    Acl\OwnershipOwnChecker,
    Acl\OwnershipTeamChecker,
    Acl\AccessChecker\AccessCheckerFactory,
    Acl\AccessChecker,
    Acl\AccessCreateChecker,
    Acl\AccessReadChecker,
    Acl\AccessEditChecker,
    Acl\AccessDeleteChecker,
    Acl\AccessStreamChecker,
    Acl\AccessEntityCreateChecker,
    Acl\AccessEntityReadChecker,
    Acl\AccessEntityEditChecker,
    Acl\AccessEntityDeleteChecker,
    Acl\AccessEntityStreamChecker,
    Acl\Exceptions\NotImplemented,
};

use stdClass;
use InvalidArgumentException;

/**
 * A central access point for access checking.
 */
class AclManager
{
    private $accessCheckerHashMap = [];

    private $ownershipCheckerHashMap = [];

    protected $tableHashMap = [];

    protected $mapHashMap = [];

    protected $userAclClassName = Acl::class;

    protected const PERMISSION_ASSIGNMENT = 'assignment';

    private $entityActionInterfaceMap = [
        Table::ACTION_CREATE => AccessEntityCreateChecker::class,
        Table::ACTION_READ => AccessEntityReadChecker::class,
        Table::ACTION_EDIT => AccessEntityEditChecker::class,
        Table::ACTION_DELETE => AccessEntityDeleteChecker::class,
        Table::ACTION_STREAM => AccessEntityStreamChecker::class,
    ];

    private $actionInterfaceMap = [
        Table::ACTION_CREATE => AccessCreateChecker::class,
        Table::ACTION_READ => AccessReadChecker::class,
        Table::ACTION_EDIT => AccessEditChecker::class,
        Table::ACTION_DELETE => AccessDeleteChecker::class,
        Table::ACTION_STREAM => AccessStreamChecker::class,
    ];

    /**
     * @var AccessCheckerFactory|\Espo\Core\Portal\Acl\AccessChecker\AccessCheckerFactory
     */
    protected $accessCheckerFactory;

    /**
     * @var OwnershipCheckerFactory|\Espo\Core\Portal\Acl\OwnershipChecker\OwnershipCheckerFactory
     */
    protected $ownershipCheckerFactory;

    /**
     * @var TableFactory
     */
    private $tableFactory;

    /**
     * @var MapFactory
     */
    private $mapFactory;

    /**
     * @var GlobalRestricton
     */
    protected $globalRestricton;

    /**
     * @var OwnerUserFieldProvider
     */
    protected $ownerUserFieldProvider;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(
        AccessCheckerFactory $accessCheckerFactory,
        OwnershipCheckerFactory $ownershipCheckerFactory,
        TableFactory $tableFactory,
        MapFactory $mapFactory,
        GlobalRestricton $globalRestricton,
        OwnerUserFieldProvider $ownerUserFieldProvider,
        EntityManager $entityManager
    ) {
        $this->accessCheckerFactory = $accessCheckerFactory;
        $this->ownershipCheckerFactory = $ownershipCheckerFactory;
        $this->tableFactory = $tableFactory;
        $this->mapFactory = $mapFactory;
        $this->globalRestricton = $globalRestricton;
        $this->ownerUserFieldProvider = $ownerUserFieldProvider;
        $this->entityManager = $entityManager;
    }

    /**
     * Get an access checker for a specific scope.
     */
    protected function getAccessChecker(string $scope): AccessChecker
    {
        if (!array_key_exists($scope, $this->accessCheckerHashMap)) {
            $this->accessCheckerHashMap[$scope] = $this->accessCheckerFactory->create($scope, $this);
        }

        return $this->accessCheckerHashMap[$scope];
    }

    /**
     * Get an ownership checker for a specific scope.
     */
    protected function getOwnershipChecker(string $scope): OwnershipChecker
    {
        if (!array_key_exists($scope, $this->ownershipCheckerHashMap)) {
            $this->ownershipCheckerHashMap[$scope] = $this->ownershipCheckerFactory->create($scope, $this);
        }

        return $this->ownershipCheckerHashMap[$scope];
    }

    protected function getTable(User $user): Table
    {
        $key = $user->getId();

        if (!$key) {
            $key = spl_object_hash($user);
        }

        if (!array_key_exists($key, $this->tableHashMap)) {
            $this->tableHashMap[$key] = $this->tableFactory->create($user);
        }

        return $this->tableHashMap[$key];
    }

    protected function getMap(User $user): Map
    {
        $key = $user->getId();

        if (!$key) {
            $key = spl_object_hash($user);
        }

        if (!array_key_exists($key, $this->mapHashMap)) {
            $this->mapHashMap[$key] = $this->mapFactory->create($user, $this->getTable($user));
        }

        return $this->mapHashMap[$key];
    }

    /**
     * Get a full access data map (for front-end).
     */
    public function getMapData(User $user): stdClass
    {
        return $this->getMap($user)->getData();
    }

    /**
     * Get an access level for a specific scope and action.
     */
    public function getLevel(User $user, string $scope, string $action): string
    {
        if (!$this->checkScope($user, $scope)) {
            return Table::LEVEL_NO;
        }

        $data = $this->getTable($user)->getScopeData($scope);

        return $data->get($action);
    }

    /**
     * Get a permission. E.g. 'assignment' permission.
     */
    public function getPermissionLevel(User $user, string $permission): string
    {
        if (substr($permission, -10) === 'Permission') {
            $permission = substr($permission, 0, -10);
        }

        return $this->getTable($user)->getPermissionLevel($permission);
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
     * Check a scope or entity. If $action is omitted, it will check whether
     * a scope level is set to 'enabled'.
     *
     * @param User $user A user to check for.
     * @param string|Entity $subject An entity type or entity.
     * @param string|null $action Action to check. Constants are available in the `Table` class.
     *
     * @throws NotImplemented
     */
    public function check(User $user, $subject, ?string $action = null): bool
    {
        if (is_string($subject)) {
            return $this->checkScope($user, $subject, $action);
        }

        /** @var mixed */
        $entity = $subject;

        if ($entity instanceof Entity) {
            $action = $action ?? Table::ACTION_READ;

            return $this->checkEntity($user, $entity, $action);
        }

        throw new InvalidArgumentException();
    }

    /**
     * The same as `check` but does not throw NotImplemented exception.
     *
     * @param User $user A user to check for.
     * @param string|Entity $subject An entity type or entity.
     * @param string|null $action Action to check. Constants are available in the `Table` class.
     */
    public function tryCheck(User $user, $subject, ?string $action = null): bool
    {
        try {
            return $this->check($user, $subject, $action);
        }
        catch (NotImplemented $e) {
            return false;
        }
    }

    /**
     * Check access to a specific entity.
     *
     * @param User $user A user to check for.
     * @param Entity $entity An entity to check.
     * @param string $action Action to check. Constants are available in the `Table` class.
     *
     * @throws NotImplemented
     */
    public function checkEntity(User $user, Entity $entity, string $action = Table::ACTION_READ): bool
    {
        $scope = $entity->getEntityType();

        if (!$this->checkScope($user, $scope, $action)) {
            return false;
        }

        $data = $this->getTable($user)->getScopeData($scope);

        $checker = $this->getAccessChecker($scope);

        $methodName = 'checkEntity' . ucfirst($action);

        $interface = $this->entityActionInterfaceMap[$action] ?? null;

        if ($interface && $checker instanceof $interface) {
            return $checker->$methodName($user, $entity, $data);
        }

        if (method_exists($checker, $methodName)) {
            // For backward compatibility.
            return $checker->$methodName($user, $entity, $data);
        }

        if (method_exists($checker, 'checkEntity')) {
            // For backward compatibility.
            return $checker->checkEntity($user, $entity, $data, $action);
        }

        throw new NotImplemented("No entity access checker for '{$scope}' action '{$action}'.");
    }

    /**
     * Check 'read' access to a specific entity.
     *
     * @throws NotImplemented.
     */
    public function checkEntityRead(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_READ);
    }

    /**
     * Check 'create' access to a specific entity.
     *
     * @throws NotImplemented
     */
    public function checkEntityCreate(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_CREATE);
    }

    /**
     * Check 'edit' access to a specific entity.
     *
     * @throws NotImplemented
     */
    public function checkEntityEdit(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_EDIT);
    }

    /**
     * Check 'delete' access to a specific entity.
     *
     * @throws NotImplemented
     */
    public function checkEntityDelete(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_DELETE);
    }

    /**
     * Check 'stream' access to a specific entity.
     *
     * @throws NotImplemented
     */
    public function checkEntityStream(User $user, Entity $entity): bool
    {
        return $this->checkEntity($user, $entity, Table::ACTION_STREAM);
    }

    /**
     * Check whether a user is an owner of an entity.
     */
    public function checkOwnershipOwn(User $user, Entity $entity): bool
    {
        $checker = $this->getOwnershipChecker($entity->getEntityType());

        if (!$checker instanceof OwnershipOwnChecker) {
            return false;
        }

        return $checker->checkOwn($user, $entity);
    }

    /**
     * Check whether an entity belongs to a user team.
     */
    public function checkOwnershipTeam(User $user, Entity $entity): bool
    {
        $checker = $this->getOwnershipChecker($entity->getEntityType());

        if (!$checker instanceof OwnershipTeamChecker) {
            return false;
        }

        return $checker->checkTeam($user, $entity);
    }

    /**
     * Check access to scope. If $action is omitted, it will check whether a scope level is set to 'enabled'.
     *
     * @throws NotImplemented If not implemented by an access checker class.
     */
    public function checkScope(User $user, string $scope, ?string $action = null): bool
    {
        if ($action && !$this->checkScope($user, $scope)) {
            return false;
        }

        $data = $this->getTable($user)->getScopeData($scope);

        $checker = $this->getAccessChecker($scope);

        if (!$action) {
            return $checker->check($user, $data);
        }

        $methodName = 'check' . ucfirst($action);

        $interface = $this->actionInterfaceMap[$action] ?? null;

        if ($interface && $checker instanceof $interface) {
            return $checker->$methodName($user, $data);
        }

        // For backward compatibility.
        $methodName = 'checkScope';

        if (!method_exists($checker, $methodName)) {
            throw new NotImplemented("No access checker for '{$scope}' action '{$action}'.");
        }

        return $checker->$methodName($user, $data, $action);
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
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     */
    public function getScopeForbiddenAttributeList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        $list = array_merge(
            $this->getMap($user)->getScopeForbiddenAttributeList(
                $scope,
                $action,
                $thresholdLevel
            ),
            $this->getScopeRestrictedAttributeList(
                $scope,
                $this->getGlobalRestrictionTypeList($user, $action)
            )
        );

        return array_unique($list);
    }

    /**
     * Get fields forbidden for a user.
     *
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     */
    public function getScopeForbiddenFieldList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        $list = array_merge(
            $this->getMap($user)->getScopeForbiddenFieldList(
                $scope,
                $action,
                $thresholdLevel
            ),
            $this->getScopeRestrictedFieldList(
                $scope,
                $this->getGlobalRestrictionTypeList($user, $action)
            )
        );

        return array_unique($list);
    }

    /**
     * Get links forbidden for a user.
     *
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     */
    public function getScopeForbiddenLinkList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        return $this->getScopeRestrictedLinkList(
            $scope,
            $this->getGlobalRestrictionTypeList($user, $action)
        );
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

            /** @var \Espo\Repositories\User $userRepository */
            $userRepository = $this->entityManager->getRepository('User');

            if (!$userRepository->checkBelongsToAnyOfTeams($userId, $teamIdList)) {
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
    public function createUserAcl(User $user): Acl
    {
        $className = $this->userAclClassName;

        $acl = new $className($this, $user);

        return $acl;
    }

    /**
     * Get a restricted field list for a specific scope by a restriction type.
     *
     * @param string|string[] $type
     * @return string[]
     */
    public function getScopeRestrictedFieldList(string $scope, $type): array
    {
        if (is_array($type)) {
            $typeList = $type;

            $list = [];

            foreach ($typeList as $type) {
                $list = array_merge(
                    $list,
                    $this->globalRestricton->getScopeRestrictedFieldList($scope, $type)
                );
            }

            return array_unique($list);
        }

        return $this->globalRestricton->getScopeRestrictedFieldList($scope, $type);
    }

    /**
     * Get a restricted attribute list for a specific scope by a restriction type.
     *
     * @param string|string[] $type
     * @return string[]
     */
    public function getScopeRestrictedAttributeList(string $scope, $type): array
    {
        if (is_array($type)) {
            $typeList = $type;

            $list = [];

            foreach ($typeList as $type) {
                $list = array_merge(
                    $list,
                    $this->globalRestricton->getScopeRestrictedAttributeList($scope, $type)
                );
            }

            return array_unique($list);
        }

        return $this->globalRestricton->getScopeRestrictedAttributeList($scope, $type);
    }

    /**
     * Get a restricted link list for a specific scope by a restriction type.
     *
     * @param string|string[] $type
     * @return string[]
     */
    public function getScopeRestrictedLinkList(string $scope, $type): array
    {
        if (is_array($type)) {
            $typeList = $type;

            $list = [];

            foreach ($typeList as $type) {
                $list = array_merge(
                    $list,
                    $this->globalRestricton->getScopeRestrictedLinkList($scope, $type)
                );
            }

            return array_unique($list);
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

    /**
     * @deprecated User `checkOwnershipOwn`.
     */
    public function checkIsOwner(User $user, Entity $entity): bool
    {
        return $this->checkOwnershipOwn($user, $entity);
    }

    /**
     * @deprecated User `checkOwnershipTeam`.
     */
    public function checkInTeam(User $user, Entity $entity): bool
    {
        return $this->checkOwnershipTeam($user, $entity);
    }

    /**
     * @deprecated
     */
    public function getImplementation(string $scope): object
    {
        return $this->getAccessChecker($scope);
    }

    /**
     * @deprecated Use `getPermissionLevel` instead.
     */
    public function get(User $user, string $permission): string
    {
        return $this->getPermissionLevel($user, $permission);
    }

    /**
     * @deprecated Use `checkUserPermission` instead.
     */
    public function checkUser(User $user, string $permission, User $target): bool
    {
        if ($this->getPermissionLevel($user, $permission) === Table::LEVEL_ALL) {
            return true;
        }

        if ($this->getPermissionLevel($user, $permission) === Table::LEVEL_NO) {
            if ($target->getId() === $user->getId()) {
                return true;
            }

            return false;
        }

        if ($this->get($user, $permission) === Table::LEVEL_TEAM) {
            if ($target->getId() === $user->getId()) {
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
}
