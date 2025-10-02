<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\LabelManager;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\DataUtil;
use Espo\Core\Utils\Json;
use Espo\Core\Di;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Language;

use RuntimeException;
use stdClass;

class LabelManager implements
    Di\DefaultLanguageAware,
    Di\MetadataAware,
    Di\FileManagerAware,
    Di\DataCacheAware
{
    use Di\DefaultLanguageSetter;
    use Di\MetadataSetter;
    use Di\FileManagerSetter;
    use Di\DataCacheSetter;

    /** @var string[] */
    protected $ignoreList = [
        'Global.sets',
    ];

    public function __construct(private InjectableFactory $injectableFactory)
    {}

    /**
     * @return string[]
     */
    public function getScopeList(): array
    {
        $scopeList = [];

        $languageObj = $this->defaultLanguage;

        $data = $languageObj->getAll();

        foreach (array_keys($data) as $scope) {
            if (!in_array($scope, $scopeList)) {
                $scopeList[] = $scope;
            }
        }

        foreach ($this->metadata->get('scopes') as $scope => $data) {
            if (!in_array($scope, $scopeList)) {
                $scopeList[] = $scope;
            }
        }

        return $scopeList;
    }

    public function getScopeData(string $language, string $scope): stdClass
    {
        $languageObj = $this->injectableFactory->createWith(Language::class, [
            'language' => $language,
            'noFallback' => true,
        ]);

        $data = $languageObj->get($scope);

        /*if (empty($data)) {
            return (object) [];
        }*/

        if ($this->metadata->get(['scopes', $scope, 'entity'])) {
            if (empty($data['fields'])) {
                $data['fields'] = [];
            }

            foreach ($this->metadata->get(['entityDefs', $scope, 'fields']) as $field => $item) {
                if (!array_key_exists($field, $data['fields'])) {
                    $data['fields'][$field] = $languageObj->get("Global.fields.$field");

                    if (is_null($data['fields'][$field])) {
                        $data['fields'][$field] = '';
                    }
                }
            }

            if (empty($data['links'])) {
                $data['links'] = [];
            }

            foreach ($this->metadata->get(['entityDefs', $scope, 'links']) as $link => $item) {
                if (!array_key_exists($link, $data['links'])) {
                    $data['links'][$link] = $languageObj->get("Global.links.$link");

                    if (is_null($data['links'][$link])) {
                        $data['links'][$link] = '';
                    }
                }
            }

            if (empty($data['labels'])) {
                $data['labels'] = [];
            }

            if (!array_key_exists('Create ' . $scope, $data['labels'])) {
                $data['labels']['Create ' . $scope] = '';
            }
        }

        foreach ($this->metadata->get(['entityDefs', $scope, 'fields'], []) as $field => $item) {
            if (
                !$this->metadata->get("entityDefs.$scope.fields.$field.options") ||
                $this->metadata->get("entityDefs.$scope.fields.$field.type") === FieldType::VARCHAR
            ) {
                continue;
            }

            $optionsData = [];
            $optionList = $this->metadata->get("entityDefs.$scope.fields.$field.options", []);

            if (!array_key_exists('options', $data)) {
                $data['options'] = [];
            }

            if (!array_key_exists($field, $data['options'])) {
                $data['options'][$field] = [];
            }
            foreach ($optionList as $option) {
                if (empty($option)) {
                    continue;
                }

                $optionsData[$option] = '';

                if (
                    array_key_exists($option, $data['options'][$field]) &&
                    isset($data['options'][$field][$option])
                ) {
                    $optionsData[$option] = $data['options'][$field][$option];
                }
            }
            $data['options'][$field] = $optionsData;
        }

        if ($scope === 'Global') {
            if (empty($data['scopeNames'])) {
                $data['scopeNames'] = [];
            }

            if (empty($data['scopeNamesPlural'])) {
                $data['scopeNamesPlural'] = [];
            }

            foreach ($this->metadata->get(['scopes']) as $scopeKey => $item) {
                if (!empty($item['entity'])) {
                    if (empty($data['scopeNamesPlural'][$scopeKey])) {
                        $data['scopeNamesPlural'][$scopeKey] = '';
                    }
                }

                if (empty($data['scopeNames'][$scopeKey])) {
                    $data['scopeNames'][$scopeKey] = '';
                }
            }
        }

        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        $finalData = [];

        foreach ($data as $category => $item) {
            if (in_array("$scope.$category", $this->ignoreList)) {
                continue;
            }

            foreach ($item as $key => $categoryItem) {
                if (is_array($categoryItem)) {
                    foreach ($categoryItem as $subKey => $subItem) {
                        $finalData[$category]["{$category}[.]{$key}[.]$subKey"] = $subItem;
                    }
                } else {
                    $finalData[$category]["{$category}[.]$key"] = $categoryItem;
                }
            }
        }

        return json_decode(Json::encode((object) $finalData));
    }

    /**
     * @param array<string, string> $labels
     */
    public function saveLabels(string $language, string $scope, array $labels): stdClass
    {
        $languageObj = $this->injectableFactory->createWith(Language::class, [
            'language' => $language,
            'noFallback' => true,
        ]);

        $languageNoCustomObj = $this->injectableFactory->createWith(Language::class, [
            'language' => $language,
            'noFallback' => true,
            'noCustom' => true,
        ]);

        $languageMainObj = $this->injectableFactory->createWith(Language::class, [
            'language' => $language,
        ]);

        $customData = $languageObj->getScopeCustom($scope) ?? (object) [];

        $listPaths = [];
        $lists = [];

        foreach ($labels as $key => $value) {
            $arr = explode('[.]', $key);
            $category = $arr[0];
            $name = $arr[1];

            if (count($arr) === 2) {
                if ($value !== '') {
                    DataUtil::setByPath($customData, [$category, $name], $value);
                } else {
                    DataUtil::unsetByKey($customData, [[$category, $name]], true);
                    /** @var stdClass $customData */
                }

                continue;
            }

            if (count($arr) !== 3) {
                continue;
            }

            $attribute = $arr[2];

            $stringPath = "$category.$name";

            $setValue = $lists[$stringPath] ?? $languageMainObj->get([$scope, $category, $name]);

            if (is_array($setValue) && array_is_list($setValue)) {
                if (!in_array($stringPath, $listPaths)) {
                    $listPaths[] = $stringPath;
                }

                $index = intval($attribute);

                $list = $setValue;

                if ($value === '') {
                    $originalList = $languageNoCustomObj->get([$scope, $category, $name]);

                    if (!array_is_list($originalList)) {
                        throw new RuntimeException("Not a list.");
                    }

                    if (!array_key_exists($index, $originalList)) {
                        throw new RuntimeException("No value in original list.");
                    }

                    $value = $originalList[$index];
                }

                if (!array_key_exists($index, $list)) {
                    throw new RuntimeException("No value in list.");
                }

                $list[$index] = $value;

                DataUtil::setByPath($customData, [$category, $name], $list);
                /** @var stdClass $customData */

                $lists[$stringPath] = $list;

                continue;
            }

            if ($value !== '') {
                DataUtil::setByPath($customData, [$category, $name, $attribute], $value);
            } else {
                DataUtil::unsetByKey($customData, [[$category, $name, $attribute]], true);
                /** @var stdClass $customData */
            }
        }

        foreach ($lists as $path => $list) {
            $originalList = $languageNoCustomObj->get("$scope.$path");

            if ($originalList === $list) {
                DataUtil::unsetByKey($customData, [$path], true);
                /** @var stdClass $customData */
            }
        }

        //die;

        $languageObj->saveScopeCustom($scope, $customData);

        return $this->getScopeData($language, $scope);
    }
}
