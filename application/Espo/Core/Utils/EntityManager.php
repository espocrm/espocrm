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

namespace Espo\Core\Utils;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Conflict;
use \Espo\Core\Utils\Json;

class EntityManager
{
    private $metadata;

    private $language;

    private $fileManager;

    private $metadataUtils;

    protected $isChanged = null;

    public function __construct(Metadata $metadata, Language $language, File\Manager $fileManager)
    {
        $this->metadata = $metadata;
        $this->language = $language;
        $this->fileManager = $fileManager;

        $this->metadataUtils = new \Espo\Core\Utils\Metadata\Utils($this->metadata);
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadataUtils()
    {
        return $this->metadataUtils;
    }

    public function read($name, $scope)
    {
        $fieldDef = $this->getFieldDef($name, $scope);

        $fieldDef['label'] = $this->getLanguage()->translate($name, 'fields', $scope);

        return $fieldDef;
    }

    public function create($name, $type)
    {
        if ($this->getMetadata()->get('scopes.' . $name)) {
            throw new Conflict('Entity ['.$name.'] already exists.');
        }
        if (empty($name) || empty($type)) {
            throw new Error();
        }

        $contents = "<" . "?" . "php\n".
            "namespace Espo\Custom\Entities;\n".
            "class {$name} extends \Espo\Core\Templates\Entities\\{$type}\n".
            "{\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Entities/{$name}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n".
            "namespace Espo\Custom\Controllers;\n".
            "class {$name} extends \Espo\Core\Templates\Controllers\\{$type}\n".
            "{\n".
            "}\n";
        $filePath = "custom/Espo/Custom/Controllers/{$name}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n".
            "namespace Espo\Custom\Services;\n".
            "class {$name} extends \Espo\Core\Templates\Services\\{$type}\n".
            "{\n".
            "}\n";
        $filePath = "custom/Espo/Custom/Services/{$name}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n".
            "namespace Espo\Custom\Repositories;\n".
            "class {$name} extends \Espo\Core\Templates\Repositories\\{$type}\n".
            "{\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Repositories/{$name}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $scopeData = array(
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Custom',
            'isCustom' => true,
            'customizable' => true,
            'importable' => true,
            'type' => $type
        );
        $this->getMetadata()->set($scopeData, 'scopes', $name);

        $filePath = "application/Espo/Core/Templates/Metadata/{$type}/entityDefs.json";
        $entityDefsData = Json::decode($this->getFileManager()->getContents($filePath), true);
        $this->getMetadata()->set($entityDefsData, 'entityDefs', $name);

        $filePath = "application/Espo/Core/Templates/Metadata/{$type}/clientDefs.json";
        $clientDefsData = Json::decode($this->getFileManager()->getContents($filePath), true);
        $this->getMetadata()->set($clientDefsData, 'clientDefs', $name);

        return true;
    }

    public function update($name, $fieldDef, $scope)
    {
        /*Add option to metadata to identify the custom field*/
        if ($this->isCustom($name, $scope)) {
            $fieldDef['isCustom'] = true;
        }

        $res = true;
        if (isset($fieldDef['label'])) {
            $this->setLabel($name, $fieldDef['label'], $scope);
        }

        if (isset($fieldDef['type']) && $fieldDef['type'] == 'enum') {
            if (isset($fieldDef['translatedOptions'])) {
                $this->setTranslatedOptions($name, $fieldDef['translatedOptions'], $scope);
            }
        }

        if (isset($fieldDef['label']) || isset($fieldDef['translatedOptions'])) {
            $res &= $this->getLanguage()->save();
        }

        if ($this->isDefsChanged($name, $fieldDef, $scope)) {
            $res &= $this->setEntityDefs($name, $fieldDef, $scope);
        }

        return (bool) $res;
    }

