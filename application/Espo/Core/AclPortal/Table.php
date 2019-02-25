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

namespace Espo\Core\AclPortal;

use \Espo\Core\Exceptions\Error;

use \Espo\ORM\Entity;
use \Espo\Entities\User;
use \Espo\Entities\Portal;

use \Espo\Core\Utils\Config;
use \Espo\Core\Utils\Metadata;
use \Espo\Core\Utils\FieldManagerUtil;
use \Espo\Core\Utils\File\Manager as FileManager;

class Table extends \Espo\Core\Acl\Table
{
    protected $type = 'aclPortal';

    protected $portal;

    protected $defaultAclType = 'recordAllOwnNo';

    protected $levelList = ['yes', 'all', 'account', 'contact', 'own', 'no'];

    protected $isStrictModeForced = true;

    public function __construct(User $user, Portal $portal, Config $config = null, FileManager $fileManager = null, Metadata $metadata = null, FieldManagerUtil $fieldManager = null)
    {
        if (empty($portal)) {
            throw new Error("No portal was passed to AclPortal\\Table constructor.");
        }
        $this->portal = $portal;
        parent::__construct($user, $config, $fileManager, $metadata, $fieldManager);
    }

    protected function getPortal()
    {
        return $this->portal;
    }

    protected function initCacheFilePath()
    {
        $this->cacheFilePath = 'data/cache/application/acl-portal/'.$this->getPortal()->id.'/' . $this->getUser()->id . '.php';
    }

    protected function getRoleList()
    {
        $roleList = [];

        $userRoleList = $this->getUser()->get('portalRoles');
        if (!(is_array($userRoleList) || $userRoleList instanceof \Traversable)) {
            throw new Error();
        }
        foreach ($userRoleList as $role) {
            $roleList[] = $role;
        }

        $portalRoleList = $this->getPortal()->get('portalRoles');
        if (!(is_array($portalRoleList) || $portalRoleList instanceof \Traversable)) {
            throw new Error();
        }
        foreach ($portalRoleList as $role) {
            $roleList[] = $role;
        }

        return $roleList;
    }

    protected function getScopeWithAclList()
    {
        $scopeList = [];
        $scopes = $this->getMetadata()->get('scopes');
        foreach ($scopes as $scope => $d) {
            if (empty($d['acl'])) continue;
            if (empty($d['aclPortal'])) continue;
            $scopeList[] = $scope;
        }
        return $scopeList;
    }

    protected function applyDefault(&$table, &$fieldTable)
    {
        parent::applyDefault($table, $fieldTable);

        foreach ($this->getScopeList() as $scope) {
            if (!isset($table->$scope)) {
                $table->$scope = false;
            }
        }
    }

    protected function applyDisabled(&$table, &$fieldTable)
    {
        foreach ($this->getScopeList() as $scope) {
            $d = $this->getMetadata()->get('scopes.' . $scope);
            if (!empty($d['disabled']) || !empty($d['portalDisabled'])) {
                $table->$scope = false;
                unset($fieldTable->$scope);
            }
        }
    }

    protected function applyAdditional(&$table, &$fieldTable, &$valuePermissionLists)
    {
    }
}

