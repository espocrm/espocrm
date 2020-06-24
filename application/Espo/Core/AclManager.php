<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Error;

use Espo\ORM\Entity;
use Espo\Entities\User;

use Espo\Core\{
    Utils\ClassFinder,
    Utils\Config,
    ORM\EntityManager,
    Acl\Acl as BaseAcl,
    Acl\ScopeAcl,
    Acl\GlobalRestricton,
    Acl as UserAclWrapper,
    Acl\Table as AclTable,
    Utils\Util,
};

/**
 * Used to check access for a specific user.
 */
class AclManager
{
    private $implementationHashMap = [];

    private $tableHashMap = [];

    protected $tableClassName = AclTable::class;

    protected $userAclClassName = UserAclWrapper::class;

    protected $baseImplementationClassName = BaseAcl::class;

    protected $globalRestricton;

    protected $injectableFactory;
    protected $classFinder;
    protected $config;
    protected $entityManager;

    public function __construct(
        InjectableFactory $injectableFactory, ClassFinder $classFinder, Config $config, EntityManager $entityManager
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->classFinder = $classFinder;
        $this->config = $config;
        $this->entityManager = $entityManager;

        $this->globalRestricton = $this->injectableFactory->createWith(GlobalRestricton::class, [
            'useCache' => $config->get('useCache'),
        ]);
    }

