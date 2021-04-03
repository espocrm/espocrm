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

namespace Espo\Core\Acl;

use Espo\Core\Exceptions\Error;

use Espo\Entities\User;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Config,
    Utils\Metadata,
    Utils\FieldUtil,
    Utils\DataCache,
    Utils\ObjectUtil,
};

use StdClass;
use Traversable;

/**
 * A table is generated for each user. Multiple roles are merged into a single table.
 * This table is used for access checking.
 */
class Table
{
    public const LEVEL_YES = 'yes';

    public const LEVEL_NO = 'no';

    public const LEVEL_ALL = 'all';

    public const LEVEL_TEAM = 'team';

    public const LEVEL_OWN = 'own';

    public const ACTION_READ = 'read';

    public const ACTION_STREAM = 'stream';

    public const ACTION_EDIT = 'edit';

    public const ACTION_DELETE = 'delete';

    public const ACTION_CREATE = 'create';

    protected $type = 'acl';

    protected $defaultAclType = 'recordAllTeamOwnNo';

    private $data = null;

    protected $cacheKey;

    protected $actionList = ['read', 'stream', 'edit', 'delete', 'create'];

    protected $booleanActionList = ['create'];

    protected $levelList = ['yes', 'all', 'team', 'own', 'no'];

    protected $fieldActionList = ['read', 'edit'];

    protected $fieldLevelList = ['yes', 'no'];

    protected $valuePermissionHighestLevels = [];

    protected $valuePermissionList = [];

    protected $forbiddenAttributesCache = [];

    protected $forbiddenFieldsCache = [];

    protected $isStrictModeForced = false;

    protected $isStrictMode = false;

    protected $entityManager;

    protected $user;

    protected $config;

    protected $metadata;

    protected $fieldUtil;

    protected $dataCache;

    public function __construct(
        EntityManager $entityManager,
        User $user,
        Config $config,
        Metadata $metadata,
        FieldUtil $fieldUtil,
        DataCache $dataCache
    ) {
        $this->entityManager = $entityManager;

        $this->data = (object) [
            'table' => (object) [],
            'fieldTable' => (object) [],
            'fieldTableQuickAccess' => (object) [],
        ];

        if ($this->isStrictModeForced) {
            $this->isStrictMode = true;
        } else {
            $this->isStrictMode = $config->get('aclStrictMode', true);
        }

        $this->user = $user;
        $this->metadata = $metadata;
        $this->fieldUtil = $fieldUtil;
        $this->dataCache = $dataCache;

        if (!$this->user->isFetched()) {
            throw new Error('User must be fetched before ACL check.');
        }

        $this->valuePermissionList = $this->metadata
            ->get(['app', $this->type, 'valuePermissionList'], []);

        $this->valuePermissionHighestLevels = $this->metadata
            ->get(['app', $this->type, 'valuePermissionHighestLevels'], []);

        $this->initCacheKey();

        if ($config && $config->get('useCache') && $this->dataCache->has($this->cacheKey)) {
            $this->data = $this->dataCache->get($this->cacheKey);
        }
        else {
            $this->load();

            if ($config && $config->get('useCache')) {
                $this->buildCache();
            }
        }
    }

    protected function initCacheKey() : void
    {
        $this->cacheKey = 'acl/' . $this->user->id;
    }

    public function getMap() : StdClass
    {
        return ObjectUtil::clone($this->data);
    }

    public function getScopeData(string $scope) : ScopeData
    {
        if (!isset($this->data->table->$scope)) {
            return ScopeData::fromRaw(false);
        }

        $data = $this->data->table->$scope;

        if (is_string($data)) {
            return $this->getScopeData($data);
        }

        return ScopeData::fromRaw($data);
    }

    public function get(string $permission) : ?string
    {
        if ($permission === 'table') {
            return null;
        }

        if (isset($this->data->$permission)) {
            return $this->data->$permission;
        }

        return self::LEVEL_NO;
    }

    public function getLevel(string $scope, string $action) : string
    {
        if (isset($this->data->table->$scope)) {
            if (isset($this->data->table->$scope->$action)) {
                return $this->data->table->$scope->$action;
            }
        }

        return self::LEVEL_NO;
    }

    public function getHighestLevel(string $scope, string $action) : string
    {
        if (in_array($action, $this->booleanActionList)) {
            return self::LEVEL_YES;
        }

        $level = $this->metadata->get(['scopes', $scope, $this->type . 'HighestLevel']);

        return $level ?? self::LEVEL_ALL;
    }

