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

class FieldManager
{
    private $metadata;

    private $language;

    private $metadataHelper;

    protected $isChanged = null;

    protected $metadataType = 'entityDefs';

    protected $customOptionName = 'isCustom';

    public function __construct(Metadata $metadata, Language $language)
    {
        $this->metadata = $metadata;
        $this->language = $language;

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

    public function read($name, $scope)
    {
        $fieldDefs = $this->getFieldDefs($name, $scope);

        $fieldDefs['label'] = $this->getLanguage()->translate($name, 'fields', $scope);

        return $fieldDefs;
    }

    public function create($name, $fieldDefs, $scope)
    {
        $existingField = $this->getFieldDefs($name, $scope);
        if (isset($existingField)) {
            throw new Conflict('Field ['.$name.'] exists in '.$scope);
        }

        return $this->update($name, $fieldDefs, $scope);
    }

    public function update($name, $fieldDefs, $scope)
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

        if (isset($fieldDefs['type']) && ($fieldDefs['type'] == 'enum' || $fieldDefs['type'] == 'phone')) {
            if (isset($fieldDefs['translatedOptions'])) {
                $this->setTranslatedOptions($name, $fieldDefs['translatedOptions'], $scope);
            }
        }

        if (isset($fieldDefs['label']) || isset($fieldDefs['translatedOptions'])) {
            $res &= $this->getLanguage()->save();
        }

        if ($this->isDefsChanged($name, $fieldDefs, $scope)) {
            $res &= $this->setEntityDefs($name, $fieldDefs, $scope);
        }

        return (bool) $res;
    }

    public function delete($name, $scope)
    {
        if ($this->isCore($name, $scope)) {
            throw new Error('Cannot delete core field ['.$name.'] in '.$scope);
        }

        $unsets = array(
            'fields.'.$name,
            'links.'.$name,
        );

        $this->getMetadata()->delete($this->metadataType, $scope, $unsets);
        $res = $this->getMetadata()->save();
        $res &= $this->deleteLabel($name, $scope);

        return (bool) $res;
    }

    protected function setEntityDefs($name, $fieldDefs, $scope)
    {
        $fieldDefs = $this->normalizeDefs($name, $fieldDefs, $scope);

        $this->getMetadata()->set($this->metadataType, $scope, $fieldDefs);
        $res = $this->getMetadata()->save();

        return $res;
    }

    protected function setTranslatedOptions($name, $value, $scope)
    {
        return $this->getLanguage()->set($scope, 'options', $name, $value);
    }

    protected function setLabel($name, $value, $scope)
    {
        return $this->getLanguage()->set($scope, 'fields', $name, $value);
    }

    protected function deleteLabel($name, $scope)
    {
        $this->getLanguage()->delete($scope, 'fields', $name);
        $this->getLanguage()->delete($scope, 'options', $name);
        return $this->getLanguage()->save();
    }

    protected function getFieldDefs($name, $scope)
    {
        return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.fields.'.$name);
    }

    protected function getLinkDefs($name, $scope)
    {
        return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.links.'.$name);
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
}