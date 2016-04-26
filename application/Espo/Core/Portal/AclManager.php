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

namespace Espo\Core\Portal;

use \Espo\ORM\Entity;
use \Espo\Entities\User;
use \Espo\Core\Utils\Util;

class AclManager extends \Espo\Core\AclManager
{
    protected $tableClassName = '\\Espo\\Core\\AclPortal\\Table';

    public function getImplementation($scope)
    {
        if (empty($this->implementationHashMap[$scope])) {
            $normalizedName = Util::normilizeClassName($scope);

            $className = '\\Espo\\Custom\\AclPortal\\' . $normalizedName;
            if (!class_exists($className)) {
                $moduleName = $this->getMetadata()->getScopeModuleName($scope);
                if ($moduleName) {
                    $className = '\\Espo\\Modules\\' . $moduleName . '\\AclPortal\\' . $normalizedName;
                } else {
                    $className = '\\Espo\\AclPortal\\' . $normalizedName;
                }
                if (!class_exists($className)) {
                    $className = '\\Espo\\Core\\AclPortal\\Base';
                }
            }

            if (class_exists($className)) {
                $acl = new $className($scope);
                $dependencies = $acl->getDependencyList();
                foreach ($dependencies as $name) {
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
            $fieldManager = $this->getContainer()->get('fieldManager');
            $portal = $this->getContainer()->get('portal');

            $this->tableHashMap[$key] = new $this->tableClassName($user, $portal, $config, $fileManager, $metadata, $fieldManager);
        }

        return $this->tableHashMap[$key];
    }

    public function checkReadOnlyAccount(User $user, $scope)
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadOnlyAccount($user, $data);
    }

    public function checkReadOnlyContact(User $user, $scope)
    {
        if ($user->isAdmin()) {
            return false;
        }
        $data = $this->getTable($user)->getScopeData($scope);
        return $this->getImplementation($scope)->checkReadOnlyContact($user, $data);
    }

    public function checkInAccount(User $user, Entity $entity, $action)
    {
        return $this->getImplementation($entity->getEntityType())->checkInAccount($user, $entity);
    }

    public function checkIsOwnContact(User $user, Entity $entity, $action)
    {
        return $this->getImplementation($entity->getEntityType())->checkIsOwnContact($user, $entity);
    }

}