    public function delete($name)
    {
        if (!$this->isCustom($name)) {
            throw new Forbidden;
        }

        $unsets = array(
            'entityDefs',
            'clientDefs',
            'scopes'
        );
        $res = $this->getMetadata()->delete($unsets, $this->metadataType, $name);

        $this->getFileManager()->removeFile("custom/Espo/Custom/Resources/metadata/entityDefs/{$name}.json");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Resources/metadata/clientDefs/{$name}.json");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Resources/metadata/scopes/{$name}.json");

        $this->getFileManager()->removeFile("custom/Espo/Custom/Entities/{$name}.php");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Services/{$name}.php");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Controllers/{$name}.php");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Repositories/{$name}.php");

        return true;
    }

    protected function setEntityDefs($name, $fieldDef, $scope)
    {
        $fieldDef = $this->normalizeDefs($name, $fieldDef, $scope);

        $data = Json::encode($fieldDef);
        $res = $this->getMetadata()->set($data, $this->metadataType, $scope);

        return $res;
    }

    protected function setTranslatedOptions($name, $value, $scope)
    {
        return $this->getLanguage()->set($name, $value, 'options', $scope);
    }

    protected function setLabel($name, $value, $scope)
    {
        return $this->getLanguage()->set($name, $value, 'fields', $scope);
    }

    protected function deleteLabel($name, $scope)
    {
        $this->getLanguage()->delete($name, 'fields', $scope);
        return $this->getLanguage()->save();
    }

    protected function getFieldDef($name, $scope)
    {
        return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.fields.'.$name);
    }

    protected function getLinkDef($name, $scope)
    {
        return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.links.'.$name);
    }

    /**
     * Prepare input fieldDefs, remove unnecessary fields
     *
     * @param string $fieldName
     * @param array $fieldDef
     * @param string $scope
     * @return array
     */
    protected function prepareFieldDef($name, $fieldDef, $scope)
    {
        $unnecessaryFields = array(
            'name',
            'label',
        );

        foreach ($unnecessaryFields as $fieldName) {
            if (isset($fieldDef[$fieldName])) {
                unset($fieldDef[$fieldName]);
            }
        }

        if (isset($fieldDef['linkDefs'])) {
            $linkDefs = $fieldDef['linkDefs'];
            unset($fieldDef['linkDefs']);
        }

        $currentOptionList = array_keys((array) $this->getFieldDef($name, $scope));
        foreach ($fieldDef as $defName => $defValue) {
            if ( (!isset($defValue) || $defValue === '') && !in_array($defName, $currentOptionList) ) {
                unset($fieldDef[$defName]);
            }
        }

        return $fieldDef;
    }

    /**
     * Add all needed block for a field defenition
     *
     * @param string $fieldName
     * @param array $fieldDef
     * @param string $scope
     * @return array
     */
    protected function normalizeDefs($fieldName, array $fieldDef, $scope)
    {
        $fieldDef = $this->prepareFieldDef($fieldName, $fieldDef, $scope);

        $metaFieldDef = $this->getMetadataUtils()->getFieldDefsInFieldMeta($fieldDef);
        if (isset($metaFieldDef)) {
            $fieldDef = Util::merge($metaFieldDef, $fieldDef);
        }

        $defs = array(
            'fields' => array(
                $fieldName => $fieldDef,
            ),
        );

        /** Save links for a field. */
        $metaLinkDef = $this->getMetadataUtils()->getLinkDefsInFieldMeta($scope, $fieldDef);
        if (isset($linkDefs) || isset($metaLinkDef)) {
            $linkDefs = Util::merge((array) $metaLinkDef, (array) $linkDefs);
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
    protected function isDefsChanged($name, $fieldDef, $scope)
    {
        $fieldDef = $this->prepareFieldDef($name, $fieldDef, $scope);
        $currentFieldDef = $this->getFieldDef($name, $scope);

        $this->isChanged = Util::isEquals($fieldDef, $currentFieldDef) ? false : true;

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

    protected function isCustom($name)
    {
        return $this->getMetadata()->get('scopes.' . $name . '.isCustom');
    }
}
