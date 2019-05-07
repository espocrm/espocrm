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

namespace Espo\Core\Utils;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Container;

class FieldManager
{
    private $metadata;

    private $language;

    private $metadataHelper;

    protected $isChanged = null;

    private $container;

    protected $forbiddenFieldNameList = ['id', 'deleted', 'skipDuplicateCheck', 'isFollowed', 'null', 'false', 'true'];

    public function __construct(Container $container = null)
    {
        $this->container = $container;

        $this->metadataHelper = new \Espo\Core\Utils\Metadata\Helper($this->getMetadata());
    }

    protected function getMetadata()
    {
        return $this->container->get('metadata');
    }

    protected function getLanguage()
    {
        return $this->container->get('language');
    }

    protected function getBaseLanguage()
    {
        return $this->container->get('baseLanguage');
    }

    protected function getMetadataHelper()
    {
        return $this->metadataHelper;
    }

    protected function getDefaultLanguage()
    {
        return $this->container->get('defaultLanguage');
    }

    protected function getFieldManagerUtil()
    {
        return $this->container->get('fieldManagerUtil');
    }

    public function read($scope, $name)
    {
        $fieldDefs = $this->getFieldDefs($scope, $name);

        $fieldDefs['label'] = $this->getLanguage()->translate($name, 'fields', $scope);

        $type = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('onRead', $type, $scope, $name, $fieldDefs);

        return $fieldDefs;
    }

    public function create($scope, $name, $fieldDefs)
    {
        if (empty($name)) {
            throw new BadRequest();
        }

        if (strlen($name) > 100) {
            throw new Error('Field name should not be longer than 100.');
        }

        if (is_numeric($name[0])) {
            throw new Error('Bad field name.');
        }

        $existingField = $this->getFieldDefs($scope, $name);
        if (isset($existingField)) {
            throw new Conflict('Field ['.$name.'] exists in '.$scope);
        }
        if ($this->getMetadata()->get(['entityDefs', $scope, 'links', $name])) {
            throw new Conflict('Link with name ['.$name.'] exists in '.$scope);
        }
        if (in_array($name, $this->forbiddenFieldNameList)) {
            throw new Conflict('Field ['.$name.'] is not allowed');
        }

        $firstLatter = $name[0];
        if (is_numeric($firstLatter)) {
            throw new Conflict('Field name should begin with a letter');
        }

        return $this->update($scope, $name, $fieldDefs, true);
    }

