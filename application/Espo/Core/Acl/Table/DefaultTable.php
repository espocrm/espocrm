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

namespace Espo\Core\Acl\Table;

use Espo\Entities\User;

use Espo\Core\{
    Acl\Table,
    Acl\Table\RoleListProvider,
    Acl\Table\CacheKeyProvider,
    Acl\ScopeData,
    Acl\FieldData,
    Utils\Config,
    Utils\Metadata,
    Utils\DataCache,
};

use StdClass;
use RuntimeException;

/**
 * A table is generated for a user. Multiple roles are merged into a single table.
 * Stores access levels.
 */
class DefaultTable implements Table
{
    private const LEVEL_NOT_SET = 'not-set';

    protected $type = 'acl';

    protected $defaultAclType = 'recordAllTeamOwnNo';

    private $actionList = [
        self::ACTION_READ,
        self::ACTION_STREAM,
        self::ACTION_EDIT,
        self::ACTION_DELETE,
        self::ACTION_CREATE,
    ];

    private $booleanActionList = [
        self::ACTION_CREATE,
    ];

    protected $levelList = [
        self::LEVEL_YES,
        self::LEVEL_ALL,
        self::LEVEL_TEAM,
        self::LEVEL_OWN,
        self::LEVEL_NO,
    ];

    private $fieldActionList = [
        self::ACTION_READ,
        self::ACTION_EDIT,
    ];

    protected $fieldLevelList = [
        self::LEVEL_YES,
        self::LEVEL_NO,
    ];

    private $data = null;

    private $cacheKey;

    private $valuePermissionList = [];

    private $roleListProvider;

    protected $user;

    protected $metadata;

    public function __construct(
        RoleListProvider $roleListProvider,
        CacheKeyProvider $cacheKeyProvider,
        User $user,
        Config $config,
        Metadata $metadata,
        DataCache $dataCache
    ) {
        $this->roleListProvider = $roleListProvider;

        $this->data = (object) [
            'scopes' => (object) [],
            'fields' => (object) [],
            'permissions' => (object) [],
        ];

        $this->user = $user;
        $this->metadata = $metadata;

        if (!$this->user->isFetched()) {
            throw new RuntimeException('User must be fetched before ACL check.');
        }

        $this->valuePermissionList = $this->metadata
            ->get(['app', $this->type, 'valuePermissionList'], []);

        $this->cacheKey = $cacheKeyProvider->get();

        if ($config->get('useCache') && $dataCache->has($this->cacheKey)) {
            $this->data = $dataCache->get($this->cacheKey);
        }
        else {
            $this->load();

            if ($config->get('useCache')) {
                $dataCache->store($this->cacheKey, $this->data);
            }
        }
    }

    /**
     * Get scope data.
     */
    public function getScopeData(string $scope): ScopeData
    {
        if (!isset($this->data->scopes->$scope)) {
            return ScopeData::fromRaw(false);
        }

        $data = $this->data->scopes->$scope;

        if (is_string($data)) {
            return $this->getScopeData($data);
        }

        return ScopeData::fromRaw($data);
    }

    /**
     * Get field data.
     */
    public function getFieldData(string $scope, string $field): FieldData
    {
        if (!isset($this->data->fields->$scope)) {
            return FieldData::fromRaw((object) [
                self::ACTION_READ => self::LEVEL_YES,
                self::ACTION_EDIT => self::LEVEL_YES,
            ]);
        }

        $data = $this->data->fields->$scope->$field ?? (object) [
            self::ACTION_READ => self::LEVEL_YES,
            self::ACTION_EDIT => self::LEVEL_YES,
        ];

        return FieldData::fromRaw($data);
    }

    /**
     * Get a permission level.
     */
    public function getPermissionLevel(string $permission): string
    {
        return $this->data->permissions->$permission ?? self::LEVEL_NO;
    }