    public function getImplementation(string $scope) : ScopeAcl
    {
        if (empty($this->implementationHashMap[$scope])) {
            $className = $this->classFinder->find('Acl', $scope);

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
        }

        return $this->implementationHashMap[$scope];
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
            ]);
        }

        return $this->tableHashMap[$key];
    }

    public function getMap(User $user) : object
    {
        return $this->getTable($user)->getMap();
    }

    public function getLevel(User $user, string $scope, string $action) : string
    {
        if ($user->isAdmin()) {
            return $this->getTable($user)->getHighestLevel($scope, $action);
        }
        return $this->getTable($user)->getLevel($scope, $action);
    }

    public function get(User $user, string $permission) : ?string
    {
        return $this->getTable($user)->get($permission);
    }

    public function checkReadNo(User $user, string $scope) : bool
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return (bool) $this->getImplementation($scope)->checkReadNo($user, $data);
    }

    public function checkReadOnlyTeam(User $user, string $scope) : bool
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return (bool) $this->getImplementation($scope)->checkReadOnlyTeam($user, $data);
    }

    public function checkReadOnlyOwn(User $user, string $scope) : bool
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return (bool) $this->getImplementation($scope)->checkReadOnlyOwn($user, $data);
    }

    public function check(User $user, $subject, ?string $action = null) : bool
    {
        if (is_string($subject)) {
            return $this->checkScope($user, $subject, $action);
        } else {
            $entity = $subject;
            if ($entity instanceof Entity) {
                return $this->checkEntity($user, $entity, $action);
            }
        }

        return false;
    }

    public function checkEntity(User $user, Entity $entity, string $action = 'read') : bool
    {
        $scope = $entity->getEntityType();

        $data = $this->getTable($user)->getScopeData($scope);

        $impl = $this->getImplementation($scope);

        if (!$action) {
            $action = 'read';
        }

        if ($action) {
            $methodName = 'checkEntity' . ucfirst($action);
            if (method_exists($impl, $methodName)) {
                return (bool) $impl->$methodName($user, $entity, $data);
            }
        }

        return (bool) $impl->checkEntity($user, $entity, $data, $action);
    }

    public function checkIsOwner(User $user, Entity $entity) : bool
    {
        return (bool) $this->getImplementation($entity->getEntityType())->checkIsOwner($user, $entity);
    }

    public function checkInTeam(User $user, Entity $entity) : bool
    {
        return (bool) $this->getImplementation($entity->getEntityType())->checkInTeam($user, $entity);
    }

    public function checkScope(User $user, string $scope, ?string $action = null) : bool
    {
        $data = $this->getTable($user)->getScopeData($scope);
        return (bool) $this->getImplementation($scope)->checkScope($user, $data, $action);
    }

    public function checkUser(User $user, string $permission, User $entity) : bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($this->get($user, $permission) === 'no') {
            if ($entity->id !== $user->id) {
                return false;
            }
        } else if ($this->get($user, $permission) === 'team') {
            if ($entity->id != $user->id) {
                $teamIdList1 = $user->getTeamIdList();
                $teamIdList2 = $entity->getTeamIdList();

                $inTeam = false;
                foreach ($teamIdList1 as $id) {
                    if (in_array($id, $teamIdList2)) {
                        $inTeam = true;
                        break;
                    }
                }
                if (!$inTeam) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function getGlobalRestrictionTypeList(User $user, string $action = 'read') : array
    {
        $typeList = ['forbidden'];

        if ($action === 'read') {
            $typeList[] = 'internal';
        }

        if (!$user->isAdmin()) {
            $typeList[] = 'onlyAdmin';
        }

        if ($action === 'edit') {
            $typeList[] = 'readOnly';
            if (!$user->isAdmin()) {
                $typeList[] = 'nonAdminReadOnly';
            }
        }

        return $typeList;
    }

    public function getScopeForbiddenAttributeList(
        User $user, string $scope, string $action = 'read', string $thresholdLevel = 'no'
    ) : array {
        $list = [];

        if (!$user->isAdmin()) {
            $list = $this->getTable($user)->getScopeForbiddenAttributeList($scope, $action, $thresholdLevel);
        }

        if ($thresholdLevel === 'no') {
            $list = array_merge(
                $list,
                $this->getScopeRestrictedAttributeList($scope, $this->getGlobalRestrictionTypeList($user, $action))
            );
            $list = array_values($list);
        }

        return $list;
    }

    public function getScopeForbiddenFieldList(
        User $user, string $scope, string $action = 'read', string $thresholdLevel = 'no'
    ) : array {
        $list = [];

        if (!$user->isAdmin()) {
            $list = $this->getTable($user)->getScopeForbiddenFieldList($scope, $action, $thresholdLevel);
        }

        if ($thresholdLevel === 'no') {
            $list = array_merge(
                $list,
                $this->getScopeRestrictedFieldList($scope, $this->getGlobalRestrictionTypeList($user, $action))
            );
            $list = array_values($list);
        }

        return $list;
    }


    public function getScopeForbiddenLinkList(
        User $user, string $scope, string $action = 'read', string $thresholdLevel = 'no'
    ) : array {
        $list = [];

        if ($thresholdLevel === 'no') {
            $list = array_merge(
                $list,
                $this->getScopeRestrictedLinkList($scope, $this->getGlobalRestrictionTypeList($user, $action))
            );
            $list = array_values($list);
        }

        return $list;
    }

    public function checkUserPermission(User $user, $target, string $permissionType = 'userPermission') : bool
    {
        $permission = $this->get($user, $permissionType);

        if (is_object($target)) {
            $userId = $target->id;
        } else {
            $userId = $target;
        }

        if ($user->id === $userId) return true;

        if ($permission === 'no') {
            return false;
        }

        if ($permission === 'yes') {
            return true;
        }

        if ($permission === 'team') {
            $teamIdList = $user->getLinkMultipleIdList('teams');
            if (!$this->entityManager->getRepository('User')->checkBelongsToAnyOfTeams($userId, $teamIdList)) {
                return false;
            }
        }

        return true;
    }

    public function checkAssignmentPermission(User $user, $target) : bool
    {
        return $this->checkUserPermission($user, $target, 'assignmentPermission');
    }

    public function createUserAcl(User $user) : UserAclWrapper
    {
        $className = $this->userAclClassName;
        $acl = new $className($this, $user);
        return $acl;
    }

    public function getScopeRestrictedFieldList(string $scope, $type) : array
    {
        if (is_array($type)) {
            $typeList = $type;
            $list = [];
            foreach ($typeList as $type) {
                $list = array_merge($list, $this->globalRestricton->getScopeRestrictedFieldList($scope, $type));
            }
            $list = array_values($list);
            return $list;
        }
        return $this->globalRestricton->getScopeRestrictedFieldList($scope, $type);
    }

    public function getScopeRestrictedAttributeList(string $scope, $type) : array
    {
        if (is_array($type)) {
            $typeList = $type;
            $list = [];
            foreach ($typeList as $type) {
                $list = array_merge($list, $this->globalRestricton->getScopeRestrictedAttributeList($scope, $type));
            }
            $list = array_values($list);
            return $list;
        }
        return $this->globalRestricton->getScopeRestrictedAttributeList($scope, $type);
    }

    public function getScopeRestrictedLinkList(string $scope, $type) : array
    {
        if (is_array($type)) {
            $typeList = $type;
            $list = [];
            foreach ($typeList as $type) {
                $list = array_merge($list, $this->globalRestricton->getScopeRestrictedLinkList($scope, $type));
            }
            $list = array_values($list);
            return $list;
        }
        return $this->globalRestricton->getScopeRestrictedLinkList($scope, $type);
    }
}