    private function load()
    {
        $valuePermissionLists = (object) [];

        foreach ($this->valuePermissionList as $permission) {
            $valuePermissionLists->$permission = [];
        }

        $aclTableList = [];
        $fieldTableList = [];

        if (!$this->user->isAdmin()) {
            $roleList = $this->getRoleList();

            foreach ($roleList as $role) {
                $aclTableList[] = $role->get('data');
                $fieldTableList[] = $role->get('fieldData');

                foreach ($this->valuePermissionList as $permission) {
                    $valuePermissionLists->{$permission}[] = $role->get($permission);
                }
            }

            $aclTable = $this->mergeTableList($aclTableList);
            $fieldTable = $this->mergeFieldTableList($fieldTableList);

            $this->applyDefault($aclTable, $fieldTable);
            $this->applyDisabled($aclTable, $fieldTable);
            $this->applyMandatory($aclTable, $fieldTable);
            $this->applyAdditional($aclTable, $fieldTable, $valuePermissionLists);
        }
        else {
            $aclTable = (object) [];

            foreach ($this->getScopeList() as $scope) {
                if ($this->metadata->get("scopes.{$scope}.{$this->type}") === 'boolean') {
                    $aclTable->$scope = true;
                } else {
                    if ($this->metadata->get("scopes.{$scope}.entity")) {
                        $aclTable->$scope = (object) [];

                        foreach ($this->actionList as $action) {
                            $aclTable->$scope->$action = 'all';

                            if (in_array($action, $this->booleanActionList)) {
                                $aclTable->$scope->$action = 'yes';
                            }
                        }
                    }
                }
            }

            $fieldTable = (object) [];
        }

        foreach ($aclTable as $scope => $data) {
            if (is_string($data)) {
                if (isset($aclTable->$data)) {
                    $aclTable->$scope = $aclTable->$data;
                }
            }
        }

        $this->data->table = $aclTable;
        $this->data->fieldTable = $fieldTable;

        $this->fillFieldTableQuickAccess();

        if (!$this->user->isAdmin()) {
            $permissionsDefaultsGroupName = 'permissionsDefaults';

            if ($this->isStrictMode) {
                $permissionsDefaultsGroupName = 'permissionsStrictDefaults';
            }

            foreach ($this->valuePermissionList as $permission) {
                $this->data->$permission = $this->mergeValueList(
                    $valuePermissionLists->$permission,
                    $this->metadata
                        ->get(['app', $this->type, $permissionsDefaultsGroupName, $permission, self::LEVEL_YES])
                );

                if ($this->metadata->get('app.'.$this->type.'.mandatory.' . $permission)) {
                    $this->data->$permission = $this->metadata
                        ->get('app.'.$this->type.'.mandatory.' . $permission);
                }
            }

        } else {
            foreach ($this->valuePermissionList as $permission) {
                if (isset($this->valuePermissionHighestLevels[$permission])) {
                    $this->data->$permission = $this->valuePermissionHighestLevels[$permission];

                    continue;
                }

                $this->data->$permission = self::LEVEL_ALL;
            }
        }
    }

    protected function getRoleList()
    {
        $roleList = [];

        $userRoleList = $this->entityManager
            ->getRepository('User')
            ->getRelation($this->user, 'roles')
            ->find();

        if (! $userRoleList instanceof Traversable) {
            throw new Error();
        }

        foreach ($userRoleList as $role) {
            $roleList[] = $role;
        }

        $teamList = $this->entityManager
            ->getRepository('User')
            ->getRelation($this->user, 'teams')
            ->find();

        if (! $teamList instanceof Traversable) {
            throw new Error();
        }

        foreach ($teamList as $team) {
            $teamRoleList = $this->entityManager
                ->getRepository('Team')
                ->getRelation($team, 'roles')
                ->find();

            foreach ($teamRoleList as $role) {
                $roleList[] = $role;
            }
        }

        return $roleList;
    }

    public function getScopeForbiddenAttributeList(
        string $scope, string $action = self::ACTION_READ, string $thresholdLevel = self::LEVEL_NO
    ) : array {

        $key = $scope . '_'. $action . '_' . $thresholdLevel;

        if (isset($this->forbiddenAttributesCache[$key])) {
            return $this->forbiddenAttributesCache[$key];
        }

        $fieldTableQuickAccess = $this->data->fieldTableQuickAccess;

        if (
            !isset($fieldTableQuickAccess->$scope) || !isset($fieldTableQuickAccess->$scope->attributes) ||
            !isset($fieldTableQuickAccess->$scope->attributes->$action)
        ) {
            $this->forbiddenAttributesCache[$key] = [];

            return [];
        }

        $levelList = [];

        foreach ($this->fieldLevelList as $level) {
            if (array_search($level, $this->fieldLevelList) >= array_search($thresholdLevel, $this->fieldLevelList)) {
                $levelList[] = $level;
            }
        }

        $attributeList = [];

        foreach ($levelList as $level) {
            if (!isset($fieldTableQuickAccess->$scope->attributes->$action->$level)) {
                continue;
            }

            foreach ($fieldTableQuickAccess->$scope->attributes->$action->$level as $attribute) {
                if (in_array($attribute, $attributeList)) {
                    continue;
                }

                $attributeList[] = $attribute;
            }
        }

        $this->forbiddenAttributesCache[$key] = $attributeList;

        return $attributeList;
    }