    public function update($scope, $name, $fieldDefs, $isNew = false)
    {
        $name = trim($name);
        $this->isChanged = false;

        if (!$this->isCore($scope, $name)) {
            $fieldDefs['isCustom'] = true;
        }

        $isCustom = false;
        if (!empty($fieldDefs['isCustom'])) {
            $isCustom = true;
        }

        $result = true;
        $isLabelChanged = false;

        if (isset($fieldDefs['label'])) {
            $this->setLabel($scope, $name, $fieldDefs['label'], $isNew, $isCustom);
            $isLabelChanged = true;
        }
        if (isset($fieldDefs['tooltipText'])) {
            $this->setTooltipText($scope, $name, $fieldDefs['tooltipText'], $isNew, $isCustom);
            $isLabelChanged = true;
        }

        $type = isset($fieldDefs['type']) ? $fieldDefs['type'] : $type = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('beforeSave', $type, $scope, $name, $fieldDefs, array('isNew' => $isNew));

        if ($this->getMetadata()->get(['fields', $type, 'translatedOptions'])) {
            if (isset($fieldDefs['translatedOptions'])) {
                $translatedOptions = $fieldDefs['translatedOptions'];
                $translatedOptions = json_decode(json_encode($fieldDefs['translatedOptions']), true);
                if (isset($translatedOptions['_empty_'])) {
                    $translatedOptions[''] = $translatedOptions['_empty_'];
                    unset($translatedOptions['_empty_']);
                }

                $this->setTranslatedOptions($scope, $name, $translatedOptions, $isNew, $isCustom);
                $isLabelChanged = true;
            }
        }

        if ($isNew) {
            $subFieldsDefs = $this->getMetadata()->get(['fields', $type, 'fields']);
            if ($subFieldsDefs) {
                foreach ($subFieldsDefs as $partField => $partFieldData) {
                    $partLabel = $this->getLanguage()->get('FieldManager.fieldParts.' . $type . '.' . $partField);
                    if ($partLabel) {
                        if ($this->getMetadata()->get(['fields', $type, 'fields', 'naming']) === 'prefix') {
                            $subFieldName = $partField . ucfirst($name);
                            $subFieldLabel = $partLabel . ' ' . $fieldDefs['label'];
                        } else {
                            $subFieldName = $name . ucfirst($partField);
                            $subFieldLabel = $fieldDefs['label'] . ' ' . $partLabel;
                        }
                        $this->setLabel($scope, $subFieldName, $subFieldLabel, $isNew, $isCustom);
                        $isLabelChanged = true;
                    }
                }
            }
        }

        if ($isLabelChanged) {
            $this->getLanguage()->save();
            if ($isNew || $isCustom) {
                if ($this->getBaseLanguage()->getLanguage() !== $this->getLanguage()->getLanguage()) {
                    $this->getBaseLanguage()->save();
                }
            }
        }

        $metadataToBeSaved = false;
        $clientDefsToBeSet = false;

        $clientDefs = array();

        if (array_key_exists('dynamicLogicVisible', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicVisible'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['fields'][$name]['visible'] = $fieldDefs['dynamicLogicVisible'];
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'visible'])) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['fields'][$name]['visible'] = null;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicReadOnly', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicReadOnly'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['fields'][$name]['readOnly'] = $fieldDefs['dynamicLogicReadOnly'];
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'readOnly'])) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['fields'][$name]['readOnly'] = null;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicRequired', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicRequired'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['fields'][$name]['required'] = $fieldDefs['dynamicLogicRequired'];
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'required'])) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['fields'][$name]['required'] = null;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicOptions', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicOptions'])) {
                $this->prepareClientDefsOptionsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['options'][$name] = $fieldDefs['dynamicLogicOptions'];
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'options', $name])) {
                    $this->prepareClientDefsOptionsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['options'][$name] = null;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if ($clientDefsToBeSet) {
            $this->getMetadata()->set('clientDefs', $scope, $clientDefs);
            $metadataToBeSaved = true;
        }

        $entityDefs = $this->normalizeDefs($scope, $name, $fieldDefs);

        if (!empty($entityDefs)) {
            $result &= $this->saveCustomEntityDefs($scope, $entityDefs);
            $this->isChanged = true;
        }

        if ($metadataToBeSaved) {
            $result &= $this->getMetadata()->save();
            $this->isChanged = true;
        }

        if ($this->isChanged) {
            $this->processHook('afterSave', $type, $scope, $name, $fieldDefs, array('isNew' => $isNew));
        }

        return (bool) $result;
    }

    protected function prepareClientDefsFieldsDynamicLogic(&$clientDefs, $name)
    {
        if (!array_key_exists('dynamicLogic', $clientDefs)) {
            $clientDefs['dynamicLogic'] = array();
        }
        if (!array_key_exists('fields', $clientDefs['dynamicLogic'])) {
            $clientDefs['dynamicLogic']['fields'] = array();
        }
        if (!array_key_exists($name, $clientDefs['dynamicLogic']['fields'])) {
            $clientDefs['dynamicLogic']['fields'][$name] = array();
        }
    }

    protected function prepareClientDefsOptionsDynamicLogic(&$clientDefs, $name)
    {
        if (!array_key_exists('dynamicLogic', $clientDefs)) {
            $clientDefs['dynamicLogic'] = array();
        }
        if (!array_key_exists('options', $clientDefs['dynamicLogic'])) {
            $clientDefs['dynamicLogic']['options'] = array();
        }
        if (!array_key_exists($name, $clientDefs['dynamicLogic']['options'])) {
            $clientDefs['dynamicLogic']['options'][$name] = array();
        }
    }

    public function delete($scope, $name)
    {
        if ($this->isCore($scope, $name)) {
            throw new Error('Cannot delete core field ['.$name.'] in '.$scope);
        }

        $type = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('beforeRemove', $type, $scope, $name);

        $unsets = array(
            'fields.'.$name,
            'links.'.$name,
        );

        $this->getMetadata()->delete('entityDefs', $scope, $unsets);

        $this->getMetadata()->delete('clientDefs', $scope, [
            'dynamicLogic.fields.' . $name,
            'dynamicLogic.options.' . $name
        ]);

        $res = $this->getMetadata()->save();
        $this->deleteLabel($scope, $name);

        $subFieldsDefs = $this->getMetadata()->get(['fields', $type, 'fields']);
        if ($subFieldsDefs) {
            foreach ($subFieldsDefs as $partField => $partFieldData) {
                if ($this->getMetadata()->get(['fields', $type, 'fields', 'naming']) === 'prefix') {
                    $subFieldName = $partField . ucfirst($name);
                } else {
                    $subFieldName = $name . ucfirst($partField);
                }
                $this->deleteLabel($scope, $subFieldName);
            }
        }

        $this->getLanguage()->save();
        if ($this->getBaseLanguage()->getLanguage() !== $this->getLanguage()->getLanguage()) {
            $this->getBaseLanguage()->save();
        }

        $this->processHook('afterRemove', $type, $scope, $name);

        return (bool) $res;
    }

    public function resetToDefault($scope, $name)
    {
        if (!$this->isCore($scope, $name)) {
            throw new Error('Cannot reset to default custom field ['.$name.'] in '.$scope);
        }

        if (!$this->getMetadata()->get(['entityDefs', $scope, 'fields', $name])) {
            throw new Error('Not found field ['.$name.'] in '.$scope);
        }

        $this->getMetadata()->delete('entityDefs', $scope, ['fields.' . $name]);
        $this->getMetadata()->delete('clientDefs', $scope, [
            'dynamicLogic.fields.' . $name,
            'dynamicLogic.options.' . $name
        ]);
        $this->getMetadata()->save();

        $this->getLanguage()->delete($scope, 'fields', $name);
        $this->getLanguage()->delete($scope, 'options', $name);
        $this->getLanguage()->delete($scope, 'tooltips', $name);

        $this->getLanguage()->save();
    }

    protected function setTranslatedOptions($scope, $name, $value, $isNew, $isCustom)
    {
        if ($isNew || $isCustom) {
            $this->getBaseLanguage()->set($scope, 'options', $name, $value);
        }

        $this->getLanguage()->set($scope, 'options', $name, $value);
    }

    protected function setLabel($scope, $name, $value, $isNew, $isCustom)
    {
        if ($isNew || $isCustom) {
            $this->getBaseLanguage()->set($scope, 'fields', $name, $value);
        }

        $this->getLanguage()->set($scope, 'fields', $name, $value);
    }

    protected function setTooltipText($scope, $name, $value, $isNew, $isCustom)
    {
        if ($value && $value !== '') {
            $this->getLanguage()->set($scope, 'tooltips', $name, $value);
            $this->getBaseLanguage()->set($scope, 'tooltips', $name, $value);
        } else {
            $this->getLanguage()->delete($scope, 'tooltips', $name);
            $this->getBaseLanguage()->delete($scope, 'tooltips', $name);
        }
    }

    protected function deleteLabel($scope, $name)
    {
        $this->getLanguage()->delete($scope, 'fields', $name);
        $this->getLanguage()->delete($scope, 'tooltips', $name);
        $this->getLanguage()->delete($scope, 'options', $name);

        $this->getBaseLanguage()->delete($scope, 'fields', $name);
        $this->getBaseLanguage()->delete($scope, 'tooltips', $name);
        $this->getBaseLanguage()->delete($scope, 'options', $name);
    }

    protected function getFieldDefs($scope, $name, $default = null)
    {
        return $this->getMetadata()->get('entityDefs'.'.'.$scope.'.fields.'.$name, $default);
    }

    protected function getCustomFieldDefs($scope, $name)
    {
        $customDefs = $this->getMetadata()->getCustom('entityDefs', $scope, (object) []);

        if (isset($customDefs->fields->$name)) {
            return (array) $customDefs->fields->$name;
        }
    }

    protected function saveCustomEntityDefs($scope, $newDefs)
    {
        $customDefs = $this->getMetadata()->getCustom('entityDefs', $scope, (object) []);

        if (isset($newDefs->fields)) {
            foreach ($newDefs->fields as $name => $defs) {
                if (!isset($customDefs->fields)) {
                    $customDefs->fields = new \StdClass();
                }

                $customDefs->fields->$name = $defs;
            }
        }

        if (isset($newDefs->links)) {
            foreach ($newDefs->links as $name => $defs) {
                if (!isset($customDefs->links)) {
                    $customDefs->links = new \StdClass();
                }

                $customDefs->links->$name = $defs;
            }
        }

        return $this->getMetadata()->saveCustom('entityDefs', $scope, $customDefs);
    }

    protected function getLinkDefs($scope, $name)
    {
        return $this->getMetadata()->get('entityDefs'.'.'.$scope.'.links.'.$name);
    }

    /**
     * Prepare input fieldDefs, remove unnecessary fields
     *
     * @param string $fieldName
     * @param array $fieldDefs
     * @param string $scope
     * @return array
     */
    protected function prepareFieldDefs($scope, $name, $fieldDefs)
    {
        $additionalParamList = [
            'type' => [
                'type' => 'varchar'
            ],
            'isCustom' => [
                'type' => 'bool',
                'default' => false
            ],
            'isPersonalData' => [
                'type' => 'bool',
                'default' => false
            ],
            'tooltip' => [
                'type' => 'bool',
                'default' => false
            ],
            'inlineEditDisabled' => [
                'type' => 'bool',
                'default' => false
            ],
            'defaultAttributes' => [
                'type' => 'jsonObject'
            ]
        ];

        if (isset($fieldDefs['fieldManagerAdditionalParamList'])) {
            foreach ($fieldDefs['fieldManagerAdditionalParamList'] as $additionalParam) {
                $additionalParamList[$additionalParam->name] = [
                    'type' => $fieldDefs['type']
                ];
            }
        }

        $fieldDefsByType = $this->getMetadataHelper()->getFieldDefsByType($fieldDefs);
        if (!isset($fieldDefsByType['params'])) {
            return $fieldDefs;
        }

        $params = [];
        foreach ($fieldDefsByType['params'] as $paramData) {
            $params[$paramData['name']] = $paramData;
        }
        foreach ($additionalParamList as $paramName => $paramValue) {
            if (!isset($params[$paramName])) {
                $params[$paramName] = array_merge(['name' => $paramName], $paramValue);
            }
        }

        $actualCustomFieldDefs = $this->getCustomFieldDefs($scope, $name);
        $actualFieldDefs = $this->getFieldDefs($scope, $name, []);
        $permittedParamList = array_keys($params);

        $filteredFieldDefs = $actualCustomFieldDefs ? $actualCustomFieldDefs : [];

        foreach ($fieldDefs as $paramName => $paramValue) {
            if (in_array($paramName, $permittedParamList)) {
                switch ($params[$paramName]['type']) {
                    case 'bool':
                        $fieldDefsDefaultValue = array_key_exists('default', $params[$paramName]) ? $params[$paramName]['default'] : false;

                        $actualValue = array_key_exists($paramName, $actualFieldDefs) ? $actualFieldDefs[$paramName] : $fieldDefsDefaultValue;

                        if (!Util::areValuesEqual($actualValue, $paramValue)) {
                            $filteredFieldDefs[$paramName] = $paramValue;
                        }
                        break;

                    default:
                        if (!array_key_exists('default', $params[$paramName]) && !array_key_exists($paramName, $actualFieldDefs)) {
                            $filteredFieldDefs[$paramName] = $paramValue;
                            break;
                        }

                        if (array_key_exists('default', $params[$paramName])) {
                            $actualValue = $params[$paramName]['default'];
                        }

                        if (array_key_exists($paramName, $actualFieldDefs)) {
                            $actualValue = $actualFieldDefs[$paramName];
                        }

                        if (!Util::areValuesEqual($actualValue, $paramValue)) {
                            $filteredFieldDefs[$paramName] = $paramValue;
                        }
                        break;
                }
            }
        }

        $metaFieldDefs = $this->getMetadataHelper()->getFieldDefsInFieldMeta($filteredFieldDefs);
        if (isset($metaFieldDefs)) {
            $filteredFieldDefs = Util::merge($metaFieldDefs, $filteredFieldDefs);
        }

        if ($actualCustomFieldDefs) {
            $actualCustomFieldDefs = array_diff_key($actualCustomFieldDefs, array_flip($permittedParamList));
            foreach ($actualCustomFieldDefs as $paramName => $paramValue) {
                if (!array_key_exists($paramName, $filteredFieldDefs)) {
                    $filteredFieldDefs[$paramName] = $paramValue;
                }
            }
        }

        return $filteredFieldDefs;
    }

    /**
     * Add all needed block for a field definition
     *
     * @param string $scope
     * @param string $fieldName
     * @param array $fieldDefs
     * @return array
     */
    protected function normalizeDefs($scope, $fieldName, array $fieldDefs)
    {
        $defs = new \stdClass();

        $normalizedFieldDefs = $this->prepareFieldDefs($scope, $fieldName, $fieldDefs);

        if (!empty($normalizedFieldDefs)) {
            $defs->fields = (object) array(
                $fieldName => (object) $normalizedFieldDefs,
            );
        }

        /** Save links for a field. */
        $linkDefs = isset($fieldDefs['linkDefs']) ? $fieldDefs['linkDefs'] : null;
        $metaLinkDefs = $this->getMetadataHelper()->getLinkDefsInFieldMeta($scope, $fieldDefs);

        if (isset($linkDefs) || isset($metaLinkDefs)) {
            $metaLinkDefs = isset($metaLinkDefs) ? $metaLinkDefs : array();
            $linkDefs = isset($linkDefs) ? $linkDefs : array();

            $normalizedLinkdDefs = Util::merge($metaLinkDefs, $linkDefs);
            if (!empty($normalizedLinkdDefs)) {
                $defs->links = (object) array(
                    $fieldName => (object) $normalizedLinkdDefs,
                );
            }
        }

        return $defs;
    }

    protected function isLabelChanged($scope, $category, $name, $newLabel)
    {
         $currentLabel = $this->getLanguage()->get([$scope, $category, $name]);

         if ($newLabel != $currentLabel) {
            return true;
         }

         return false;
    }

    public function isChanged()
    {
        return $this->isChanged;
    }

    /**
     * Check if a field is core field
     *
     * @param  string  $name
     * @param  string  $scope
     * @return boolean
     */
    protected function isCore($scope, $name)
    {
        $existingField = $this->getFieldDefs($scope, $name);
        if (isset($existingField) && (!isset($existingField['isCustom']) || !$existingField['isCustom'])) {
            return true;
        }

        return false;
    }

    private function getAttributeListByType($scope, $name, $type)
    {
        $fieldType = $this->getMetadata()->get('entityDefs.' . $scope . '.fields.' . $name . '.type');
        if (!$fieldType) return [];

        $defs = $this->getMetadata()->get('fields.' . $fieldType);
        if (!$defs) return [];
        if (is_object($defs)) {
            $defs = get_object_vars($defs);
        }

        $fieldList = [];

        if (isset($defs[$type . 'Fields'])) {
            $list = $defs[$type . 'Fields'];
            $naming = 'suffix';
            if (isset($defs['naming'])) {
                $naming = $defs['naming'];
            }
            if ($naming == 'prefix') {
                foreach ($list as $f) {
                    $fieldList[] = $f . ucfirst($name);
                }
            } else {
                foreach ($list as $f) {
                    $fieldList[] = $name . ucfirst($f);
                }
            }
        } else {
            if ($type == 'actual') {
                $fieldList[] = $name;
            }
        }

        return $fieldList;
    }

    public function getActualAttributeList($scope, $name)
    {
        return $this->getAttributeListByType($scope, $name, 'actual');
    }

    public function getNotActualAttributeList($scope, $name)
    {
        return $this->getAttributeListByType($scope, $name, 'notActual');
    }

    public function getAttributeList($scope, $name)
    {
        return array_merge($this->getActualAttributeList($scope, $name), $this->getNotActualAttributeList($scope, $name));
    }

    protected function processHook($methodName, $type, $scope, $name, &$defs = null, $options = array())
    {
        $hook = $this->getHook($type);
        if (!$hook) return;

        if (!method_exists($hook, $methodName)) return;

        $hook->$methodName($scope, $name, $defs, $options);
    }

    protected function getHook($type)
    {
        $className = $this->getMetadata()->get(['fields', $type, 'hookClassName']);

        if (!$className) return;

        if (class_exists($className)) {
            $hook = new $className();
            foreach ($hook->getDependencyList() as $name) {
                $hook->inject($name, $this->container->get($name));
            }
            return $hook;
        }
        $GLOBALS['log']->error("Field Manager hook class '{$className}' does not exist.");
        return;
    }
}
