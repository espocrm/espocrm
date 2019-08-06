<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\Error;

use \Espo\ORM\Entity;
use \Espo\Entities\User;
use \Espo\Core\Utils\Util;

class AclManager
{
    private $container;

    private $metadata;

    private $implementationHashMap = [];

    private $tableHashMap = [];

    protected $tableClassName = '\\Espo\\Core\\Acl\\Table';

    protected $userAclClassName = '\\Espo\\Core\\Acl';

    protected $globalRestricton;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->metadata = $container->get('metadata');

        $this->globalRestricton = new \Espo\Core\Acl\GlobalRestricton(
            $container->get('metadata'),
            $container->get('fileManager'),
            $container->get('fieldManagerUtil'),
            $container->get('config')->get('useCache')
        );
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    public function getImplementation($scope)
    {
        if (empty($this->implementationHashMap[$scope])) {
            $normalizedName = Util::normilizeClassName($scope);

            $className = '\\Espo\\Custom\\Acl\\' . $normalizedName;
            if (!class_exists($className)) {
                $moduleName = $this->metadata->getScopeModuleName($scope);
                if ($moduleName) {
                    $className = '\\Espo\\Modules\\' . $moduleName . '\\Acl\\' . $normalizedName;
                } else {
                    $className = '\\Espo\\Acl\\' . $normalizedName;
                }
                if (!class_exists($className)) {
                    $className = '\\Espo\\Core\\Acl\\Base';
                }
            }

            if (class_exists($className)) {
                $acl = new $className($scope);
                $dependencyList = $acl->getDependencyList();
                foreach ($dependencyList as $name) {
                    $acl->inject($name, $this->getContainer()->get($name));
                }
                $this->implementationHashMap[$scope] = $acl;
            } else {
                throw new Error();
            }
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
            $config = $this->getContainer()->get('config');
            $fileManager = $this->getContainer()->get('fileManager');
            $metadata = $this->getContainer()->get('metadata');
            $fieldManager = $this->getContainer()->get('fieldManagerUtil');

            $this->tableHashMap[$key] = new $this->tableClassName($user, $config, $fileManager, $metadata, $fieldManager);
        }

        return $this->tableHashMap[$key];
    }

    public function getMap(User $user)
    {
        return $this->getTable($user)->getMap();
    }

    public function getLevel(User $user, $scope, $action)
    {
        if ($user->isAdmin()) {
            return $this->getTable($user)->getHighestLevel($action);
        }
        return $this->getTable($user)->getLevel($scope, $action);
    }

    public function get(User $user, $permission)
    {
        return $this->getTable($user)->get($permission);
    }

    public function checkReadNo(User $user, $scope)
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadNo($user, $data);
    }

    public function checkReadOnlyTeam(User $user, $scope)
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadOnlyTeam($user, $data);
    }

    public function checkReadOnlyOwn(User $user, $scope)
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadOnlyOwn($user, $data);
    }

    public function check(User $user, $subject, $action = null)
    {
        if (is_string($subject)) {
            return $this->checkScope($user, $subject, $action);
        } else {
            $entity = $subject;
            if ($entity instanceof Entity) {
                return $this->checkEntity($user, $entity, $action);
            }
        }
    }

    public function checkEntity(User $user, Entity $entity, $action = 'read')
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
                return $impl->$methodName($user, $entity, $data);
            }
        }

        return $impl->checkEntity($user, $entity, $data, $action);
    }

    public function checkIsOwner(User $user, Entity $entity)
    {
        return $this->getImplementation($entity->getEntityType())->checkIsOwner($user, $entity);
    }

    public function checkInTeam(User $user, Entity $entity)
    {
        return $this->getImplementation($entity->getEntityType())->checkInTeam($user, $entity);
    }

    public function checkScope(User $user, $scope, $action = null)
    {
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkScope($user, $data, $action);
    }

    public function checkUser(User $user, $permission, User $entity)
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

    protected function getGlobalRestrictionTypeList(User $user, $action = 'read')
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

    public function getScopeForbiddenAttributeList(User $user, $scope, $action = 'read', $thresholdLevel = 'no')
    {
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

    public function getScopeForbiddenFieldList(User $user, $scope, $action = 'read', $thresholdLevel = 'no')
    {
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


    public function getScopeForbiddenLinkList(User $user, $scope, $action = 'read', $thresholdLevel = 'no')
    {
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

    public function checkUserPermission(User $user, $target, $permissionType = 'userPermission')
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
            if (!$this->getContainer()->get('entityManager')->getRepository('User')->checkBelongsToAnyOfTeams($userId, $teamIdList)) {
                return false;
            }
        }

        return true;
    }

    public function checkAssignmentPermission(User $user, $target)
    {
        return $this->checkUserPermission($user, $target, 'assignmentPermission');
    }

    public function createUserAcl(User $user)
    {
        $className = $this->userAclClassName;
        $acl = new $className($this, $user);
        return $acl;
    }

    public function getScopeRestrictedFieldList($scope, $type)
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

    public function getScopeRestrictedAttributeList($scope, $type)
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

    public function getScopeRestrictedLinkList($scope, $type)
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