    public function getScopeForbiddenFieldList(
        string $scope, string $action = self::ACTION_READ, string $thresholdLevel = self::LEVEL_NO
    ) : array {

        $key = $scope . '_'. $action . '_' . $thresholdLevel;

        if (isset($this->forbiddenFieldsCache[$key])) {
            return $this->forbiddenFieldsCache[$key];
        }

        $fieldTableQuickAccess = $this->data->fieldTableQuickAccess;

        if (
            !isset($fieldTableQuickAccess->$scope) || !isset($fieldTableQuickAccess->$scope->fields) ||
            !isset($fieldTableQuickAccess->$scope->fields->$action)
        ) {
            $this->forbiddenFieldsCache[$key] = [];

            return [];
        }

        $levelList = [];

        foreach ($this->fieldLevelList as $level) {
            if (array_search($level, $this->fieldLevelList) >= array_search($thresholdLevel, $this->fieldLevelList)) {
                $levelList[] = $level;
            }
        }

        $fieldList = [];

        foreach ($levelList as $level) {
            if (!isset($fieldTableQuickAccess->$scope->fields->$action->$level)) {
                continue;
            }

            foreach ($fieldTableQuickAccess->$scope->fields->$action->$level as $field) {
                if (in_array($field, $fieldList)) {
                    continue;
                }

                $fieldList[] = $field;
            }
        }

        $this->forbiddenFieldsCache[$key] = $fieldList;

        return $fieldList;
    }

    protected function fillFieldTableQuickAccess() : void
    {
        $fieldTable = $this->data->fieldTable;

        $fieldTableQuickAccess = (object) [];

        foreach (get_object_vars($fieldTable) as $scope => $scopeData) {
            $fieldTableQuickAccess->$scope = (object) [
                'attributes' => (object) [],
                'fields' => (object) []
            ];

            foreach ($this->fieldActionList as $action) {
                $fieldTableQuickAccess->$scope->attributes->$action = (object) [];
                $fieldTableQuickAccess->$scope->fields->$action = (object) [];

                foreach ($this->fieldLevelList as $level) {
                    $fieldTableQuickAccess->$scope->attributes->$action->$level = [];
                    $fieldTableQuickAccess->$scope->fields->$action->$level = [];
                }
            }

            foreach (get_object_vars($scopeData) as $field => $fieldData) {
                $attributeList = $this->fieldUtil->getAttributeList($scope, $field);

                foreach ($this->fieldActionList as $action) {
                    if (!isset($fieldData->$action)) {
                        continue;
                    }

                    foreach ($this->fieldLevelList as $level) {
                        if ($fieldData->$action === $level) {
                            $fieldTableQuickAccess->$scope->fields->$action->{$level}[] = $field;

                            foreach ($attributeList as $attribute) {
                                $fieldTableQuickAccess->$scope->attributes->$action->{$level}[] = $attribute;
                            }
                        }
                    }
                }
            }
        }

        $this->data->fieldTableQuickAccess = $fieldTableQuickAccess;
    }

    protected function applyDefault(&$table, &$fieldTable) : void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        $defaultsGroupName = 'default';

        if ($this->isStrictMode) {
            $defaultsGroupName = 'strictDefault';
        }

        $data = $this->metadata->get(['app', $this->type, $defaultsGroupName, 'scopeLevel'], []);

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

        $defaultFieldData = $this->metadata->get(['app', $this->type, $defaultsGroupName, 'fieldLevel'], []);

