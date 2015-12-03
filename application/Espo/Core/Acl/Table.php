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

namespace Espo\Core\Acl;

use \Espo\Core\Exceptions\Error;

use \Espo\ORM\Entity;

class Table
{
    private $data = array(
        'table' => array()
    );

    private $cacheFile;

    private $actionList = ['read', 'edit', 'delete'];

    private $levelList = ['all', 'team', 'own', 'no'];

    protected $fileManager;

    protected $metadata;

    public function __construct(\Espo\Entities\User $user, $config = null, $fileManager = null, $metadata = null)
    {
        $this->user = $user;

        $this->metadata = $metadata;

        if (!$this->user->isFetched()) {
            throw new Error('User must be fetched before ACL check.');
        }

        $this->user->loadLinkMultipleField('teams');

        if ($fileManager) {
            $this->fileManager = $fileManager;
        }

        $this->cacheFile = 'data/cache/application/acl/' . $user->id . '.php';

        if ($config && $config->get('useCache') && file_exists($this->cacheFile)) {
            $cached = include $this->cacheFile;
            $this->data = $cached;
            $this->initSolid();
        } else {
            $this->load();
            $this->initSolid();
            if ($config && $fileManager && $config->get('useCache')) {
                $this->buildCache();
            }
        }
    }

    public function getMap()
    {
        return $this->data;
    }

    public function getScopeData($scope)
    {
        if (array_key_exists($scope, $this->data['table'])) {
            $data = $this->data['table'][$scope];
            if (is_string($data)) {
                $data = $this->getScopeData($data);
                return $data;
            }
            return $data;
        }
        return null;
    }

    public function get($permission)
    {
        if ($permission == 'table') {
            return null;
        }

        if (array_key_exists($permission, $this->data)) {
            return $this->data[$permission];
        }
        return null;
    }

    public function getLevel($scope, $action)
    {
        if (array_key_exists($scope, $this->data['table'])) {
            if (array_key_exists($action, $this->data['table'][$scope])) {
                return $this->data['table'][$scope][$action];
            }
        }
        return false;
    }

    private function load()
    {
        $aclTableList = [];
        $assignmentPermissionList = [];
        $userPermissionList = [];

        $userRoles = $this->user->get('roles');

        foreach ($userRoles as $role) {
            $aclTableList[] = $role->get('data');
            $assignmentPermissionList[] = $role->get('assignmentPermission');
            $userPermissionList[] = $role->get('userPermission');
        }

        $teams = $this->user->get('teams');
        foreach ($teams as $team) {
            $teamRoles = $team->get('roles');
            foreach ($teamRoles as $role) {
                $aclTableList[] = $role->get('data');
                $assignmentPermissionList[] = $role->get('assignmentPermission');
                $userPermissionList[] = $role->get('userPermission');
            }
        }

        $aclTable = $this->merge($aclTableList);

        foreach ($this->getScopeList() as $scope) {
            if ($this->metadata->get('scopes.' . $scope . '.disabled')) {
                $aclTable[$scope] = false;
            }
        }

        $this->data['table'] = $aclTable;

        $this->data['assignmentPermission'] = $this->mergeValues($assignmentPermissionList, $this->metadata->get('app.acl.valueDefaults.assignmentPermission', 'all'));
        $this->data['userPermission'] = $this->mergeValues($userPermissionList, $this->metadata->get('app.acl.valueDefaults.userPermission', 'no'));
    }

    private function initSolid()
    {
        if (!$this->metadata) {
            return;
        }

        $data = $this->metadata->get('app.acl.solid', array());

        foreach ($data as $entityType => $item) {
            $this->data['table'][$entityType] = $item;
        }
    }

    private function mergeValues(array $list, $defaultValue)
    {
        $result = null;
        foreach ($list as $level) {
            if ($level != 'not-set') {
                if (is_null($result)) {
                    $result = $level;
                    continue;
                }
                if (array_search($result, $this->levelList) > array_search($level, $this->levelList)) {
                    $result = $level;
                }
            }
        }
        if (is_null($result)) {
            $result = $defaultValue;
        }
        return $result;
    }

    private function getScopeWithAclList()
    {
        $scopeList = [];
        $scopes = $this->metadata->get('scopes');
        foreach ($scopes as $scope => $d) {
        	if (!empty($d['acl'])) {
        		$scopeList[] = $scope;
        	}
        }
        return $scopeList;
    }

    private function getScopeList()
    {
        $scopeList = [];
        $scopes = $this->metadata->get('scopes');
        foreach ($scopes as $scope => $d) {
            $scopeList[] = $scope;
        }
        return $scopeList;
    }

    private function merge($tables)
    {
        $data = array();
        $scopeList = $this->getScopeWithAclList();

        foreach ($tables as $table) {
            foreach ($scopeList as $scope) {
            	if (!isset($table->$scope)) {
            		continue;
            	}
            	$row = $table->$scope;

                if ($row == false) {
                    if (!isset($data[$scope])) {
                        $data[$scope] = false;
                    }
                } else if ($row === true) {
                    $data[$scope] = true;
                } else {
                    if (!isset($data[$scope])) {
                        $data[$scope] = array();
                    }
                    if ($data[$scope] == false) {
                        $data[$scope] = array();
                    }

                    if (is_array($row) || $row instanceof \stdClass) {
                        foreach ($row as $action => $level) {
                            if (!isset($data[$scope][$action])) {
                                $data[$scope][$action] = $level;
                            } else {
                                if (array_search($data[$scope][$action], $this->levelList) > array_search($level, $this->levelList)) {
                                    $data[$scope][$action] = $level;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($scopeList as $scope) {
        	if (!array_key_exists($scope, $data)) {
        		$aclType = $this->metadata->get('scopes.' . $scope . '.acl');
                if ($aclType === true) {
                    $aclType = 'recordAllTeamOwnNo';
                }
        		if (!empty($aclType)) {
	        		$data[$scope] = $this->metadata->get('app.acl.defaults.' . $aclType, true);
        		}
        	}
        }
        return $data;
    }

    private function buildCache()
    {
        $contents = '<' . '?'. 'php return ' .  var_export($this->data, true)  . ';';
        $this->fileManager->putContents($this->cacheFile, $contents);
    }
}

