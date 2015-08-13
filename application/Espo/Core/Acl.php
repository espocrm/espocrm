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
 ************************************************************************/

namespace Espo\Core;

class Acl
{
    private $user;

    private $aclManager;

    public function __construct(AclManager $aclManager, \Espo\Entities\User $user)
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

    public function checkReadOnlyTeam($scope)
    {
        return $this->getAclManager()->checkReadOnlyTeam($this->getUser(), $scope);
    }

    public function checkReadOnlyOwn($scope)
    {
        return $this->getAclManager()->checkReadOnlyOwn($this->getUser(), $scope);
    }

    public function check($subject, $action = null, $isOwner = null, $inTeam = null)
    {
        return $this->getAclManager()->check($this->getUser(), $subject, $action, $isOwner, $inTeam) ;
    }

    public function checkScope($scope, $action = null, $isOwner = null, $inTeam = null, $entity = null)
    {
        return $this->getAclManager()->checkScope($this->getUser(), $scope, $action, $isOwner, $inTeam, $entity) ;
    }
}

