<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

    private $implementationHashMap = array();

    private $tableHashMap = array();

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->metadata = $container->get('metadata');
    }

    protected function getContainer()
    {
        return $this->container;
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
                $acl = new $className();
                $dependencies = $acl->getDependencyList();
                foreach ($dependencies as $name) {
                    $acl->inject($name, $this->container->get($name));
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
        $key = spl_object_hash($user);

        if (empty($this->tableHashMap[$key])) {
            $config = $this->getContainer()->get('config');
            $fileManager = $this->getContainer()->get('fileManager');
            $metadata = $this->getContainer()->get('metadata');

            $this->tableHashMap[$key] = new \Espo\Core\Acl\Table($user, $config, $fileManager, $metadata);
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
            return 'all';
        }
        return $this->getTable($user)->getLevel($scope, $action);
    }

    public function get(User $user, $permission)
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $this->getTable($user)->get($permission);
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

    public function check(User $user, $subject, $action = null, $isOwner = null, $inTeam = null)
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (is_string($subject)) {
            return $this->checkScope($user, $subject, $action, $isOwner, $inTeam);
        } else {
            $entity = $subject;
            if ($entity instanceof Entity) {
                $entityType = $entity->getEntityType();

                $impl = $this->getImplementation($entityType);
                $methodName = 'checkEntity' . ucfirst($action);
                if (method_exists($impl, $methodName)) {
                    $data = $this->getTable($user)->getScopeData($entityType);
                    return $impl->$methodName($user, $entity, $data);
                }

                return $this->checkEntity($user, $entity, $action);
            }
        }
    }

    public function checkEntity(User $user, Entity $entity, $action)
    {
        if ($user->isAdmin()) {
            return true;
        }
        $data = $this->getTable($user)->getScopeData($entity->getEntityType());
        return $this->getImplementation($entity->getEntityType())->checkEntity($user, $entity, $data, $action);
    }

    public function checkScope(User $user, $scope, $action = null, $isOwner = null, $inTeam = null, $entity = null)
    {
        if ($user->isAdmin()) {
            return true;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkScope($user, $data, $scope, $action, $isOwner, $inTeam, $entity);
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
}

