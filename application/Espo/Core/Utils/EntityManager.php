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

    public function create($name, $type, $params = array())
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

        $stream = false;
        if (!empty($params['stream'])) {
            $stream = $params['stream'];
        }
        $labelSingular = $name;
        if (!empty($params['labelSingular'])) {
            $labelSingular = $params['labelSingular'];
        }
        $labelPlural = $name;
        if (!empty($params['labelPlural'])) {
            $labelPlural = $params['labelPlural'];
        }
        $labelCreate = $this->getLanguage()->translate('Create') . ' ' . $labelSingular;

        $scopeData = array(
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Custom',
            'isCustom' => true,
            'customizable' => true,
            'importable' => true,
            'type' => $type,
            'stream' => $stream
        );
        $this->getMetadata()->set($scopeData, 'scopes', $name);

        $filePath = "application/Espo/Core/Templates/Metadata/{$type}/entityDefs.json";
        $entityDefsData = Json::decode($this->getFileManager()->getContents($filePath), true);
        $this->getMetadata()->set($entityDefsData, 'entityDefs', $name);

        $filePath = "application/Espo/Core/Templates/Metadata/{$type}/clientDefs.json";
        $clientDefsData = Json::decode($this->getFileManager()->getContents($filePath), true);
        $this->getMetadata()->set($clientDefsData, 'clientDefs', $name);

        $this->getLanguage()->set($name, $labelSingular, 'scopeNames', 'Global');
        $this->getLanguage()->set($name, $labelPlural, 'scopeNamesPlural', 'Global');
        $this->getLanguage()->set('Create ' . $name, $labelCreate, 'labels', $name);
        $this->getLanguage()->save();


        return true;
    }

    public function update($name, $fieldDef, $scope)
    {

        return true;
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

        try {
            $this->getLanguage()->delete($name, 'scopeNames', 'Global');
            $this->getLanguage()->delete($name, 'scopeNamesPlural', 'Global');
        } catch (\Exception $e) {}

        $this->getLanguage()->save();

        return true;
    }

    protected function isCustom($name)
    {
        return $this->getMetadata()->get('scopes.' . $name . '.isCustom');
    }
}
