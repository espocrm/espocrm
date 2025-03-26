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

use Espo\Core\Acl\OwnershipSharedChecker;
use Espo\Core\Acl\Permission;
use Espo\Core\Name\Field;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Core\Acl\AccessChecker;
use Espo\Core\Acl\AccessChecker\AccessCheckerFactory;
use Espo\Core\Acl\AccessCreateChecker;
use Espo\Core\Acl\AccessDeleteChecker;
use Espo\Core\Acl\AccessEditChecker;
use Espo\Core\Acl\AccessEntityCreateChecker;
use Espo\Core\Acl\AccessEntityDeleteChecker;
use Espo\Core\Acl\AccessEntityEditChecker;
use Espo\Core\Acl\AccessEntityReadChecker;
use Espo\Core\Acl\AccessEntityStreamChecker;
use Espo\Core\Acl\AccessReadChecker;
use Espo\Core\Acl\AccessStreamChecker;
use Espo\Core\Acl\Exceptions\NotImplemented;
use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\Acl\Map\Map;
use Espo\Core\Acl\Map\MapFactory;
use Espo\Core\Acl\OwnershipChecker;
use Espo\Core\Acl\OwnershipChecker\OwnershipCheckerFactory;
use Espo\Core\Acl\OwnershipOwnChecker;
use Espo\Core\Acl\OwnershipTeamChecker;
use Espo\Core\Acl\OwnerUserFieldProvider;
use Espo\Core\Acl\Table;
use Espo\Core\Acl\Table\TableFactory;

use stdClass;
use InvalidArgumentException;

/**
 * A central access point for access checking.
 *
 * @todo Refactor. Replace with an interface `Espo\Core\Acl\AclManager`.
 * Keep `Espo\Core\AclManager` as an extending interface for bc, bind it to the service.
 * Implementation in `Espo\Core\Acl\DefaultAclManager`.
 * The same for `Portal\AclManager`.
 */
class AclManager
{
    protected const PERMISSION_ASSIGNMENT = Permission::ASSIGNMENT;

    /** @var array<string, AccessChecker> */
    private $accessCheckerHashMap = [];
    /** @var array<string, OwnershipChecker> */
    private $ownershipCheckerHashMap = [];
    /** @var array<string, Table> */
    protected $tableHashMap = [];
    /** @var array<string, Map> */
    protected $mapHashMap = [];
    /** @var class-string */
    protected $userAclClassName = Acl::class;

    /** @var array<string, class-string<AccessChecker>> */
    private $entityActionInterfaceMap = [
        Table::ACTION_CREATE => AccessEntityCreateChecker::class,
        Table::ACTION_READ => AccessEntityReadChecker::class,
        Table::ACTION_EDIT => AccessEntityEditChecker::class,
        Table::ACTION_DELETE => AccessEntityDeleteChecker::class,
        Table::ACTION_STREAM => AccessEntityStreamChecker::class,
    ];
    /** @var array<string, class-string<AccessChecker>> */
    private $actionInterfaceMap = [
        Table::ACTION_CREATE => AccessCreateChecker::class,
        Table::ACTION_READ => AccessReadChecker::class,
        Table::ACTION_EDIT => AccessEditChecker::class,
        Table::ACTION_DELETE => AccessDeleteChecker::class,
        Table::ACTION_STREAM => AccessStreamChecker::class,
    ];

    /** @var AccessCheckerFactory|Portal\Acl\AccessChecker\AccessCheckerFactory */
    protected $accessCheckerFactory;
    /** @var OwnershipCheckerFactory|Portal\Acl\OwnershipChecker\OwnershipCheckerFactory */
    protected $ownershipCheckerFactory;

    /** @var TableFactory  */
    private $tableFactory;
    /** @var MapFactory  */
    private $mapFactory;

