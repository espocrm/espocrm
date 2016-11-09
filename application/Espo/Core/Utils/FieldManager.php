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

namespace Espo\Core\Utils;
use \Espo\Core\Exceptions\Error,
    \Espo\Core\Exceptions\Conflict;

use \Espo\Core\Container;

class FieldManager
{
    private $metadata;

    private $language;

    private $metadataHelper;

    protected $isChanged = null;

    private $container;

    protected $metadataType = 'entityDefs';

    protected $customOptionName = 'isCustom';

    public function __construct(Metadata $metadata, Language $language, Container $container = null)
    {
        $this->metadata = $metadata;
        $this->language = $language;
        $this->container = $container;

        $this->metadataHelper = new \Espo\Core\Utils\Metadata\Helper($this->metadata);
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getMetadataHelper()
    {
        return $this->metadataHelper;
    }

    protected function getDefaultLanguage()
    {
        return $this->container->get('defaultLanguage');
    }

    public function read($name, $scope)
    {
        $fieldDefs = $this->getFieldDefs($name, $scope);

        $fieldDefs['label'] = $this->getLanguage()->translate($name, 'fields', $scope);

        $type = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('onRead', $type, $scope, $name, $fieldDefs);

        return $fieldDefs;
    }

    public function create($name, $fieldDefs, $scope)
    {
        $existingField = $this->getFieldDefs($name, $scope);
        if (isset($existingField)) {
            throw new Conflict('Field ['.$name.'] exists in '.$scope);
        }

        return $this->update($name, $fieldDefs, $scope, true);
    }

    public function update($name, $fieldDefs, $scope, $isNew = false)
    {
        $name = trim($name);
        /*Add option to metadata to identify the custom field*/
        if (!$this->isCore($name, $scope)) {
            $fieldDefs[$this->customOptionName] = true;
        }

        $res = true;
        if (isset($fieldDefs['label'])) {
            $this->setLabel($name, $fieldDefs['label'], $scope);
        }
        if (isset($fieldDefs['tooltipText'])) {
            $this->setTooltipText($name, $fieldDefs['tooltipText'], $scope);
        }

        $type = isset($fieldDefs['type']) ? $fieldDefs['type'] : $type = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('beforeSave', $type, $scope, $name, $fieldDefs);

        if ($this->getMetadata()->get(['fields', $type, 'translatedOptions'])) {
            if (isset($fieldDefs['translatedOptions'])) {
                $this->setTranslatedOptions($name, $fieldDefs['translatedOptions'], $scope);
            }
        }

        if (
            isset($fieldDefs['label']) || isset($fieldDefs['translatedOptions']) || isset($fieldDefs['tooltipText'])
        ) {
            $res &= $this->getLanguage()->save();

            if (isset($fieldDefs['tooltipText'])) {
                $this->getDefaultLanguage()->save();
            }
        }

        $metadataToBeSaved = false;
        $clientDefsToBeSet = false;

        $clientDefs = array();

        if (array_key_exists('dynamicLogicVisible', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicVisible'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['fields'][$name]['visible'] = array(
                    'conditionGroup' => $fieldDefs['dynamicLogicVisible']
                );
                $metadataToBeSaved = true;
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'visible'])) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['fields'][$name]['visible'] = null;
                    $metadataToBeSaved = true;
                    $clientDefsToBeSet = true;
                }
            }

        }

        if (array_key_exists('dynamicLogicReadOnly', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicReadOnly'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['fields'][$name]['readOnly'] = array(
                    'conditionGroup' => $fieldDefs['dynamicLogicReadOnly']
                );
                $metadataToBeSaved = true;
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'readOnly'])) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['fields'][$name]['readOnly'] = null;
                    $metadataToBeSaved = true;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicRequired', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicRequired'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['fields'][$name]['required'] = array(
                    'conditionGroup' => $fieldDefs['dynamicLogicRequired']
                );
                $metadataToBeSaved = true;
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'required'])) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['fields'][$name]['required'] = null;
                    $metadataToBeSaved = true;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicOptions', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicOptions'])) {
                $this->prepareClientDefsOptionsDynamicLogic($clientDefs, $name);
                $clientDefs['dynamicLogic']['options'][$name] = $fieldDefs['dynamicLogicOptions'];
                $metadataToBeSaved = true;
                $clientDefsToBeSet = true;
            } else {
                if ($this->getMetadata()->get(['clientDefs', $scope, 'dynamicLogic', 'options', $name])) {
                    $this->prepareClientDefsOptionsDynamicLogic($clientDefs, $name);
                    $clientDefs['dynamicLogic']['options'][$name] = null;
                    $metadataToBeSaved = true;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if ($clientDefsToBeSet) {
            $this->getMetadata()->set('clientDefs', $scope, $clientDefs);
        }

        if ($this->isDefsChanged($name, $fieldDefs, $scope)) {
            $entityDefs = $this->normalizeDefs($name, $fieldDefs, $scope);
            $this->getMetadata()->set('entityDefs', $scope, $entityDefs);
            $metadataToBeSaved = true;
        }

        if ($metadataToBeSaved) {
            $res &= $this->getMetadata()->save();

            $this->processHook('afterSave', $type, $scope, $name, $fieldDefs, array('isNew' => $isNew));
        }

        return (bool) $res;
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

    public function delete($name, $scope)
    {
        if ($this->isCore($name, $scope)) {
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
        $res &= $this->deleteLabel($name, $scope);

        $this->processHook('afterRemove', $type, $scope, $name);

        return (bool) $res;
    }

    public function resetToDefault($name, $scope)
    {
        if (!$this->isCore($name, $scope)) {
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
        $this->getDefaultLanguage()->delete($scope, 'tooltips', $name);

        $this->getLanguage()->save();
        $this->getDefaultLanguage()->save();
    }

    protected function setEntityDefs($name, $fieldDefs, $scope)
    {
        $fieldDefs = $this->normalizeDefs($name, $fieldDefs, $scope);

        $this->getMetadata()->set('entityDefs', $scope, $fieldDefs);
        $res = $this->getMetadata()->save();

        return $res;
    }

    protected function setTranslatedOptions($name, $value, $scope)
    {
        $this->getLanguage()->set($scope, 'options', $name, $value);
    }

    protected function setLabel($name, $value, $scope)
    {
        return $this->getLanguage()->set($scope, 'fields', $name, $value);
    }

    protected function setTooltipText($name, $value, $scope)
    {
        if ($value && $value !== '') {
            $this->getLanguage()->set($scope, 'tooltips', $name, $value);
            $this->getDefaultLanguage()->set($scope, 'tooltips', $name, $value);
        } else {
            $this->getLanguage()->delete($scope, 'tooltips', $name);
            $this->getDefaultLanguage()->delete($scope, 'tooltips', $name);
        }
    }

    protected function deleteLabel($name, $scope)
    {
        $this->getLanguage()->delete($scope, 'fields', $name);
        $this->getLanguage()->delete($scope, 'tooltips', $name);
        $this->getLanguage()->delete($scope, 'options', $name);
        $this->getDefaultLanguage()->delete($scope, 'tooltips', $name);

        $this->getLanguage()->save();
        $this->getDefaultLanguage()->save();
    }

    protected function getFieldDefs($name, $scope)
    {
        return $this->getMetadata()->get('entityDefs'.'.'.$scope.'.fields.'.$name);
    }

    protected function getLinkDefs($name, $scope)
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
    protected function prepareFieldDefs($name, $fieldDefs, $scope)
    {
        $unnecessaryFields = array(
            'name',
            'label',
            'translatedOptions',
        );

        foreach ($unnecessaryFields as $fieldName) {
            if (isset($fieldDefs[$fieldName])) {
                unset($fieldDefs[$fieldName]);
            }
        }

        $currentOptionList = array_keys((array) $this->getFieldDefs($name, $scope));
        foreach ($fieldDefs as $defName => $defValue) {
            if ( (!isset($defValue) || $defValue === '') && !in_array($defName, $currentOptionList) ) {
                unset($fieldDefs[$defName]);
            }
        }

        return $fieldDefs;
    }

    /**
     * Add all needed block for a field defenition
     *
     * @param string $fieldName
     * @param array $fieldDefs
     * @param string $scope
     * @return array
     */
    protected function normalizeDefs($fieldName, array $fieldDefs, $scope)
    {
        $fieldDefs = $this->prepareFieldDefs($fieldName, $fieldDefs, $scope);

        $metaFieldDefs = $this->getMetadataHelper()->getFieldDefsInFieldMeta($fieldDefs);
        if (isset($metaFieldDefs)) {
            $fieldDefs = Util::merge($metaFieldDefs, $fieldDefs);
        }

        if (isset($fieldDefs['linkDefs'])) {
            $linkDefs = $fieldDefs['linkDefs'];
            unset($fieldDefs['linkDefs']);
        }

        $defs = array(
            'fields' => array(
                $fieldName => $fieldDefs,
            ),
        );

        /** Save links for a field. */
        $metaLinkDefs = $this->getMetadataHelper()->getLinkDefsInFieldMeta($scope, $fieldDefs);
        if (isset($linkDefs) || isset($metaLinkDefs)) {
            $linkDefs = Util::merge((array) $metaLinkDefs, (array) $linkDefs);
            $defs['links'] = array(
                $fieldName => $linkDefs,
            );
        }

        return $defs;
    }

    /**
     * Check if changed metadata defenition for a field except 'label'
     *
     * @return boolean
     */
    protected function isDefsChanged($name, $fieldDefs, $scope)
    {
        $fieldDefs = $this->prepareFieldDefs($name, $fieldDefs, $scope);
        $currentFieldDefs = $this->getFieldDefs($name, $scope);

        $this->isChanged = Util::isEquals($fieldDefs, $currentFieldDefs) ? false : true;

        return $this->isChanged;
    }

    /**
     * Only for update method
     *
     * @return boolean
     */
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
    protected function isCore($name, $scope)
    {
        $existingField = $this->getFieldDefs($name, $scope);
        if (isset($existingField) && (!isset($existingField[$this->customOptionName]) || !$existingField[$this->customOptionName])) {
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