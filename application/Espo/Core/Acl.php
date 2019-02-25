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

use \Espo\ORM\Entity;
use \Espo\Entities\User;

class Acl
{
    private $user;

    private $aclManager;

    public function __construct(AclManager $aclManager, User $user)
    {
        $this->aclManager = $aclManager;
        $this->user = $user;
    }

    protected function getAclManager()
    {
        return $this->aclManager;
    }

    protected function getUser()
    {
        return $this->user;
    }

    public function getMap()
    {
        return $this->getAclManager()->getMap($this->getUser());
    }

    public function getLevel($scope, $action)
    {
        return $this->getAclManager()->getLevel($this->getUser(), $scope, $action);
    }

    public function get($permission)
    {
        return $this->getAclManager()->get($this->getUser(), $permission);
    }

    public function checkReadNo($scope)
    {
        return $this->getAclManager()->checkReadNo($this->getUser(), $scope);
    }

    public function checkReadOnlyTeam($scope)
    {
        return $this->getAclManager()->checkReadOnlyTeam($this->getUser(), $scope);
    }

    public function checkReadOnlyOwn($scope)
    {
        return $this->getAclManager()->checkReadOnlyOwn($this->getUser(), $scope);
    }

    public function check($subject, $action = null)
    {
        return $this->getAclManager()->check($this->getUser(), $subject, $action);
    }

    public function checkScope($scope, $action = null)
    {
        return $this->getAclManager()->checkScope($this->getUser(), $scope, $action);
    }

    public function checkEntity(Entity $entity, $action = 'read')
    {
        return $this->getAclManager()->checkEntity($this->getUser(), $entity, $action);
    }

    public function checkUser($permission, User $entity)
    {
        return $this->getAclManager()->checkUser($this->getUser(), $permission, $entity);
    }

    public function checkIsOwner(Entity $entity)
    {
        return $this->getAclManager()->checkIsOwner($this->getUser(), $entity);
    }

    public function checkInTeam(Entity $entity)
    {
        return $this->getAclManager()->checkInTeam($this->getUser(), $entity);
    }

    public function getScopeForbiddenAttributeList($scope, $action = 'read', $thresholdLevel = 'no')
    {
        return $this->getAclManager()->getScopeForbiddenAttributeList($this->getUser(), $scope, $action, $thresholdLevel);
    }

    public function getScopeForbiddenFieldList($scope, $action = 'read', $thresholdLevel = 'no')
    {
        return $this->getAclManager()->getScopeForbiddenFieldList($this->getUser(), $scope, $action, $thresholdLevel);
    }

    public function getScopeForbiddenLinkList($scope, $action = 'read', $thresholdLevel = 'no')
    {
        return $this->getAclManager()->getScopeForbiddenLinkList($this->getUser(), $scope, $action, $thresholdLevel);
    }

    public function checkUserPermission($target, $permissionType = 'userPermission')
    {
        return $this->getAclManager()->checkUserPermission($this->getUser(), $target, $permissionType);
    }

    public function checkAssignmentPermission($target)
    {
        return $this->getAclManager()->checkAssignmentPermission($this->getUser(), $target);
    }

    public function getScopeRestrictedFieldList($scope, $type)
    {
        return $this->getAclManager()->getScopeRestrictedFieldList($scope, $type);
    }

    public function getScopeRestrictedAttributeList($scope, $type)
    {
        return $this->getAclManager()->getScopeRestrictedAttributeList($scope, $type);
    }

    public function getScopeRestrictedLinkList($scope, $type)
    {
        return $this->getAclManager()->getScopeRestrictedLinkList($scope, $type);
    }
}