        foreach ($this->getScopeList() as $scope) {
            if (isset($table->$scope) && $table->$scope === false) {
                continue;
            }

            if (!$this->metadata->get('scopes.' . $scope . '.entity')) {
                continue;
            }

            $fieldList = array_keys($this->metadata->get("entityDefs.{$scope}.fields", []));

            $defaultScopeFieldData = $this->metadata
                ->get('app.'.$this->type.'.'.$defaultsGroupName.'.scopeFieldLevel.' . $scope, []);

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
            if (!isset($table->$scope)) {
                $aclType = $this->metadata->get('scopes.' . $scope . '.' . $this->type);

                if ($aclType === true) {
                    $aclType = $this->defaultAclType;
                }

                if (!empty($aclType)) {
                    $paramDefaultsName = 'scopeLevelTypesDefaults';

                    if ($this->isStrictMode) {
                        $paramDefaultsName = 'scopeLevelTypesStrictDefaults';
                    }

                    $defaultValue = $this->metadata
                        ->get(
                            ['app', $this->type, $paramDefaultsName, $aclType],
                            $this->metadata->get(['app', $this->type, $paramDefaultsName, 'record'])
                        );

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
        }
    }

    protected function applyMandatory(&$table, &$fieldTable) : void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        $data = $this->metadata->get('app.'.$this->type.'.mandatory.scopeLevel', []);

        foreach ($data as $scope => $item) {
            $value = $item;

            if (is_array($item)) {
                $value = (object) $item;
            }

            $table->$scope = $value;
        }

        $mandatoryFieldData = $this->metadata->get('app.'.$this->type.'.mandatory.fieldLevel', []);

        foreach ($this->getScopeList() as $scope) {
            if (isset($table->$scope) && $table->$scope === false) {
                continue;
            }

            if (!$this->metadata->get('scopes.' . $scope . '.entity')) {
                continue;
            }

            $fieldList = array_keys($this->metadata->get("entityDefs.{$scope}.fields", []));

            $mandatoryScopeFieldData = $this->metadata
                ->get('app.'.$this->type.'.mandatory.scopeFieldLevel.' . $scope, []);

            foreach (array_merge($mandatoryFieldData, $mandatoryScopeFieldData) as $field => $f) {
                if (!in_array($field, $fieldList)) {
                    continue;
                }

                if (!isset($fieldTable->$scope)) {
                    $fieldTable->$scope = (object) [];
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
    }

    protected function applyDisabled(&$table, &$fieldTable) : void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        foreach ($this->getScopeList() as $scope) {
            if ($this->metadata->get('scopes.' . $scope . '.disabled')) {
                $table->$scope = false;

                unset($fieldTable->$scope);
            }
        }
    }

    protected function applyAdditional(&$table, &$fieldTable, &$valuePermissionLists) : void
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

    private function mergeValueList(array $list, string $defaultValue) : string
    {
        $result = null;

        foreach ($list as $level) {
            if ($level !== 'not-set') {
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

    protected function getScopeWithAclList() : array
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

    protected function getScopeList() : array
    {
        $scopeList = [];

        $scopes = $this->metadata->get('scopes');

        foreach ($scopes as $scope => $d) {
            $scopeList[] = $scope;
        }

        return $scopeList;
    }

    private function mergeTableList(array $tableList) : StdClass
    {
        $data = (object) [];

        $scopeList = $this->getScopeWithAclList();

        foreach ($tableList as $table) {
            foreach ($scopeList as $scope) {
            	if (!isset($table->$scope)) {
                    continue;
                }

            	$row = $table->$scope;

                if ($row == false) {
                    if (!isset($data->$scope)) {
                        $data->$scope = false;
                    }
                }
                else if ($row === true) {
                    $data->$scope = true;
                }
                else {
                    if (!isset($data->$scope)) {
                        $data->$scope = (object) [];
                    }
                    if ($data->$scope === false) {
                        $data->$scope = (object) [];
                    }

                    if (!is_object($row)) {
                        continue;
                    }

                    $actionList = $this->metadata
                        ->get(['scopes', $scope, $this->type . 'ActionList'], $this->actionList);

                    foreach ($actionList as $i => $action) {
                        if (isset($row->$action)) {
                            $level = $row->$action;

                            if (!isset($data->$scope->$action)) {
                                $data->$scope->$action = $level;
                            }
                            else {
                                if (
                                    array_search($data->$scope->$action, $this->levelList) >
                                    array_search($level, $this->levelList)
                                ) {
                                    $data->$scope->$action = $level;
                                }
                            }
                        } else {
                            if ($i > 0) {
                                // TODO remove it
                                $previousAction = $this->actionList[$i - 1];

                                if (in_array($action, $this->booleanActionList)) {
                                    $data->$scope->$action = self::LEVEL_YES;
                                }
                                else {
                                    if ($action === self::ACTION_STREAM && isset($data->$scope->$previousAction)) {
                                        $data->$scope->$action = $data->$scope->$previousAction;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    private function mergeFieldTableList(array $tableList) : StdClass
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

                $fieldList = array_keys($this->metadata->get("entityDefs.{$scope}.fields", []));

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

    private function buildCache() : void
    {
        $this->dataCache->store($this->cacheKey, $this->data);
    }
}
