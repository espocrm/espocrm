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

namespace Espo\Core;

use Espo\ORM\Entity;
use Espo\Entities\User;

/**
 * A wrapper for AclManager. To check access for a current user.
 */
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

    public function getMap() : \StdClass
    {
        return $this->getAclManager()->getMap($this->getUser());
    }

    /**
     * Get an access level for a specific scope and action.
     */
    public function getLevel(string $scope, string $action) : string
    {
        return $this->getAclManager()->getLevel($this->getUser(), $scope, $action);
    }

    /**
     * Get a permission. E.g. 'assignment' permission.
     */
    public function get(string $permission) : ?string
    {
        return $this->getAclManager()->get($this->getUser(), $permission);
    }

    /**
     * Whether there's no 'read' access for a specific scope.
     */
    public function checkReadNo(string $scope) : bool
    {
        return $this->getAclManager()->checkReadNo($this->getUser(), $scope);
    }

    /**
     * Whether 'read' access is set to 'team' for a specific scope.
     */
    public function checkReadOnlyTeam(string $scope) : bool
    {
        return $this->getAclManager()->checkReadOnlyTeam($this->getUser(), $scope);
    }

    /**
     * Whether 'read' access is set to 'own' for a specific scope.
     */
    public function checkReadOnlyOwn(string $scope) : bool
    {
        return $this->getAclManager()->checkReadOnlyOwn($this->getUser(), $scope);
    }

    /**
     * Check a scope or entity. If $action is omitted, it will check whether a scope level is set to 'enabled'.
     */
    public function check($subject, ?string $action = null) : bool
    {
        return $this->getAclManager()->check($this->getUser(), $subject, $action);
    }

    /**
     * Check access to scope. If $action is omitted, it will check whether a scope level is set to 'enabled'.
     */
    public function checkScope(string $scope, ?string $action = null) : bool
    {
        return $this->getAclManager()->checkScope($this->getUser(), $scope, $action);
    }

    /**
     * Check access to a specific entity (record).
     */
    public function checkEntity(Entity $entity, string $action = 'read') : bool
    {
        return $this->getAclManager()->checkEntity($this->getUser(), $entity, $action);
    }

    /**
     * @deprecated
     */
    public function checkUser(string $permission, User $entity) : bool
    {
        return $this->getAclManager()->checkUser($this->getUser(), $permission, $entity);
    }

    /**
     * Whether a user is owned of an entity (record). Usually 'assignedUser' field is used for checking.
     */
    public function checkIsOwner(Entity $entity) : bool
    {
        return $this->getAclManager()->checkIsOwner($this->getUser(), $entity);
    }

    /**
     * Whether a user team list overlaps with teams set in an entity.
     */
    public function checkInTeam(Entity $entity) : bool
    {
        return $this->getAclManager()->checkInTeam($this->getUser(), $entity);
    }

    /**
     * Get attributes forbidden for a user.
     */
    public function getScopeForbiddenAttributeList(string $scope, string $action = 'read', string $thresholdLevel = 'no') : array
    {
        return $this->getAclManager()->getScopeForbiddenAttributeList($this->getUser(), $scope, $action, $thresholdLevel);
    }

    /**
     * Get fields forbidden for a user.
     */
    public function getScopeForbiddenFieldList(string $scope, string $action = 'read', string $thresholdLevel = 'no') : array
    {
        return $this->getAclManager()->getScopeForbiddenFieldList($this->getUser(), $scope, $action, $thresholdLevel);
    }

    /**
     * Get links forbidden for a user.
     */
    public function getScopeForbiddenLinkList(string $scope, string $action = 'read', string $thresholdLevel = 'no') : array
    {
        return $this->getAclManager()->getScopeForbiddenLinkList($this->getUser(), $scope, $action, $thresholdLevel);
    }

    /**
     * Whether a user has an access to another user over a specific permission.
     *
     * @param $target User|string User entity or user ID.
     */
    public function checkUserPermission($target, string $permissionType = 'user') : bool
    {
        return $this->getAclManager()->checkUserPermission($this->getUser(), $target, $permissionType);
    }

    /**
     * Whether a user can assign to another user.
     *
     * @param $target User|string User entity or user ID.
     */
    public function checkAssignmentPermission($target) : bool
    {
        return $this->getAclManager()->checkAssignmentPermission($this->getUser(), $target);
    }

    public function getScopeRestrictedFieldList(string $scope, $type) : array
    {
        return $this->getAclManager()->getScopeRestrictedFieldList($scope, $type);
    }

    public function getScopeRestrictedAttributeList(string $scope, $type) : array
    {
        return $this->getAclManager()->getScopeRestrictedAttributeList($scope, $type);
    }

    public function getScopeRestrictedLinkList(string $scope, $type) : array
    {
        return $this->getAclManager()->getScopeRestrictedLinkList($scope, $type);
    }
}