    private function load(): void
    {
        $valuePermissionLists = (object) [];

        foreach ($this->valuePermissionList as $permission) {
            $valuePermissionLists->$permission = [];
        }

        $aclTableList = [];
        $fieldTableList = [];

        $aclTable = (object) [];
        $fieldTable = (object) [];

        if (!$this->user->isAdmin()) {
            $roleList = $this->roleListProvider->get();

            foreach ($roleList as $role) {
                $aclTableList[] = $role->getScopeTableData();
                $fieldTableList[] = $role->getFieldTableData();

                foreach ($this->valuePermissionList as $permissionKey) {
                    $permission = $this->normilizePermissionName($permissionKey);

                    $valuePermissionLists->{$permissionKey}[] = $role->getPermissionLevel($permission);
                }
            }

            $aclTable = $this->mergeTableList($aclTableList);
            $fieldTable = $this->mergeFieldTableList($fieldTableList);

            $this->applyDefault($aclTable, $fieldTable);
            $this->applyDisabled($aclTable, $fieldTable);
            $this->applyMandatory($aclTable, $fieldTable);
            $this->applyAdditional($aclTable, $fieldTable, $valuePermissionLists);
        }

        if ($this->user->isAdmin()) {
            $aclTable = (object) [];
            $fieldTable = (object) [];

            $this->applyHighest($aclTable, $fieldTable);
            $this->applyAdminMandatory($aclTable, $fieldTable);
        }

        foreach ($aclTable as $scope => $data) {
            if (is_string($data) && isset($aclTable->$data)) {
                $aclTable->$scope = $aclTable->$data;
            }
        }

        $this->data->scopes = $aclTable;
        $this->data->fields = $fieldTable;

        if (!$this->user->isAdmin()) {
            foreach ($this->valuePermissionList as $permissionKey) {
                $permission = $this->normilizePermissionName($permissionKey);

                $defaultLevel = $this->metadata
                    ->get(['app', $this->type, 'permissionsStrictDefaults', $permissionKey]) ??
                    self::LEVEL_NO;

                $this->data->permissions->$permission = $this->mergeValueList(
                    $valuePermissionLists->$permissionKey,
                    $defaultLevel
                );

                $mandatoryLevel = $this->metadata->get(['app', $this->type, 'mandatory', $permissionKey]);

                if ($mandatoryLevel !== null) {
                    $this->data->permissions->$permission = $mandatoryLevel;
                }
            }
        }

        if ($this->user->isAdmin()) {
            foreach ($this->valuePermissionList as $permissionKey) {
                $permission = $this->normilizePermissionName($permissionKey);

                $highestLevel = $this->metadata
                    ->get(['app', $this->type, 'valuePermissionHighestLevels', $permissionKey]);

                if ($highestLevel !== null) {
                    $this->data->permissions->$permission = $highestLevel;

                    continue;
                }

                $this->data->permissions->$permission = self::LEVEL_ALL;
            }
        }
    }

    private function normilizePermissionName(string $permissionKey): string
    {
        $permission = $permissionKey;

        if (substr($permissionKey, -10) === 'Permission') {
            $permission = substr($permissionKey, 0, -10);
        }

        return $permission;
    }

    protected function applyHighest(StdClass &$table, StdClass &$fieldTable): void
    {
        foreach ($this->getScopeList() as $scope) {
            if ($this->metadata->get(['scopes', $scope, $this->type]) === 'boolean') {
                $table->$scope = true;

                continue;
            }

            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            $table->$scope = (object) [];

            $actionList = $this->metadata->get(
                ['scopes', $scope, $this->type . 'ActionList'],
                $this->actionList
            );

            $highest = $this->metadata->get(
                ['scopes', $scope, $this->type . 'HighestLevel'],
                self::LEVEL_ALL
            );

            foreach ($actionList as $action) {
                $table->$scope->$action = $highest;

                if (in_array($action, $this->booleanActionList)) {
                    $table->$scope->$action = 'yes';
                }
            }
        }
    }

    protected function applyDefault(&$table, &$fieldTable): void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        $data = $this->metadata->get(['app', $this->type, 'strictDefault', 'scopeLevel'], []);

        foreach ($data as $scope => $item) {
            if (isset($table->$scope)) {
                continue;
            }

            $value = $item;

            if (is_array($item)) {
                $value = (object) $item;
            }

            $table->$scope = $value;
        }

        $defaultFieldData = $this->metadata
            ->get(['app', $this->type, 'strictDefault', 'fieldLevel']) ?? [];

