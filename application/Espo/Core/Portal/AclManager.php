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

namespace Espo\Core\Portal;

use Espo\Core\Acl\Permission;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Entities\Portal;
use Espo\Entities\User;

use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\Acl\Map\Map;
use Espo\Core\Acl\OwnerUserFieldProvider;
use Espo\Core\Acl\Table;
use Espo\Core\AclManager as InternalAclManager;
use Espo\Core\Portal\Acl\AccessChecker\AccessCheckerFactory as PortalAccessCheckerFactory;
use Espo\Core\Portal\Acl\Map\MapFactory as PortalMapFactory;
use Espo\Core\Portal\Acl\OwnershipAccountChecker;
use Espo\Core\Portal\Acl\OwnershipChecker\OwnershipCheckerFactory as PortalOwnershipCheckerFactory;
use Espo\Core\Portal\Acl\OwnershipContactChecker;
use Espo\Core\Portal\Acl\Table as PortalTable;
use Espo\Core\Portal\Acl\Table\TableFactory as PortalTableFactory;

use stdClass;
use RuntimeException;

class AclManager extends InternalAclManager
{
    /** @var class-string */
    protected $userAclClassName = Acl::class;

    private InternalAclManager $internalAclManager;
    private ?Portal $portal = null;
    private PortalTableFactory $portalTableFactory;
    private PortalMapFactory $portalMapFactory;

    public function __construct(
        PortalAccessCheckerFactory $accessCheckerFactory,
        PortalOwnershipCheckerFactory $ownershipCheckerFactory,
        PortalTableFactory $portalTableFactory,
        PortalMapFactory $portalMapFactory,
        GlobalRestriction $globalRestriction,
        OwnerUserFieldProvider $ownerUserFieldProvider,
        EntityManager $entityManager,
        InternalAclManager $internalAclManager
    ) {
        $this->accessCheckerFactory = $accessCheckerFactory;
        $this->ownershipCheckerFactory = $ownershipCheckerFactory;
        $this->portalTableFactory = $portalTableFactory;
        $this->portalMapFactory = $portalMapFactory;
        $this->globalRestriction = $globalRestriction;
        $this->ownerUserFieldProvider = $ownerUserFieldProvider;
        $this->entityManager = $entityManager;
        $this->internalAclManager = $internalAclManager;
    }

    public function setPortal(Portal $portal): void
    {
        $this->portal = $portal;
    }

    protected function getPortal(): Portal
    {
        if (!$this->portal) {
            throw new RuntimeException("Portal is not set.");
        }

        return $this->portal;
    }

    protected function getTable(User $user): Table
    {
        $key = $user->hasId() ? $user->getId() : spl_object_hash($user);

        if (!array_key_exists($key, $this->tableHashMap)) {
            $this->tableHashMap[$key] = $this->portalTableFactory->create($user, $this->getPortal());
        }

        return $this->tableHashMap[$key];
    }

    protected function getMap(User $user): Map
    {
        $key = $user->hasId() ? $user->getId() : spl_object_hash($user);

        if (!array_key_exists($key, $this->mapHashMap)) {
            /** @var PortalTable $table */
            $table = $this->getTable($user);

            $this->mapHashMap[$key] = $this->portalMapFactory->create($user, $table, $this->getPortal());
        }

        return $this->mapHashMap[$key];
    }

    public function getMapData(User $user): stdClass
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->getMapData($user);
        }

        return parent::getMapData($user);
    }

    public function getLevel(User $user, string $scope, string $action): string
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->getLevel($user, $scope, $action);
        }

        return parent::getLevel($user, $scope, $action);
    }

    public function getPermissionLevel(User $user, string $permission): string
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->getPermissionLevel($user, $permission);
        }

        return parent::getPermissionLevel($user, $permission);
    }

    public function checkReadOnlyTeam(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkReadOnlyTeam($user, $scope);
        }

        return false;
    }

    public function checkReadNo(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkReadNo($user, $scope);
        }

        return parent::checkReadNo($user, $scope);
    }

    public function checkReadOnlyOwn(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkReadOnlyOwn($user, $scope);
        }

        return parent::checkReadOnlyOwn($user, $scope);
    }

    public function checkReadAll(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkReadAll($user, $scope);
        }

        return parent::checkReadAll($user, $scope);
    }

    /**
     * Whether 'read' access is set to 'account' for a specific scope.
     */
    public function checkReadOnlyAccount(User $user, string $scope): bool
    {
        return $this->getLevel($user, $scope, Table::ACTION_READ) === PortalTable::LEVEL_ACCOUNT;
    }

    /**
     * Whether 'read' access is set to 'contact' for a specific scope.
     */
    public function checkReadOnlyContact(User $user, string $scope): bool
    {
        return $this->getLevel($user, $scope, Table::ACTION_READ)=== PortalTable::LEVEL_CONTACT;
    }

    public function check(User $user, $subject, ?string $action = null): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->check($user, $subject, $action);
        }

        return parent::check($user, $subject, $action);
    }

    public function checkEntity(User $user, Entity $entity, string $action = Table::ACTION_READ): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkEntity($user, $entity, $action);
        }

        return parent::checkEntity($user, $entity, $action);
    }

    public function checkUserPermission(User $user, $target, string $permissionType = Permission::USER): bool
    {
        return $this->internalAclManager->checkUserPermission($user, $target, $permissionType);
    }

    public function checkOwnershipOwn(User $user, Entity $entity): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkOwnershipOwn($user, $entity);
        }

        return parent::checkOwnershipOwn($user, $entity);
    }

    public function checkOwnershipShared(User $user, Entity $entity, string $action): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkOwnershipShared($user, $entity, $action);
        }

        return false;
    }

    public function checkOwnershipTeam(User $user, Entity $entity): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkOwnershipTeam($user, $entity);
        }

        return false;
    }

    /**
     * Check whether an entity belongs to a user account.
     */
    public function checkOwnershipAccount(User $user, Entity $entity): bool
    {
        $checker = $this->getOwnershipChecker($entity->getEntityType());

        if (!$checker instanceof OwnershipAccountChecker) {
            return false;
        }

        return $checker->checkAccount($user, $entity);
    }

    /**
     * Check whether an entity belongs to a user account.
     */
    public function checkOwnershipContact(User $user, Entity $entity): bool
    {
        $checker = $this->getOwnershipChecker($entity->getEntityType());

        if (!$checker instanceof OwnershipContactChecker) {
            return false;
        }

        return $checker->checkContact($user, $entity);
    }

    public function checkScope(User $user, string $scope, ?string $action = null): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkScope($user, $scope, $action);
        }

        return parent::checkScope($user, $scope, $action);
    }

    public function checkUser(User $user, string $permission, User $entity): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager->checkUser($user, $permission, $entity);
        }

        return parent::checkUser($user, $permission, $entity);
    }

    public function getScopeForbiddenAttributeList(
        User $user,
        string $scope,
        string $action = PortalTable::ACTION_READ,
        string $thresholdLevel = PortalTable::LEVEL_NO
    ): array {

        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager
                ->getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
        }

        return parent::getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
    }

    public function getScopeForbiddenFieldList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        if ($this->checkUserIsNotPortal($user)) {
            return $this->internalAclManager
                ->getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
        }

        return parent::getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
    }

    protected function checkUserIsNotPortal(User $user): bool
    {
        return !$user->isPortal();
    }

    /**
     * @deprecated As of v6.0. Use `getPermissionLevel` instead.
     */
    public function get(User $user, string $permission): string
    {
        return $this->getPermissionLevel($user, $permission);
    }
}
