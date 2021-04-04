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

namespace Espo\Core\Portal;

use Espo\ORM\Entity;

use Espo\Entities\{
    User,
    Portal,
};

use Espo\Core\{
    AclPortal\Table as Table,
    AclPortal\Acl as BasePortalAcl,
    AclPortal\AclFactory as PortalAclFactory,
    Acl\ScopeAcl,
    Acl\GlobalRestrictonFactory,
    Acl\OwnerUserFieldProvider,
    Acl\Table as TableBase,
    Portal\Acl as UserAclWrapper,
    AclManager as BaseAclManager,
    InjectableFactory,
    ORM\EntityManager,
};

use StdClass;
use RuntimeException;

class AclManager extends BaseAclManager
{
    protected $tableClassName = Table::class;

    protected $userAclClassName = UserAclWrapper::class;

    protected $baseImplementationClassName = BasePortalAcl::class;

    private $mainManager = null;

    private $portal = null;

    public function __construct(
        InjectableFactory $injectableFactory,
        EntityManager $entityManager,
        PortalAclFactory $aclFactory,
        GlobalRestrictonFactory $globalRestrictonFactory,
        OwnerUserFieldProvider $ownerUserFieldProvider,
        BaseAclManager $mainManager
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->entityManager = $entityManager;
        $this->aclFactory = $aclFactory;
        $this->ownerUserFieldProvider = $ownerUserFieldProvider;
        $this->mainManager = $mainManager;

        $this->globalRestricton = $globalRestrictonFactory->create();
    }

    public function getImplementation(string $scope): ScopeAcl
    {
        if (!array_key_exists($scope, $this->implementationHashMap)) {
            $this->implementationHashMap[$scope] = $this->aclFactory->create($scope);
        }

        return $this->implementationHashMap[$scope];
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

    protected function getTable(User $user): TableBase
    {
        $key = $user->id;

        if (empty($key)) {
            $key = spl_object_hash($user);
        }

        if (empty($this->tableHashMap[$key])) {
            $this->tableHashMap[$key] = $this->injectableFactory->createWith($this->tableClassName, [
                'user' => $user,
                'portal' => $this->getPortal(),
            ]);
        }

        return $this->tableHashMap[$key];
    }

    public function checkReadOnlyAccount(User $user, string $scope): bool
    {
        $data = $this->getTable($user)->getScopeData($scope);

        $impl = $this->getEntityImplementation($scope);

        return $impl->getLevel($user, $data, Table::ACTION_READ) === Table::LEVEL_ACCOUNT;
    }

    public function checkReadOnlyContact(User $user, string $scope): bool
    {
        $data = $this->getTable($user)->getScopeData($scope);

        $impl = $this->getEntityImplementation($scope);

        return $impl->getLevel($user, $data, Table::ACTION_READ) === Table::LEVEL_CONTACT;
    }

    public function checkInAccount(User $user, Entity $entity): bool
    {
        return (bool) $this->getEntityImplementation($entity->getEntityType())->checkInAccount($user, $entity);
    }

    public function checkIsOwnContact(User $user, Entity $entity): bool
    {
        return (bool) $this->getEntityImplementation($entity->getEntityType())->checkIsOwnContact($user, $entity);
    }

    public function getMap(User $user): StdClass
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->getMap($user);
        }

        return parent::getMap($user);
    }

    public function getLevel(User $user, string $scope, string $action): string
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->getLevel($user, $scope, $action);
        }

        return parent::getLevel($user, $scope, $action);
    }

    public function get(User $user, string $permission): ?string
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->get($user, $permission);
        }

        return parent::get($user, $permission);
    }

    public function checkReadOnlyTeam(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkReadOnlyTeam($user, $scope);
        }

        return false;
    }

    public function checkReadNo(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkReadNo($user, $scope);
        }

        return parent::checkReadNo($user, $scope);
    }

    public function checkReadOnlyOwn(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkReadOnlyOwn($user, $scope);
        }

        return parent::checkReadOnlyOwn($user, $scope);
    }

    public function checkReadAll(User $user, string $scope): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkReadAll($user, $scope);
        }

        return parent::checkReadAll($user, $scope);
    }

    public function check(User $user, $subject, ?string $action = null): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->check($user, $subject, $action);
        }

        return parent::check($user, $subject, $action);
    }

    public function checkEntity(User $user, Entity $entity, string $action = Table::ACTION_READ): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkEntity($user, $entity, $action);
        }

        return parent::checkEntity($user, $entity, $action);
    }

    public function checkIsOwner(User $user, Entity $entity): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkIsOwner($user, $entity);
        }

        return parent::checkIsOwner($user, $entity);
    }

    public function checkInTeam(User $user, Entity $entity): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkInTeam($user, $entity);
        }

        return parent::checkInTeam($user, $entity);
    }

    public function checkScope(User $user, string $scope, ?string $action = null): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkScope($user, $scope, $action);
        }

        return parent::checkScope($user, $scope, $action);
    }

    public function checkUser(User $user, string $permission, User $entity): bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->checkUser($user, $permission, $entity);
        }

        return parent::checkUser($user, $permission, $entity);
    }

    public function getScopeForbiddenAttributeList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        if ($this->checkUserIsNotPortal($user)) {
            return $this->mainManager->getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
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
            return $this->mainManager->getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
        }

        return parent::getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
    }

    protected function checkUserIsNotPortal(User $user): bool
    {
        return !$user->isPortal();
    }
}
