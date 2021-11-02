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

namespace Espo\Core\Acl\Map;

use Espo\Entities\User;

use Espo\Core\{
    Acl\Table,
    Utils\Config,
    Utils\DataCache,
    Utils\ObjectUtil,
};

use stdClass;
use RuntimeException;

/**
 * Provides quick access to ACL data.
 */
class Map
{
    private $data;

    private $cacheKey;

    private $forbiddenFieldsCache = [];

    private $forbiddenAttributesCache;

    private $fieldLevelList = [
        Table::LEVEL_YES,
        Table::LEVEL_NO,
    ];

    private $user;

    private $table;

    private $config;

    private $dataCache;

    private $dataBuilder;

    public function __construct(
        User $user,
        Table $table,
        DataBuilder $dataBuilder,
        Config $config,
        DataCache $dataCache,
        CacheKeyProvider $cacheKeyProvider
    ) {
        $this->user = $user;
        $this->table = $table;
        $this->dataBuilder = $dataBuilder;
        $this->config = $config;
        $this->dataCache = $dataCache;

        $this->cacheKey = $cacheKeyProvider->get();

        if ($this->config->get('useCache') && $this->dataCache->has($this->cacheKey)) {
            $this->data = $this->dataCache->get($this->cacheKey);
        }
        else {
            $this->data = $this->dataBuilder->build($table);

            if ($this->config->get('useCache')) {
                $this->dataCache->store($this->cacheKey, $this->data);
            }
        }
    }

    /**
     * Get raw data (for front-end).
     */
    public function getData(): stdClass
    {
        return ObjectUtil::clone($this->data);
    }

    /**
     * Get a list of forbidden attributes for a scope and action.
     *
     * @param string $scope A scope.
     * @param string $action An action.
     * @param string $thresholdLevel An attribute will be treated as forbidden if the level is
     * equal to or lower than the threshold.
     * @return string[]
     */
    public function getScopeForbiddenAttributeList(
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        if (
            !in_array($thresholdLevel, $this->fieldLevelList) ||
            $thresholdLevel === Table::LEVEL_YES
        ) {
            throw new RuntimeException("Bad threshold level.");
        }

        $key = $scope . '_'. $action . '_' . $thresholdLevel;

        if (isset($this->forbiddenAttributesCache[$key])) {
            return $this->forbiddenAttributesCache[$key];
        }

        $fieldTableQuickAccess = $this->data->fieldTableQuickAccess;

        if (
            !isset($fieldTableQuickAccess->$scope) ||
            !isset($fieldTableQuickAccess->$scope->attributes) ||
            !isset($fieldTableQuickAccess->$scope->attributes->$action)
        ) {
            $this->forbiddenAttributesCache[$key] = [];

            return [];
        }

        $levelList = [];

        foreach ($this->fieldLevelList as $level) {
            if (
                array_search($level, $this->fieldLevelList) >=
                array_search($thresholdLevel, $this->fieldLevelList)
            ) {
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

    /**
     * Get a list of forbidden fields for a scope and action.
     *
     * @param string $scope A scope.
     * @param string $action An action.
     * @param string $thresholdLevel An attribute will be treated as forbidden if the level is
     * equal to or lower than the threshold.
     * @return string[]
     */
    public function getScopeForbiddenFieldList(
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        if (
            !in_array($thresholdLevel, $this->fieldLevelList) ||
            $thresholdLevel === Table::LEVEL_YES
        ) {
            throw new RuntimeException("Bad threshold level.");
        }

        $key = $scope . '_'. $action . '_' . $thresholdLevel;

        if (isset($this->forbiddenFieldsCache[$key])) {
            return $this->forbiddenFieldsCache[$key];
        }

        $fieldTableQuickAccess = $this->data->fieldTableQuickAccess;

        if (
            !isset($fieldTableQuickAccess->$scope) ||
            !isset($fieldTableQuickAccess->$scope->fields) ||
            !isset($fieldTableQuickAccess->$scope->fields->$action)
        ) {
            $this->forbiddenFieldsCache[$key] = [];

            return [];
        }

        $levelList = [];

        foreach ($this->fieldLevelList as $level) {
            if (
                array_search($level, $this->fieldLevelList) >=
                array_search($thresholdLevel, $this->fieldLevelList)
            ) {
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
}