    public function __construct(
        AccessCheckerFactory $accessCheckerFactory,
        OwnershipCheckerFactory $ownershipCheckerFactory,
        TableFactory $tableFactory,
        MapFactory $mapFactory,
        protected GlobalRestriction $globalRestriction,
        protected OwnerUserFieldProvider $ownerUserFieldProvider,
        protected EntityManager $entityManager
    ) {
        $this->accessCheckerFactory = $accessCheckerFactory;
        $this->ownershipCheckerFactory = $ownershipCheckerFactory;
        $this->tableFactory = $tableFactory;
        $this->mapFactory = $mapFactory;
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
        $key = $user->hasId() ? $user->getId() : spl_object_hash($user);

        if (!array_key_exists($key, $this->tableHashMap)) {
            $this->tableHashMap[$key] = $this->tableFactory->create($user);
        }

        return $this->tableHashMap[$key];
    }

    protected function getMap(User $user): Map
    {
        $key = $user->hasId() ? $user->getId() : spl_object_hash($user);

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
     *
     * @param Table::ACTION_* $action
     * @noinspection PhpDocSignatureInspection
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
        if (str_ends_with($permission, 'Permission')) {
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
     * @param Table::ACTION_*|null $action $action Action to check. Constants are available in the `Table` class.
     * @throws NotImplemented
     * @noinspection PhpDocSignatureInspection
     */
    public function check(User $user, $subject, ?string $action = null): bool
    {
        if (is_string($subject)) {
            return $this->checkScope($user, $subject, $action);
        }

        /** @var mixed $entity */
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
     * @param Table::ACTION_*|null $action Action to check. Constants are available in the `Table` class.
     * @noinspection PhpDocSignatureInspection
     */
    public function tryCheck(User $user, $subject, ?string $action = null): bool
    {
        try {
            return $this->check($user, $subject, $action);
        } catch (NotImplemented) {
            return false;
        }
    }

    /**
     * Check access to a specific entity.
     *
     * @param User $user A user to check for.
     * @param Entity $entity An entity to check.
     * @param Table::ACTION_* $action Action to check. Constants are available in the `Table` class.
     * @throws NotImplemented
     * @noinspection PhpDocSignatureInspection
     */
    public function checkEntity(User $user, Entity $entity, string $action = Table::ACTION_READ): bool
    {
        $scope = $entity->getEntityType();

        if (!$this->checkScope($user, $scope, $action)) {
            return false;
        }

        $data = $this->getTable($user)->getScopeData($scope);

        $checker = $this->getAccessChecker($scope);

        /** @var non-falsy-string $methodName */
        $methodName = 'checkEntity' . ucfirst($action);

        $interface = $this->entityActionInterfaceMap[$action] ?? null;

        if ($interface && $checker instanceof $interface && method_exists($checker, $methodName)) {
            return $checker->$methodName($user, $entity, $data);
        }

        throw new NotImplemented("No entity access checker for '$scope' action '$action'.");
    }

    /**
     * Check 'read' access to a specific entity.
     *
     * @throws NotImplemented
     */
    public function checkEntityRead(User $user, Entity $entity): bool
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
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
     * Check whether an entity is shared with a user.
     *
     * @param Table::ACTION_* $action
     * @since 9.0.0
     * @noinspection PhpDocSignatureInspection
     */
    public function checkOwnershipShared(User $user, Entity $entity, string $action): bool
    {
        $checker = $this->getOwnershipChecker($entity->getEntityType());

        if (!$checker instanceof OwnershipSharedChecker) {
            return false;
        }

        return $checker->checkShared($user, $entity, $action);
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
            throw new NotImplemented("No access checker for '$scope' action '$action'.");
        }

        return $checker->$methodName($user, $data, $action);
    }

    /**
     * @return array<int,GlobalRestriction::TYPE_*>
     */
    protected function getGlobalRestrictionTypeList(User $user, string $action = Table::ACTION_READ): array
    {
        $typeList = [
            GlobalRestriction::TYPE_FORBIDDEN,
        ];

        if ($action === Table::ACTION_READ) {
            $typeList[] = GlobalRestriction::TYPE_INTERNAL;
        }

        if (!$user->isAdmin()) {
            $typeList[] = GlobalRestriction::TYPE_ONLY_ADMIN;
        }

        if ($action === Table::ACTION_EDIT) {
            $typeList[] = GlobalRestriction::TYPE_READ_ONLY;

            if (!$user->isAdmin()) {
                $typeList[] = GlobalRestriction::TYPE_NON_ADMIN_READ_ONLY;
            }
        }

        return $typeList;
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
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     * @noinspection PhpDocSignatureInspection
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
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @param string $thresholdLevel Should not be used. Stands for possible future enhancements.
     * @return string[]
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpDocSignatureInspection
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
     * Check access to a field.
     *
     * @param User $user A user.
     * @param string $scope A scope (entity type).
     * @param string $field A field to check.
     * @param Table::ACTION_READ|Table::ACTION_EDIT $action An action.
     * @return bool
     * @noinspection PhpDocSignatureInspection
     */
    public function checkField(User $user, string $scope, string $field, string $action = Table::ACTION_READ): bool
    {
        return !in_array($field, $this->getScopeForbiddenFieldList($user, $scope, $action));
    }

    /**
     * Whether a user has access to another user over a specific permission.
     *
     * @param User|string $target User entity or user ID.
     */
    public function checkUserPermission(User $user, $target, string $permissionType = Permission::USER): bool
    {
        $permission = $this->getPermissionLevel($user, $permissionType);

        if (is_object($target)) {
            $userId = $target->getId();
        } else {
            $userId = $target;
        }

        if ($user->getId() === $userId) {
            return true;
        }

        if ($permission === Table::LEVEL_NO) {
            return false;
        }

        if ($permission === Table::LEVEL_YES) {
            return true;
        }

        if ($permission === Table::LEVEL_TEAM) {
            $teamIdList = $user->getLinkMultipleIdList(Field::TEAMS);

            /** @var \Espo\Repositories\User $userRepository */
            $userRepository = $this->entityManager->getRepository(User::ENTITY_TYPE);

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

        /** @var Acl */
        return new $className($this, $user);
    }

    /**
     * Get a restricted field list for a specific scope by a restriction type.
     *
     * @param GlobalRestriction::TYPE_*|array<int, GlobalRestriction::TYPE_*> $type
     * @return string[]
     */
    public function getScopeRestrictedFieldList(string $scope, $type): array
    {
        $typeList = !is_array($type) ? [$type] : $type;

        $list = [];

        foreach ($typeList as $type) {
            $list = array_merge(
                $list,
                $this->globalRestriction->getScopeRestrictedFieldList($scope, $type)
            );
        }

        return array_unique($list);
    }

    /**
     * Get a restricted attribute list for a specific scope by a restriction type.
     *
     * @param GlobalRestriction::TYPE_*|array<int, GlobalRestriction::TYPE_*> $type
     * @return string[]
     */
    public function getScopeRestrictedAttributeList(string $scope, $type): array
    {
        $typeList = !is_array($type) ? [$type] : $type;

        $list = [];

        foreach ($typeList as $type) {
            $list = array_merge(
                $list,
                $this->globalRestriction->getScopeRestrictedAttributeList($scope, $type)
            );
        }

        return array_unique($list);
    }

    /**
     * Get a restricted link list for a specific scope by a restriction type.
     *
     * @param GlobalRestriction::TYPE_*|array<int, GlobalRestriction::TYPE_*> $type
     * @return string[]
     */
    public function getScopeRestrictedLinkList(string $scope, $type): array
    {
        $typeList = !is_array($type) ? [$type] : $type;

        $list = [];

        foreach ($typeList as $type) {
            $list = array_merge(
                $list,
                $this->globalRestriction->getScopeRestrictedLinkList($scope, $type)
            );
        }

        return array_unique($list);
    }

    /**
     * Get an entity field that stores an owner-user (or multiple users).
     * Must be a link or linkMultiple field. NULL means no owner.
     */
    public function getReadOwnerUserField(string $entityType): ?string
    {
        return $this->ownerUserFieldProvider->get($entityType);
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

        if ($this->getPermissionLevel($user, $permission) === Table::LEVEL_TEAM) {
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
