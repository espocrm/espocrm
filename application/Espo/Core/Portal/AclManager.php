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
use Espo\Entities\User;
use Espo\Core\Utils\Util;

use Espo\Entities\Portal;

use Espo\Core\{
    AclPortal\Table as AclPortalTable,
    AclPortal\Acl as BasePortalAcl,
    AclPortal\PortalScopeAcl,
    Acl\ScopeAcl,
    Portal\Acl as UserAclWrapper,
    AclManager as BaseAclManager,
};

use Espo\Core\Exceptions\Error;

class AclManager extends BaseAclManager
{
    protected $tableClassName = AclPortalTable::class;

    private $mainManager = null;

    private $portal = null;

    protected $userAclClassName = UserAclWrapper::class;

    protected $baseImplementationClassName = BasePortalAcl::class;

    public function getImplementation(string $scope) : ScopeAcl
    {
        if (empty($this->implementationHashMap[$scope])) {
            $className = $this->classFinder->find('AclPortal', $scope);

            if (!$className) {
                $className = $this->baseImplementationClassName;
            }

            if (!class_exists($className)) {
                throw new Error("{$className} does not exist.");
            }

            $acl = $this->injectableFactory->createWith($className, [
                'scope' => $scope,
            ]);

            $this->implementationHashMap[$scope] = $acl;

            if (!$acl instanceof PortalScopeAcl) {
                throw new Error("Portal\AclManager: Implementation should be instance of PortalScopeAcl.");
            }
        }

        return $this->implementationHashMap[$scope];
    }

    public function setMainManager(BaseAclManager $mainManager)
    {
        $this->mainManager = $mainManager;
    }

    protected function getMainManager()
    {
        return $this->mainManager;
    }

    public function setPortal(Portal $portal)
    {
        $this->portal = $portal;
    }

    protected function getPortal() : Portal
    {
        return $this->portal ?? null;
    }

    protected function getTable(User $user)
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

    public function checkReadOnlyAccount(User $user, string $scope) : bool
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadOnlyAccount($user, $data);
    }

    public function checkReadOnlyContact(User $user, string $scope) : bool
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadOnlyContact($user, $data);
    }

    public function checkInAccount(User $user, Entity $entity) : bool
    {
        return (bool) $this->getImplementation($entity->getEntityType())->checkInAccount($user, $entity);
    }

    public function checkIsOwnContact(User $user, Entity $entity) : bool
    {
        return (bool) $this->getImplementation($entity->getEntityType())->checkIsOwnContact($user, $entity);
    }

    public function getMap(User $user) : \StdClass
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getMap($user);
        }
        return parent::getMap($user);
    }

    public function getLevel(User $user, string $scope, string $action) : string
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getLevel($user, $scope, $action);
        }
        return parent::getLevel($user, $scope, $action);
    }

    public function get(User $user, string $permission) : ?string
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->get($user, $permission);
        }
        return parent::get($user, $permission);
    }

    public function checkReadOnlyTeam(User $user, string $scope) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            $data = $this->getTable($user)->getScopeData($scope);
            return $this->getMainManager()->checkReadOnlyTeam($user, $data);
        }
        return parent::checkReadOnlyTeam($user, $scope);
    }

    public function checkReadNo(User $user, string $scope) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            $data = $this->getTable($user)->getScopeData($scope);
            return $this->getMainManager()->checkReadNo($user, $data);
        }
        return parent::checkReadNo($user, $scope);
    }

    public function checkReadOnlyOwn(User $user, string $scope) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            $data = $this->getTable($user)->getScopeData($scope);
            return $this->getMainManager()->checkReadOnlyOwn($user, $data);
        }
        return parent::checkReadOnlyOwn($user, $scope);
    }

    public function check(User $user, $subject, ?string $action = null) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->check($user, $subject, $action);
        }
        return parent::check($user, $subject, $action);
    }

    public function checkEntity(User $user, Entity $entity, string $action = 'read') : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkEntity($user, $entity, $action);
        }
        return parent::checkEntity($user, $entity, $action);
    }

    public function checkIsOwner(User $user, Entity $entity) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkIsOwner($user, $entity);
        }
        return parent::checkIsOwner($user, $entity);
    }

    public function checkInTeam(User $user, Entity $entity) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkInTeam($user, $entity);
        }
        return parent::checkInTeam($user, $entity);
    }

    public function checkScope(User $user, string $scope, ?string $action = null) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkScope($user, $scope, $action);
        }
        return parent::checkScope($user, $scope, $action);
    }

    public function checkUser(User $user, string $permission, User $entity) : bool
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkUser($user, $permission, $entity);
        }
        return parent::checkUser($user, $permission, $entity);
    }

    public function getScopeForbiddenAttributeList(
        User $user, string $scope, string $action = 'read', string $thresholdLevel = 'no'
    ) : array {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
        }
        return parent::getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
    }

    public function getScopeForbiddenFieldList(
        User $user, string $scope, string $action = 'read', string $thresholdLevel = 'no'
    ) : array {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
        }
        return parent::getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
    }

    protected function checkUserIsNotPortal(User $user) : bool
    {
        return !$user->isPortal();
    }
}
