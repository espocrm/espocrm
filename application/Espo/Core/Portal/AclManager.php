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

namespace Espo\Core\Portal;

use \Espo\ORM\Entity;
use \Espo\Entities\User;
use \Espo\Core\Utils\Util;

class AclManager extends \Espo\Core\AclManager
{
    protected $tableClassName = '\\Espo\\Core\\AclPortal\\Table';

    private $mainManager = null;

    private $portal = null;

    protected $userAclClassName = '\\Espo\\Core\\Portal\\Acl';

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

    public function setMainManager($mainManager)
    {
        $this->mainManager = $mainManager;
    }

    protected function getMainManager()
    {
        return $this->mainManager;
    }

    public function setPortal($portal)
    {
        $this->portal = $portal;
    }

    protected function getPortal()
    {
        if ($this->portal) {
            return $this->portal;
        }
        return $this->getContainer()->get('portal');
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
            $portal = $this->getPortal();

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

    public function getMap(User $user)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getMap($user);
        }
        return parent::getMap($user);
    }

    public function getLevel(User $user, $scope, $action)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getLevel($user, $scope, $action);
        }
        return parent::getLevel($user, $scope, $action);
    }

    public function get(User $user, $permission)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->get($user, $permission);
        }
        return parent::get($user, $permission);
    }

    public function checkReadOnlyTeam(User $user, $scope)
    {
        if ($this->checkUserIsNotPortal($user)) {
            $data = $this->getTable($user)->getScopeData($scope);
            return $this->getMainManager()->checkReadOnlyTeam($user, $data);
        }
        return parent::checkReadOnlyTeam($user, $scope);
    }

    public function checkReadNo(User $user, $scope)
    {
        if ($this->checkUserIsNotPortal($user)) {
            $data = $this->getTable($user)->getScopeData($scope);
            return $this->getMainManager()->checkReadNo($user, $data);
        }
        return parent::checkReadNo($user, $scope);
    }

    public function checkReadOnlyOwn(User $user, $scope)
    {
        if ($this->checkUserIsNotPortal($user)) {
            $data = $this->getTable($user)->getScopeData($scope);
            return $this->getMainManager()->checkReadOnlyOwn($user, $data);
        }
        return parent::checkReadOnlyOwn($user, $scope);
    }

    public function check(User $user, $subject, $action = null)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->check($user, $subject, $action);
        }
        return parent::check($user, $subject, $action);
    }

    public function checkEntity(User $user, Entity $entity, $action = 'read')
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkEntity($user, $entity, $action);
        }
        return parent::checkEntity($user, $entity, $action);
    }

    public function checkIsOwner(User $user, Entity $entity)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkIsOwner($user, $entity);
        }
        return parent::checkIsOwner($user, $entity);
    }

    public function checkInTeam(User $user, Entity $entity)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkInTeam($user, $entity);
        }
        return parent::checkInTeam($user, $entity);
    }

    public function checkScope(User $user, $scope, $action = null)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkScope($user, $scope, $action);
        }
        return parent::checkScope($user, $scope, $action);
    }

    public function checkUser(User $user, $permission, User $entity)
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->checkUser($user, $permission, $entity);
        }
        return parent::checkUser($user, $permission, $entity);
    }

    public function getScopeForbiddenAttributeList(User $user, $scope, $action = 'read', $thresholdLevel = 'no')
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
        }
        return parent::getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);
    }

    public function getScopeForbiddenFieldList(User $user, $scope, $action = 'read', $thresholdLevel = 'no')
    {
        if ($this->checkUserIsNotPortal($user)) {
            return $this->getMainManager()->getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
        }
        return parent::getScopeForbiddenFieldList($user, $scope, $action, $thresholdLevel);
    }

    protected function checkUserIsNotPortal($user)
    {
        return !$user->isPortal();
    }

}