        foreach ($this->getScopeList() as $scope) {
            if (isset($table->$scope) && $table->$scope === false) {
                continue;
            }

            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            $fieldList = array_keys($this->metadata->get(['entityDefs', $scope, 'fields']) ?? []);

            $defaultScopeFieldData = $this->metadata
                ->get(['app', $this->type, 'strictDefault', 'scopeFieldLevel', $scope]) ?? [];

            foreach (array_merge($defaultFieldData, $defaultScopeFieldData) as $field => $f) {
                if (!in_array($field, $fieldList)) {
                    continue;
                }

                if (!isset($fieldTable->$scope)) {
                    $fieldTable->$scope = (object) [];
                }

                if (isset($fieldTable->$scope->$field)) {
                    continue;
                }

                $fieldTable->$scope->$field = (object) [];

                foreach ($this->fieldActionList as $action) {
                    $level = self::LEVEL_NO;

                    if ($f === true) {
                        $level = self::LEVEL_YES;
                    }
                    else {
                        if (is_array($f) && isset($f[$action])) {
                            $level = $f[$action];
                        }
                    }

                    $fieldTable->$scope->$field->$action = $level;
                }
            }
        }

        foreach ($this->getScopeWithAclList() as $scope) {
            if (isset($table->$scope)) {
                continue;
            }

            $aclType = $this->metadata->get(['scopes', $scope, $this->type]);

            if ($aclType === true) {
                $aclType = $this->defaultAclType;
            }

            if (empty($aclType)) {
                continue;
            }

            $defaultValue =
                $this->metadata->get(['app', $this->type, 'scopeLevelTypesStrictDefaults', $aclType]) ??
                $this->metadata->get(['app', $this->type, 'scopeLevelTypesStrictDefaults', 'record']);

            if (is_array($defaultValue)) {
                $defaultValue = (object) $defaultValue;
            }

            $table->$scope = $defaultValue;

            if (is_object($table->$scope)) {
                $actionList = $this->metadata->get(['scopes', $scope, $this->type . 'ActionList']);

                if ($actionList) {
                    foreach (get_object_vars($table->$scope) as $action => $level) {
                        if (!in_array($action, $actionList)) {
                            unset($table->$scope->$action);
                        }
                    }
                }
            }
        }
    }

    protected function applyMandatoryInternal(StdClass $table, StdClass $fieldTable, string $type): void
    {
        $data = $this->metadata->get(['app', $this->type, $type, 'scopeLevel']) ?? [];

        foreach ($data as $scope => $item) {
            $value = $item;

            if (is_array($item)) {
                $value = (object) $item;
            }

            $table->$scope = $value;
        }

        $mandatoryFieldData = $this->metadata->get(['app', $this->type, $type, 'fieldLevel']) ?? [];

        foreach ($this->getScopeList() as $scope) {
            if (isset($table->$scope) && $table->$scope === false) {
                continue;
            }

            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            $fieldList = array_keys($this->metadata->get(['entityDefs', $scope, 'fields']) ?? []);

            $mandatoryScopeFieldData = $this->metadata
                ->get(['app', $this->type, $type, 'scopeFieldLevel', $scope]) ?? [];

            foreach (array_merge($mandatoryFieldData, $mandatoryScopeFieldData) as $field => $item) {
                if (!in_array($field, $fieldList)) {
                    continue;
                }

                if (!isset($fieldTable->$scope)) {
                    $fieldTable->$scope = (object) [];
                }

                $fieldTable->$scope->$field = (object) [];

                foreach ($this->fieldActionList as $action) {
                    $level = self::LEVEL_NO;

                    if ($item === true) {
                        $level = self::LEVEL_YES;
                    }
                    else {
                        if (is_array($item) && isset($item[$action])) {
                            $level = $item[$action];
                        }
                    }

                    $fieldTable->$scope->$field->$action = $level;
                }
            }
        }
    }

    private function applyMandatory(StdClass $table, StdClass $fieldTable): void
    {
        $this->applyMandatoryInternal($table, $fieldTable, 'mandatory');
    }

    private function applyAdminMandatory(StdClass $table, StdClass $fieldTable): void
    {
        $this->applyMandatoryInternal($table, $fieldTable, 'adminMandatory');
    }

    protected function applyDisabled(&$table, &$fieldTable): void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        foreach ($this->getScopeList() as $scope) {
            if ($this->metadata->get(['scopes', $scope, 'disabled'])) {
                $table->$scope = false;

                unset($fieldTable->$scope);
            }
        }
    }

    /**
     * @todo Revise usage of this method.
     */
    protected function applyAdditional(&$table, &$fieldTable, &$valuePermissionLists): void
    {
        if ($this->user->isPortal()) {
            foreach ($this->getScopeList() as $scope) {
                $table->$scope = false;

                unset($fieldTable->$scope);
            }

            foreach ($this->valuePermissionList as $permission) {
                $valuePermissionLists->{$permission}[] = self::LEVEL_NO;
            }
        }
    }

    private function mergeValueList(array $list, string $defaultValue): string
    {
        $result = null;

        foreach ($list as $level) {
            if ($level === self::LEVEL_NOT_SET) {
                continue;
            }

            if (is_null($result)) {
                $result = $level;

                continue;
            }

            if (
                array_search($result, $this->levelList) >
                array_search($level, $this->levelList)
            ) {
                $result = $level;
            }
        }

        if (is_null($result)) {
            $result = $defaultValue;
        }

        return $result;
    }

    protected function getScopeWithAclList(): array
    {
        $scopeList = [];

        $scopes = $this->metadata->get('scopes');

        foreach ($scopes as $scope => $d) {
            if (empty($d['acl'])) {
                continue;
            }

            $scopeList[] = $scope;
        }

        return $scopeList;
    }

    protected function getScopeList(): array
    {
        $scopeList = [];

        $scopes = $this->metadata->get('scopes');

        foreach ($scopes as $scope => $item) {
            $scopeList[] = $scope;
        }

        return $scopeList;
    }

    private function mergeTableList(array $tableList): StdClass
    {
        $data = (object) [];

        $scopeList = $this->getScopeWithAclList();

        foreach ($tableList as $table) {
            foreach ($scopeList as $scope) {
            	if (!isset($table->$scope)) {
                    continue;
                }

                $this->mergeTableListItem($data, $scope, $table->$scope);
            }
        }

        return $data;
    }

    private function mergeTableListItem(StdClass $data, string $scope, $row): void
    {
        if ($row === false || $row === null) {
            if (!isset($data->$scope)) {
                $data->$scope = false;
            }

            return;
        }

        if ($row === true) {
            $data->$scope = true;

            return;
        }

        if (!isset($data->$scope)) {
            $data->$scope = (object) [];
        }

        if ($data->$scope === false) {
            $data->$scope = (object) [];
        }

        if (!is_object($row)) {
            return;
        }

        $actionList = $this->metadata
            ->get(['scopes', $scope, $this->type . 'ActionList']) ?? $this->actionList;

        foreach ($actionList as $i => $action) {
            if (isset($row->$action)) {
                $level = $row->$action;

                if (!isset($data->$scope->$action)) {
                    $data->$scope->$action = $level;
                }
                else if (
                    array_search($data->$scope->$action, $this->levelList) >
                    array_search($level, $this->levelList)
                ) {
                    $data->$scope->$action = $level;
                }

                continue;
            }

            if ($i === 0) {
                continue;
            }

            // @todo Remove everything below.
            $previousAction = $this->actionList[$i - 1];

            if (in_array($action, $this->booleanActionList)) {
                $data->$scope->$action = self::LEVEL_YES;
            }
            else if ($action === self::ACTION_STREAM && isset($data->$scope->$previousAction)) {
                $data->$scope->$action = $data->$scope->$previousAction;
            }
        }
    }

    private function mergeFieldTableList(array $tableList): StdClass
    {
        $data = (object) [];

        $scopeList = $this->getScopeWithAclList();

        foreach ($tableList as $table) {
            foreach ($scopeList as $scope) {
                if (!isset($table->$scope)) {
                    continue;
                }

                if (!isset($data->$scope)) {
                    $data->$scope = (object) [];
                }

                if (!is_object($table->$scope)) {
                    continue;
                }

                $fieldList = array_keys($this->metadata->get(['entityDefs', $scope, 'fields']) ?? []);

                foreach (get_object_vars($table->$scope) as $field => $row) {
                    if (!is_object($row)) {
                        continue;
                    }

                    if (!in_array($field, $fieldList)) {
                        continue;
                    }

                    if (!isset($data->$scope->$field)) {
                        $data->$scope->$field = (object) [];
                    }

                    foreach ($this->fieldActionList as $i => $action) {
                        if (!isset($row->$action)) {
                            continue;
                        }

                        $level = $row->$action;

                        if (!isset($data->$scope->$field->$action)) {
                            $data->$scope->$field->$action = $level;
                        }
                        else {
                            if (
                                array_search(
                                    $data->$scope->$field->$action,
                                    $this->fieldLevelList
                                ) > array_search($level, $this->fieldLevelList)
                            ) {
                                $data->$scope->$field->$action = $level;
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
