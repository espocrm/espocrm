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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Conflict;
use \Espo\Core\Utils\Json;
use \Espo\Core\Container;

class EntityManager
{
    private $metadata;

    private $language;

    private $fileManager;

    private $config;

    private $metadataHelper;

    private $container;

    private $reservedWordList = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'common'];

    private $linkForbiddenNameList = ['posts', 'stream', 'subscription', 'followers', 'action', 'null', 'false', 'true'];

    private $forbiddenEntityTypeNameList = ['Common', 'PortalUser', 'ApiUser', 'Timeline', 'About', 'Admin', 'Null', 'False', 'True'];

    public function __construct(Metadata $metadata, Language $language, File\Manager $fileManager, Config $config, Container $container = null)
    {
        $this->metadata = $metadata;
        $this->language = $language;
        $this->fileManager = $fileManager;
        $this->config = $config;

        $this->metadataHelper = new \Espo\Core\Utils\Metadata\Helper($this->metadata);

        $this->container = $container;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getEntityManager()
    {
        if (!$this->container) return;

        return $this->container->get('entityManager');
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getBaseLanguage()
    {
        return $this->container->get('baseLanguage');
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadataHelper()
    {
        return $this->metadataHelper;
    }

    protected function getServiceFactory()
    {
        if (!$this->container) return;

        return $this->container->get('serviceFactory');
    }

    protected function checkControllerExists($name)
    {
        $controllerClassName = '\\Espo\\Custom\\Controllers\\' . Util::normilizeClassName($name);
        if (class_exists($controllerClassName)) {
            return true;
        } else {
            foreach ($this->getMetadata()->getModuleList() as $moduleName) {
                $controllerClassName = '\\Espo\\Modules\\' . $moduleName . '\\Controllers\\' . Util::normilizeClassName($name);
                if (class_exists($controllerClassName)) {
                    return true;
                }
            }
            $controllerClassName = '\\Espo\\Controllers\\' . Util::normilizeClassName($name);
            if (class_exists($controllerClassName)) {
                return true;
            }
        }
        return false;
    }

    protected function checkRelationshipExists($name)
    {
        $name = ucfirst($name);

        $scopeList = array_keys($this->getMetadata()->get(['scopes'], []));

        foreach ($scopeList as $entityType) {
            $relationsDefs = $this->getEntityManager()->getMetadata()->get($entityType, 'relations');
            if (empty($relationsDefs)) continue;
            foreach ($relationsDefs as $link => $item) {
                if (empty($item['type'])) continue;
                if (empty($item['relationName'])) continue;
                if ($item['type'] === 'manyMany') {
                    if (ucfirst($item['relationName']) === $name) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function create($name, $type, $params = [], $replaceData = [])
    {
        $name = ucfirst($name);
        $name = trim($name);

        if (empty($name) || empty($type)) {
            throw new BadRequest();
        }

        if (strlen($name) > 100) {
            throw new Error('Entity name should not be longer than 100.');
        }

        if (is_numeric($name[0])) {
            throw new Error('Bad entity name.');
        }

        if (!in_array($type, $this->getMetadata()->get(['app', 'entityTemplateList'], []))) {
            throw new Error('Type \''.$type.'\' does not exist.');
        }

        $templateDefs = $this->getMetadata()->get(['app', 'entityTemplates', $type], []);

        if (!empty($templateDefs['isNotCreatable']) && empty($params['forceCreate'])) {
            throw new Error('Type \''.$type.'\' is not creatable.');
        }

        if ($this->getMetadata()->get('scopes.' . $name)) {
            throw new Conflict('Entity \''.$name.'\' already exists.');
        }

        if ($this->getMetadata()->get(['clientDefs.', $name])) {
            throw new Conflict('Entity \''.$name.'\' already exists.');
        }

        if ($this->checkControllerExists($name)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        $serviceFactory = $this->getServiceFactory();
        if ($serviceFactory && $serviceFactory->checKExists($name)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        if (in_array($name, $this->forbiddenEntityTypeNameList)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        if (in_array(strtolower($name), $this->reservedWordList)) {
            throw new Conflict('Entity name \''.$name.'\' is not allowed.');
        }

        if ($this->checkRelationshipExists($name)) {
            throw new Conflict('Relationship with the same name \''.$name.'\' exists.');
        }

        $normalizedName = Util::normilizeClassName($name);

        $templateNamespace = "\Espo\Core\Templates";
        $templatePath = "application/Espo/Core/Templates";

        $templateModuleName = null;
        if (!empty($templateDefs['module'])) {
            $templateModuleName = $templateDefs['module'];
            $normalizedTemplateModuleName = Util::normilizeClassName($templateModuleName);
            $templateNamespace = "\Espo\Modules\\{$normalizedTemplateModuleName}\Core\Templates";
            $templatePath = "application/Espo/Modules/".$normalizedTemplateModuleName."/Core/Templates";
        }

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Entities;\n\n".
            "class {$normalizedName} extends {$templateNamespace}\Entities\\{$type}\n".
            "{\n".
            "    protected \$entityType = \"$name\";\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Entities/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Controllers;\n\n".
            "class {$normalizedName} extends {$templateNamespace}\Controllers\\{$type}\n".
            "{\n".
            "}\n";
        $filePath = "custom/Espo/Custom/Controllers/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Services;\n\n".
            "class {$normalizedName} extends {$templateNamespace}\Services\\{$type}\n".
            "{\n".
            "}\n";
        $filePath = "custom/Espo/Custom/Services/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Repositories;\n\n".
            "class {$normalizedName} extends {$templateNamespace}\Repositories\\{$type}\n".
            "{\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Repositories/{$normalizedName}.php";
        $this->getFileManager()->putContents($filePath, $contents);

        if (file_exists($templatePath . '/SelectManagers/' . $type . '.php')) {
            $contents = "<" . "?" . "php\n\n".
                "namespace Espo\Custom\SelectManagers;\n\n".
                "class {$normalizedName} extends {$templateNamespace}\SelectManagers\\{$type}\n".
                "{\n".
                "}\n";

            $filePath = "custom/Espo/Custom/SelectManagers/{$normalizedName}.php";
            $this->getFileManager()->putContents($filePath, $contents);
        }

        $stream = false;
        if (!empty($params['stream'])) {
            $stream = $params['stream'];
        }
        $disabled = false;
        if (!empty($params['disabled'])) {
            $disabled = $params['disabled'];
        }
        $labelSingular = $name;
        if (!empty($params['labelSingular'])) {
            $labelSingular = $params['labelSingular'];
        }
        $labelPlural = $name;
        if (!empty($params['labelPlural'])) {
            $labelPlural = $params['labelPlural'];
        }

        $languageList = $this->getConfig()->get('languageList', []);
        foreach ($languageList as $language) {
            $filePath = $templatePath . '/i18n/' . $language . '/' . $type . '.json';
            if (!file_exists($filePath)) continue;
            $languageContents = $this->getFileManager()->getContents($filePath);
            $languageContents = str_replace('{entityType}', $name, $languageContents);
            $languageContents = str_replace('{entityTypeTranslated}', $labelSingular, $languageContents);
            foreach ($replaceData as $key => $value) {
                $languageContents = str_replace('{'.$key.'}', $value, $languageContents);
            }

            $destinationFilePath = 'custom/Espo/Custom/Resources/i18n/' . $language . '/' . $name . '.json';
            $this->getFileManager()->putContents($destinationFilePath, $languageContents);
        }

        $filePath = $templatePath . "/Metadata/{$type}/scopes.json";
        $scopesDataContents = $this->getFileManager()->getContents($filePath);
        $scopesDataContents = str_replace('{entityType}', $name, $scopesDataContents);
        foreach ($replaceData as $key => $value) {
            $scopesDataContents = str_replace('{'.$key.'}', $value, $scopesDataContents);
        }

        $scopesData = Json::decode($scopesDataContents, true);

        $scopesData['stream'] = $stream;
        $scopesData['disabled'] = $disabled;
        $scopesData['type'] = $type;
        $scopesData['module'] = 'Custom';
        $scopesData['object'] = true;
        $scopesData['isCustom'] = true;

        if (!empty($templateDefs['isNotRemovable']) ||!empty($params['isNotRemovable'])) {
            $scopesData['isNotRemovable'] = true;
        }

        if (!empty($params['kanbanStatusIgnoreList'])) {
            $scopesData['kanbanStatusIgnoreList'] = $params['kanbanStatusIgnoreList'];
        }

        $this->getMetadata()->set('scopes', $name, $scopesData);

        $filePath = $templatePath . "/Metadata/{$type}/entityDefs.json";
        $entityDefsDataContents = $this->getFileManager()->getContents($filePath);
        $entityDefsDataContents = str_replace('{entityType}', $name, $entityDefsDataContents);
        $entityDefsDataContents = str_replace('{tableName}', $this->getEntityManager()->getQuery()->toDb($name), $entityDefsDataContents);
        foreach ($replaceData as $key => $value) {
            $entityDefsDataContents = str_replace('{'.$key.'}', $value, $entityDefsDataContents);
        }
        $entityDefsData = Json::decode($entityDefsDataContents, true);
        $this->getMetadata()->set('entityDefs', $name, $entityDefsData);

        $filePath = $templatePath . "/Metadata/{$type}/clientDefs.json";
        $clientDefsContents = $this->getFileManager()->getContents($filePath);
        $clientDefsContents = str_replace('{entityType}', $name, $clientDefsContents);
        foreach ($replaceData as $key => $value) {
            $clientDefsContents = str_replace('{'.$key.'}', $value, $clientDefsContents);
        }
        $clientDefsData = Json::decode($clientDefsContents, true);

        if (array_key_exists('color', $params)) {
            $clientDefsData['color'] = $params['color'];
        }

        if (array_key_exists('iconClass', $params)) {
            $clientDefsData['iconClass'] = $params['iconClass'];
        }

        if (!empty($params['kanbanViewMode'])) {
            $clientDefsData['kanbanViewMode'] = true;
        }
        $this->getMetadata()->set('clientDefs', $name, $clientDefsData);

        $this->getBaseLanguage()->set('Global', 'scopeNames', $name, $labelSingular);
        $this->getBaseLanguage()->set('Global', 'scopeNamesPlural', $name, $labelPlural);

        $this->getMetadata()->save();
        $this->getBaseLanguage()->save();

        $layoutsPath = $templatePath . "/Layouts/{$type}";
        if ($this->getFileManager()->isDir($layoutsPath)) {
            $this->getFileManager()->copy($layoutsPath, 'custom/Espo/Custom/Resources/layouts/' . $name);
        }

        $this->processHook('afterCreate', $type, $name, $params);

        return true;
    }

    public function update($name, $data)
    {
        if (!$this->getMetadata()->get('scopes.' . $name)) {
            throw new Error('Entity ['.$name.'] does not exist.');
        }

        if (isset($data['stream']) || isset($data['disabled'])) {
            $scopeData = array();
            if (isset($data['stream'])) {
                $scopeData['stream'] = true == $data['stream'];
            }
            if (isset($data['disabled'])) {
                $scopeData['disabled'] = true == $data['disabled'];
            }
            $this->getMetadata()->set('scopes', $name, $scopeData);
        }

        $isCustom = false;
        if (!empty($scopeData['isCustom'])) {
            $isCustom = true;
        }

        if (array_key_exists('statusField', $data)) {
            $scopeData['statusField'] = $data['statusField'];
            $this->getMetadata()->set('scopes', $name, $scopeData);
        }

        if (!empty($data['labelSingular'])) {
            $labelSingular = $data['labelSingular'];
            $labelCreate = $this->getLanguage()->translate('Create') . ' ' . $labelSingular;

            $this->getLanguage()->set('Global', 'scopeNames', $name, $labelSingular);
            $this->getLanguage()->set($name, 'labels', 'Create ' . $name, $labelCreate);

            if ($isCustom) {
                $this->getBaseLanguage()->set('Global', 'scopeNames', $name, $labelSingular);
                $this->getBaseLanguage()->set($name, 'labels', 'Create ' . $name, $labelCreate);
            }
        }

        if (!empty($data['labelPlural'])) {
            $labelPlural = $data['labelPlural'];
            $this->getLanguage()->set('Global', 'scopeNamesPlural', $name, $labelPlural);
            if ($isCustom) {
                $this->getBaseLanguage()->set('Global', 'scopeNamesPlural', $name, $labelPlural);
            }
        }

        if (isset($data['sortBy'])) {
            $entityDefsData = [
                'collection' => [
                    'orderBy' => $data['sortBy']
                ]
            ];
            if (isset($data['sortDirection'])) {
                $entityDefsData['collection']['order'] = $data['sortDirection'];
            }

            $this->getMetadata()->set('entityDefs', $name, $entityDefsData);
        }

        if (isset($data['textFilterFields'])) {
            $entityDefsData = array(
                'collection' => array(
                    'textFilterFields' => $data['textFilterFields']
                )
            );
            $this->getMetadata()->set('entityDefs', $name, $entityDefsData);
        }


        if (isset($data['fullTextSearch'])) {
            $entityDefsData = [
                'collection' => [
                    'fullTextSearch' => !!$data['fullTextSearch']
                ]
            ];
            $this->getMetadata()->set('entityDefs', $name, $entityDefsData);
        }

        if (array_key_exists('kanbanStatusIgnoreList', $data)) {
            $scopeData['kanbanStatusIgnoreList'] = $data['kanbanStatusIgnoreList'];
            $this->getMetadata()->set('scopes', $name, $scopeData);
        }

        if (array_key_exists('kanbanViewMode', $data)) {
            $clientDefsData = [
                'kanbanViewMode' => $data['kanbanViewMode']
            ];
            $this->getMetadata()->set('clientDefs', $name, $clientDefsData);
        }

        if (array_key_exists('color', $data)) {
            $clientDefsData = [
                'color' => $data['color']
            ];
            $this->getMetadata()->set('clientDefs', $name, $clientDefsData);
        }

        if (array_key_exists('iconClass', $data)) {
            $clientDefsData = [
                'iconClass' => $data['iconClass']
            ];
            $this->getMetadata()->set('clientDefs', $name, $clientDefsData);
        }

        $this->getMetadata()->save();
        $this->getLanguage()->save();
        if ($isCustom) {
            if ($this->getLanguage()->getLanguage() !== $this->getBaseLanguage()->getLanguage()) {
                $this->getBaseLanguage()->save();
            }
        }

        return true;
    }

    public function delete($name, $params = [])
    {
        if (!$this->isCustom($name)) {
            throw new Forbidden;
        }

        $normalizedName = Util::normilizeClassName($name);

        $type = $this->getMetadata()->get(['scopes', $name, 'type']);

        $isNotRemovable = $this->getMetadata()->get(['scopes', $name, 'isNotRemovable']);

        $templateDefs = $this->getMetadata()->get(['app', 'entityTemplates', $type], []);

        $templateModuleName = null;
        if (!empty($templateDefs['module'])) {
            $templateModuleName = $templateDefs['module'];
        }

        if ((!empty($templateDefs['isNotRemovable']) || $isNotRemovable) && empty($params['forceRemove'])) {
            throw new Error('Type \''.$type.'\' is not removable.');
        }

        $unsets = array(
            'entityDefs',
            'clientDefs',
            'scopes'
        );
        $res = $this->getMetadata()->delete('entityDefs', $name);
        $res = $this->getMetadata()->delete('clientDefs', $name);
        $res = $this->getMetadata()->delete('scopes', $name);

        foreach ($this->getMetadata()->get(['entityDefs', $name, 'links'], []) as $link => $item) {
            try {
                $this->deleteLink(['entity' => $name, 'link' => $link]);
            } catch (\Exception $e) {}
        }

        $this->getFileManager()->removeFile("custom/Espo/Custom/Resources/metadata/entityDefs/{$name}.json");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Resources/metadata/clientDefs/{$name}.json");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Resources/metadata/scopes/{$name}.json");

        $this->getFileManager()->removeFile("custom/Espo/Custom/Entities/{$normalizedName}.php");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Services/{$normalizedName}.php");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Controllers/{$normalizedName}.php");
        $this->getFileManager()->removeFile("custom/Espo/Custom/Repositories/{$normalizedName}.php");

        if (file_exists("custom/Espo/Custom/SelectManagers/{$normalizedName}.php")) {
            $this->getFileManager()->removeFile("custom/Espo/Custom/SelectManagers/{$normalizedName}.php");
        }

        $this->getFileManager()->removeInDir("custom/Espo/Custom/Resources/layouts/{$normalizedName}");
        $this->getFileManager()->removeDir("custom/Espo/Custom/Resources/layouts/{$normalizedName}");

        $languageList = $this->getConfig()->get('languageList', []);
        foreach ($languageList as $language) {
            $filePath = 'custom/Espo/Custom/Resources/i18n/' . $language . '/' . $normalizedName . '.json' ;
            if (!file_exists($filePath)) continue;
            $this->getFileManager()->removeFile($filePath);
        }

        try {
            $this->getLanguage()->delete('Global', 'scopeNames', $name);
            $this->getLanguage()->delete('Global', 'scopeNamesPlural', $name);

            $this->getBaseLanguage()->delete('Global', 'scopeNames', $name);
            $this->getBaseLanguage()->delete('Global', 'scopeNamesPlural', $name);
        } catch (\Exception $e) {}

        $this->getMetadata()->save();
        $this->getLanguage()->save();

        if ($this->getLanguage()->getLanguage() !== $this->getBaseLanguage()->getLanguage()) {
            $this->getBaseLanguage()->save();
        }

        if ($type) {
            $this->processHook('afterRemove', $type, $name);
        }

        return true;
    }

    protected function isCustom($name)
    {
        return $this->getMetadata()->get('scopes.' . $name . '.isCustom');
    }

    public function createLink(array $params)
    {
        $linkType = $params['linkType'];

        $entity = $params['entity'];
        $link = trim($params['link']);
        $entityForeign = $params['entityForeign'];
        $linkForeign = trim($params['linkForeign']);

        $label = $params['label'];
        $labelForeign = $params['labelForeign'];

        if ($linkType === 'manyToMany') {
            if (!empty($params['relationName'])) {
                $relationName = $params['relationName'];
            } else {
                $relationName = lcfirst($entity) . $entityForeign;
            }
            if (strlen($relationName) > 100) {
                throw new Error('Relation name should not be longer than 100.');
            }
            if ($this->getMetadata()->get(['scopes', ucfirst($relationName)])) {
                throw new Conflict("Entity with the same name '{$relationName}' exists.");
            }
            if ($this->checkRelationshipExists($relationName)) {
                throw new Conflict("Relationship with the same name '{$relationName}' exists.");
            }
        }

        if (empty($link) || empty($linkForeign)) {
            throw new BadRequest();
        }

        if (strlen($link) > 100 || strlen($linkForeign) > 100) {
            throw new Error('Link name should not be longer than 100.');
        }

        if (is_numeric($link[0]) || is_numeric($linkForeign[0])) {
            throw new Error('Bad link name.');
        }

        if (in_array($link, $this->linkForbiddenNameList)) {
            throw new Conflict("Link name '{$link}' is not allowed.");
        }

        if (in_array($linkForeign, $this->linkForbiddenNameList)) {
            throw new Conflict("Link name '{$linkForeign}' is not allowed.");
        }

        $linkMultipleField = false;
        if (!empty($params['linkMultipleField'])) {
            $linkMultipleField = true;
        }

        $linkMultipleFieldForeign = false;
        if (!empty($params['linkMultipleFieldForeign'])) {
            $linkMultipleFieldForeign = true;
        }


        $audited = false;
        if (!empty($params['audited'])) {
            $audited = true;
        }

        $auditedForeign = false;
        if (!empty($params['auditedForeign'])) {
            $auditedForeign = true;
        }

        if (empty($linkType)) {
            throw new Error();
        }
        if (empty($entity) || empty($entityForeign)) {
            throw new Error();
        }
        if (empty($entityForeign) || empty($linkForeign)) {
            throw new Error();
        }

        if ($this->getMetadata()->get('entityDefs.' . $entity . '.links.' . $link)) {
            throw new Conflict('Link ['.$entity.'::'.$link.'] already exists.');
        }
        if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.links.' . $linkForeign)) {
            throw new Conflict('Link ['.$entityForeign.'::'.$linkForeign.'] already exists.');
        }

        if ($entity === $entityForeign) {
            if ($link === ucfirst($entity) || $linkForeign === ucfirst($entity)) {
                throw new Conflict();
            }
        }

        switch ($linkType) {
            case 'oneToMany':
                if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign)) {
                    throw new Conflict('Field ['.$entityForeign.'::'.$linkForeign.'] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Id')) {
                    throw new Conflict('Field ['.$entityForeign.'::'.$linkForeign.'Id] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Name')) {
                    throw new Conflict('Field ['.$entityForeign.'::'.$linkForeign.'Name] already exists.');
                }
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleField,
                            "layoutMassUpdateDisabled"  => !$linkMultipleField,
                            "noLoad"  => !$linkMultipleField,
                            "importDisabled" => !$linkMultipleField,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $link => array(
                            'type' => 'hasMany',
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true
                        )
                    )
                );
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            'type' => 'link'
                        )
                    ),
                    'links' => array(
                        $linkForeign => array(
                            'type' => 'belongsTo',
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true
                        )
                    )
                );
                break;
            case 'manyToOne':
                if ($this->getMetadata()->get('entityDefs.' . $entity . '.fields.' . $link)) {
                    throw new Conflict('Field ['.$entity.'::'.$link.'] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entity . '.fields.' . $link . 'Id')) {
                    throw new Conflict('Field ['.$entity.'::'.$link.'Id] already exists.');
                }
                if ($this->getMetadata()->get('entityDefs.' . $entity . '.fields.' . $link . 'Name')) {
                    throw new Conflict('Field ['.$entity.'::'.$link.'Name] already exists.');
                }
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            'type' => 'link'
                        )
                    ),
                    'links' => array(
                        $link => array(
                            'type' => 'belongsTo',
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true
                        )
                    )
                );
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleFieldForeign,
                            "layoutMassUpdateDisabled"  => !$linkMultipleFieldForeign,
                            "noLoad"  => !$linkMultipleFieldForeign,
                            "importDisabled" => !$linkMultipleFieldForeign,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $linkForeign => array(
                            'type' => 'hasMany',
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true
                        )
                    )
                );
                break;
            case 'manyToMany':
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleField,
                            "layoutMassUpdateDisabled"  => !$linkMultipleField,
                            "importDisabled" => !$linkMultipleField,
                            "noLoad"  => !$linkMultipleField,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $link => array(
                            'type' => 'hasMany',
                            'relationName' => $relationName,
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true
                        )
                    )
                );
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleFieldForeign,
                            "layoutMassUpdateDisabled"  => !$linkMultipleFieldForeign,
                            "importDisabled" => !$linkMultipleFieldForeign,
                            "noLoad"  => !$linkMultipleFieldForeign,
                            'isCustom' => true
                        )
                    ),
                    'links' => array(
                        $linkForeign => array(
                            'type' => 'hasMany',
                            'relationName' => $relationName,
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true
                        )
                    )
                );
                if ($entityForeign == $entity) {
                    $dataLeft['links'][$link]['midKeys'] = ['leftId', 'rightId'];
                    $dataRight['links'][$linkForeign]['midKeys'] = ['rightId', 'leftId'];
                }
                break;
        }

        $this->getMetadata()->set('entityDefs', $entity, $dataLeft);
        $this->getMetadata()->set('entityDefs', $entityForeign, $dataRight);
        $this->getMetadata()->save();

        $this->getLanguage()->set($entity, 'fields', $link, $label);
        $this->getLanguage()->set($entity, 'links', $link, $label);
        $this->getLanguage()->set($entityForeign, 'fields', $linkForeign, $labelForeign);
        $this->getLanguage()->set($entityForeign, 'links', $linkForeign, $labelForeign);
        $this->getLanguage()->save();

        if ($this->getLanguage()->getLanguage() !== $this->getBaseLanguage()->getLanguage()) {
            $this->getBaseLanguage()->set($entity, 'fields', $link, $label);
            $this->getBaseLanguage()->set($entity, 'links', $link, $label);
            $this->getBaseLanguage()->set($entityForeign, 'fields', $linkForeign, $labelForeign);
            $this->getBaseLanguage()->set($entityForeign, 'links', $linkForeign, $labelForeign);
            $this->getBaseLanguage()->save();
        }

        return true;
    }

    public function updateLink(array $params)
    {
        $entity = $params['entity'];
        $link = $params['link'];
        $entityForeign = $params['entityForeign'];
        $linkForeign = $params['linkForeign'];

        if (empty($entity) || empty($entityForeign)) {
            throw new Error();
        }
        if (empty($entityForeign) || empty($linkForeign)) {
            throw new Error();
        }

        $isCustom = $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.isCustom");

        if (
            $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.type") == 'hasMany'
            &&
            $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.isCustom")
        ) {
            if (array_key_exists('linkMultipleField', $params)) {
                $linkMultipleField = $params['linkMultipleField'];
                $dataLeft = array(
                    'fields' => array(
                        $link => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleField,
                            "layoutMassUpdateDisabled"  => !$linkMultipleField,
                            "noLoad"  => !$linkMultipleField,
                            "importDisabled" => !$linkMultipleField,
                            'isCustom' => true
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entity, $dataLeft);
                $this->getMetadata()->save();
            }
        }

        if (
            $this->getMetadata()->get("entityDefs.{$entityForeign}.links.{$linkForeign}.type") == 'hasMany'
            &&
            $this->getMetadata()->get("entityDefs.{$entityForeign}.links.{$linkForeign}.isCustom")
        ) {
            if (array_key_exists('linkMultipleFieldForeign', $params)) {
                $linkMultipleFieldForeign = $params['linkMultipleFieldForeign'];
                $dataRight = array(
                    'fields' => array(
                        $linkForeign => array(
                            "type" => "linkMultiple",
                            "layoutDetailDisabled"  => !$linkMultipleFieldForeign,
                            "layoutMassUpdateDisabled"  => !$linkMultipleFieldForeign,
                            "noLoad"  => !$linkMultipleFieldForeign,
                            "importDisabled" => !$linkMultipleFieldForeign,
                            'isCustom' => true
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityForeign, $dataRight);
                $this->getMetadata()->save();
            }
        }

        if (
            in_array($this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.type"), ['hasMany', 'hasChildren'])
        ) {
            if (array_key_exists('audited', $params)) {
                $audited = $params['audited'];
                $dataLeft = array(
                    'links' => array(
                        $link => array(
                            "audited" => $audited
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entity, $dataLeft);
                $this->getMetadata()->save();
            }
        }

        if (
            in_array($this->getMetadata()->get("entityDefs.{$entityForeign}.links.{$linkForeign}.type"), ['hasMany', 'hasChildren'])
        ) {
            if (array_key_exists('auditedForeign', $params)) {
                $auditedForeign = $params['auditedForeign'];
                $dataRight = array(
                    'links' => array(
                        $linkForeign => array(
                            "audited" => $auditedForeign
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityForeign, $dataRight);
                $this->getMetadata()->save();
            }
        }

        $label = null;
        if (isset($params['label'])) {
            $label = $params['label'];
        }
        $labelForeign = null;
        if (isset($params['labelForeign'])) {
            $labelForeign = $params['labelForeign'];
        }

        if ($label) {
            $this->getLanguage()->set($entity, 'fields', $link, $label);
            $this->getLanguage()->set($entity, 'links', $link, $label);
        }

        if ($labelForeign) {
            $this->getLanguage()->set($entityForeign, 'fields', $linkForeign, $labelForeign);
            $this->getLanguage()->set($entityForeign, 'links', $linkForeign, $labelForeign);
        }

        $this->getLanguage()->save();

        if ($isCustom) {
            if ($this->getBaseLanguage()->getLanguage() !== $this->getBaseLanguage()->getLanguage()) {
                if ($label) {
                    $this->getBaseLanguage()->set($entity, 'fields', $link, $label);
                    $this->getBaseLanguage()->set($entity, 'links', $link, $label);
                }
                if ($labelForeign) {
                    $this->getBaseLanguage()->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                    $this->getBaseLanguage()->set($entityForeign, 'links', $linkForeign, $labelForeign);
                }
                $this->getBaseLanguage()->save();
            }
        }

        return true;
    }

    public function deleteLink(array $params)
    {
        $entity = $params['entity'];
        $link = $params['link'];

        if (!$this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.isCustom")) {
            throw new Error("Could not delete link {$entity}.{$link}. Not isCustom.");
        }

        $entityForeign = $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.entity");
        $linkForeign = $this->getMetadata()->get("entityDefs.{$entity}.links.{$link}.foreign");

        if (empty($entity) || empty($entityForeign)) {
            throw new Error();
        }
        if (empty($entityForeign) || empty($linkForeign)) {
            throw new Error();
        }

        $this->getMetadata()->delete('entityDefs', $entity, array(
            'fields.' . $link,
            'links.' . $link
        ));
        $this->getMetadata()->delete('entityDefs', $entityForeign, array(
            'fields.' . $linkForeign,
            'links.' . $linkForeign
        ));
        $this->getMetadata()->save();

        return true;
    }

    public function setFormulaData($scope, $data)
    {
        $this->getMetadata()->set('formula', $scope, $data);
        $this->getMetadata()->save();
    }

    protected function processHook($methodName, $type, $name, &$params = null)
    {
        $hook = $this->getHook($type);
        if (!$hook) return;

        if (!method_exists($hook, $methodName)) return;

        $hook->$methodName($name, $params);
    }

    protected function getHook($type)
    {
        $templateDefs = $this->getMetadata()->get(['app', 'entityTemplates', $type], []);

        $className = '\\Espo\\Core\\Utils\\EntityManager\\Hooks\\' . $type . 'Type';

        $templateModuleName = null;
        if (!empty($templateDefs['module'])) {
            $templateModuleName = $templateDefs['module'];
            $normalizedTemplateModuleName = Util::normilizeClassName($templateModuleName);
            $className = '\\Espo\\Modules\\'.$normalizedTemplateModuleName.'\\Core\\Utils\\EntityManager\\Hooks\\' . $type . 'Type';
        }



        $className = $this->getMetadata()->get(['app', 'entityTemplates', $type, 'hookClassName'], $className);

        if (class_exists($className)) {
            $hook = new $className();
            foreach ($hook->getDependencyList() as $name) {
                $hook->inject($name, $this->container->get($name));
            }
            return $hook;
        }
        return;
    }

    public function resetToDefaults($scope)
    {
        if ($this->isCustom($scope)) {
            throw new Error("Can't reset to defaults custom entity type '{$scope}.'");
        }

        $this->getMetadata()->delete('scopes', $scope, [
            'disabled',
            'stream',
            'statusField',
            'kanbanStatusIgnoreList'
        ]);
        $this->getMetadata()->delete('clientDefs', $scope, [
            'iconClass',
            'statusField',
            'kanbanViewMode'
        ]);
        $this->getMetadata()->delete('entityDefs', $scope, [
            'collection.sortBy',
            'collection.asc',
            'collection.orderBy',
            'collection.order',
            'collection.textFilterFields'
        ]);
        $this->getMetadata()->save();

        $this->getLanguage()->delete('Global', 'scopeNames', $scope);
        $this->getLanguage()->delete('Global', 'scopeNamesPlural', $scope);
        $this->getLanguage()->save();
    }
}
