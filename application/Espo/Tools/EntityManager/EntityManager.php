<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\EntityManager;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Conflict;
use Espo\ORM\Entity;
use Espo\Tools\EntityManager\Link\Params as LinkParams;
use Espo\Tools\EntityManager\Link\HookProcessor as LinkHookProcessor;
use Espo\Tools\EntityManager\Link\Type as LinkType;
use Espo\Core\Utils\Route;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;

use Exception;

/**
 * Administration > Entity Manager.
 */
class EntityManager
{
    private const DEFAULT_PARAM_LOCATION = 'scopes';

    public function __construct(
        private Metadata $metadata,
        private Language $language,
        private Language $baseLanguage,
        private FileManager $fileManager,
        private Config $config,
        private ConfigWriter $configWriter,
        private DataManager $dataManager,
        private InjectableFactory $injectableFactory,
        private LinkHookProcessor $linkHookProcessor,
        private NameUtil $nameUtil,
        private Route $routeUtil
    ) {}

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $replaceData
     * @throws BadRequest
     * @throws Error
     * @throws Conflict
     */
    public function create(string $name, string $type, array $params = [], array $replaceData = []): void
    {
        $name = ucfirst($name);
        $name = trim($name);

        if (empty($name) || empty($type)) {
            throw new BadRequest();
        }

        if (!in_array($type, $this->metadata->get(['app', 'entityTemplateList'], []))) {
            throw new Error("Type '$type' does not exist.");
        }

        $templateDefs = $this->metadata->get(['app', 'entityTemplates', $type], []);

        if (!empty($templateDefs['isNotCreatable']) && empty($params['forceCreate'])) {
            throw new Error("Type '$type' is not creatable.");
        }

        if ($this->nameUtil->nameIsBad($name)) {
            throw new Error("Entity name should contain only letters and numbers, " .
                "start with an upper case letter.");
        }

        if ($this->nameUtil->nameIsTooShort($name)) {
            throw new Error("Entity name should not shorter than " . NameUtil::MIN_ENTITY_NAME_LENGTH . ".");
        }

        if ($this->nameUtil->nameIsTooLong($name)) {
            throw new Error("Entity name should not be longer than " . NameUtil::MAX_ENTITY_NAME_LENGTH . ".");
        }

        if ($this->nameUtil->nameIsUsed($name)) {
            throw new Conflict("Name '$name' is already used.");
        }

        if ($this->nameUtil->nameIsNotAllowed($name)) {
            throw new Conflict("Entity name '$name' is not allowed.");
        }

        $normalizedName = Util::normalizeClassName($name);

        $templateNamespace = "\Espo\Core\Templates";

        $templatePath = "application/Espo/Core/Templates";

        if (!empty($templateDefs['module'])) {
            $templateModuleName = $templateDefs['module'];

            $normalizedTemplateModuleName = Util::normalizeClassName($templateModuleName);

            $templateNamespace = "\Espo\Modules\\$normalizedTemplateModuleName\Core\Templates";
            $templatePath = "application/Espo/Modules/$normalizedTemplateModuleName/Core/Templates";
        }

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Entities;\n\n".
            "class $normalizedName extends $templateNamespace\Entities\\$type\n".
            "{\n".
            "    public const ENTITY_TYPE = '$name';\n\n".
            "    protected \$entityType = '$name';\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Entities/$normalizedName.php";

        $this->fileManager->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Controllers;\n\n".
            "class $normalizedName extends $templateNamespace\Controllers\\$type\n".
            "{\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Controllers/$normalizedName.php";

        $this->fileManager->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Services;\n\n".
            "class $normalizedName extends $templateNamespace\Services\\$type\n".
            "{\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Services/$normalizedName.php";

        $this->fileManager->putContents($filePath, $contents);

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\Custom\Repositories;\n\n".
            "class $normalizedName extends $templateNamespace\Repositories\\$type\n".
            "{\n".
            "}\n";

        $filePath = "custom/Espo/Custom/Repositories/$normalizedName.php";

        $this->fileManager->putContents($filePath, $contents);

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

        $languageList = $this->metadata->get(['app', 'language', 'list'], []);

        foreach ($languageList as $language) {
            $filePath = $templatePath . '/i18n/' . $language . '/' . $type . '.json';

            if (!$this->fileManager->exists($filePath)) {
                continue;
            }

            $languageContents = $this->fileManager->getContents($filePath);

            $languageContents = str_replace('{entityType}', $name, $languageContents);
            $languageContents = str_replace('{entityTypeTranslated}', $labelSingular, $languageContents);

            foreach ($replaceData as $key => $value) {
                $languageContents = str_replace('{'.$key.'}', $value, $languageContents);
            }

            $destinationFilePath = 'custom/Espo/Custom/Resources/i18n/' . $language . '/' . $name . '.json';

            $this->fileManager->putContents($destinationFilePath, $languageContents);
        }

        $filePath = $templatePath . "/Metadata/$type/scopes.json";

        $scopesDataContents = $this->fileManager->getContents($filePath);

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

        if (!empty($templateDefs['isNotRemovable']) || !empty($params['isNotRemovable'])) {
            $scopesData['isNotRemovable'] = true;
        }

        if (!empty($params['kanbanStatusIgnoreList'])) {
            $scopesData['kanbanStatusIgnoreList'] = $params['kanbanStatusIgnoreList'];
        }

        $this->metadata->set('scopes', $name, $scopesData);

        $filePath = $templatePath . "/Metadata/$type/entityDefs.json";

        $entityDefsDataContents = $this->fileManager->getContents($filePath);

        $entityDefsDataContents = str_replace('{entityType}', $name, $entityDefsDataContents);
        $entityDefsDataContents = str_replace('{entityTypeLowerFirst}', lcfirst($name), $entityDefsDataContents);

        foreach ($replaceData as $key => $value) {
            $entityDefsDataContents = str_replace('{' . $key . '}', $value, $entityDefsDataContents);
        }

        $entityDefsData = Json::decode($entityDefsDataContents, true);

        $this->metadata->set('entityDefs', $name, $entityDefsData);

        $filePath = $templatePath . "/Metadata/$type/clientDefs.json";

        $clientDefsContents = $this->fileManager->getContents($filePath);

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
        $this->metadata->set('clientDefs', $name, $clientDefsData);

        $this->processMetadataCreateSelectDefs($templatePath, $name, $type);
        $this->processMetadataCreateRecordDefs($templatePath, $name, $type);

        $this->baseLanguage->set('Global', 'scopeNames', $name, $labelSingular);
        $this->baseLanguage->set('Global', 'scopeNamesPlural', $name, $labelPlural);

        $this->metadata->save();
        $this->baseLanguage->save();

        $layoutsPath = $templatePath . "/Layouts/$type";

        if ($this->fileManager->isDir($layoutsPath)) {
            $this->fileManager->copy($layoutsPath, 'custom/Espo/Custom/Resources/layouts/' . $name);
        }

        $this->processHook('afterCreate', $type, $name, $params);

        $tabList = $this->config->get('tabList', []);

        if (!in_array($name, $tabList)) {
            $tabList[] = $name;

            $this->configWriter->set('tabList', $tabList);

            $this->configWriter->save();
        }

        $this->dataManager->rebuild();
    }

    private function processMetadataCreateSelectDefs(string $templatePath, string $name, string $type): void
    {
        $path = $templatePath . "/Metadata/$type/selectDefs.json";

        if (!$this->fileManager->isFile($path)) {
            return;
        }

        $contents = $this->fileManager->getContents($path);

        $data = Json::decode($contents, true);

        $this->metadata->set('selectDefs', $name, $data);
    }

    private function processMetadataCreateRecordDefs(string $templatePath, string $name, string $type): void
    {
        $path = $templatePath . "/Metadata/$type/recordDefs.json";

        if (!$this->fileManager->isFile($path)) {
            return;
        }

        $contents = $this->fileManager->getContents($path);

        $data = Json::decode($contents, true);

        $this->metadata->set('recordDefs', $name, $data);
    }

    /**
     * @param array{
     *   stream?: bool,
     *   disabled?: bool,
     *   statusField?: ?string,
     *   labelSingular?: ?string,
     *   labelPlural?: ?string,
     *   sortBy?: ?string,
     *   sortDirection?: ?string,
     *   textFilterFields?: ?string[],
     *   fullTextSearch?: bool,
     *   countDisabled?: bool,
     *   kanbanStatusIgnoreList?: ?string[],
     *   kanbanViewMode?: bool,
     *   color?: ?string,
     *   iconClass?: ?string,
     * } $data
     * @throws Error
     */
    public function update(string $name, array $data): void
    {
        if (!$this->metadata->get('scopes.' . $name)) {
            throw new Error("Entity `$name` does not exist.");
        }

        $isCustom = $this->metadata->get(['scopes', $name, 'isCustom']);

        if ($this->metadata->get(['scopes', $name, 'statusFieldLocked'])) {
            unset($data['statusField']);
        }

        $initialData = [
            'optimisticConcurrencyControl' =>
                $this->metadata->get(['entityDefs', $name, 'optimisticConcurrencyControl']) ?? false,
            'fullTextSearch' =>
                $this->metadata->get(['entityDefs', $name, 'collection', 'fullTextSearch']) ?? false,
        ];

        if (array_key_exists('stream', $data)) {
            $this->metadata->set('scopes', $name, ['stream' => (bool) $data['stream']]);
        }

        if (array_key_exists('disabled', $data)) {
            $this->metadata->set('scopes', $name, ['disabled' => (bool) $data['disabled']]);
        }

        if (array_key_exists('statusField', $data)) {
            $this->metadata->set('scopes', $name, ['statusField' => $data['statusField']]);
        }

        if (isset($data['sortBy'])) {
            $this->metadata->set('entityDefs', $name, [
                'collection' => ['orderBy' => $data['sortBy']],
            ]);

            if (isset($data['sortDirection'])) {
                $this->metadata->set('entityDefs', $name, [
                    'collection' => ['order' => $data['sortDirection']],
                ]);
            }
        }

        if (isset($data['textFilterFields'])) {
            $this->metadata->set('entityDefs', $name, [
                'collection' => ['textFilterFields' => $data['textFilterFields']]
            ]);
        }

        if (isset($data['fullTextSearch'])) {
            $this->metadata->set('entityDefs', $name, [
                'collection' => ['fullTextSearch' => (bool) $data['fullTextSearch']],
            ]);
        }

        if (isset($data['countDisabled'])) {
            $this->metadata->set('entityDefs', $name, [
                'collection' => ['countDisabled' => (bool) $data['countDisabled']],
            ]);
        }

        if (array_key_exists('kanbanStatusIgnoreList', $data)) {
            $itemValue = $data['kanbanStatusIgnoreList'] ?: null;

            $this->metadata->set('scopes', $name, ['kanbanStatusIgnoreList' => $itemValue]);
        }

        if (array_key_exists('kanbanViewMode', $data)) {
            $this->metadata->set('clientDefs', $name, ['kanbanViewMode' => $data['kanbanViewMode']]);
        }

        if (array_key_exists('color', $data)) {
            $this->metadata->set('clientDefs', $name, ['color' => $data['color']]);
        }

        if (array_key_exists('iconClass', $data)) {
            $this->metadata->set('clientDefs', $name, ['iconClass' => $data['iconClass']]);
        }

        $this->setAdditionalParamsInMetadata($name, $data);

        if (!empty($data['labelSingular'])) {
            $labelSingular = $data['labelSingular'];
            $labelCreate = $this->language->translateLabel('Create') . ' ' . $labelSingular;

            $this->language->set('Global', 'scopeNames', $name, $labelSingular);
            $this->language->set($name, 'labels', 'Create ' . $name, $labelCreate);

            if ($isCustom) {
                $this->baseLanguage->set('Global', 'scopeNames', $name, $labelSingular);
                $this->baseLanguage->set($name, 'labels', 'Create ' . $name, $labelCreate);
            }
        }

        if (!empty($data['labelPlural'])) {
            $labelPlural = $data['labelPlural'];
            $this->language->set('Global', 'scopeNamesPlural', $name, $labelPlural);

            if ($isCustom) {
                $this->baseLanguage->set('Global', 'scopeNamesPlural', $name, $labelPlural);
            }
        }

        $this->metadata->save();
        $this->language->save();

        if ($isCustom) {
            if ($this->isLanguageNotBase()) {
                $this->baseLanguage->save();
            }
        }

        $this->dataManager->clearCache();

        if (
            !$initialData['optimisticConcurrencyControl'] &&
            !empty($data['optimisticConcurrencyControl']) &&
            (
                empty($data['fullTextSearch']) || $initialData['fullTextSearch']
            )
        ) {
            $this->dataManager->rebuild();
        }
    }

    /**
     * @param array{forceRemove?: bool} $params
     * @throws Forbidden
     * @throws Error
     */
    public function delete(string $name, array $params = []): void
    {
        if (!$this->isCustom($name)) {
            throw new Forbidden;
        }

        $normalizedName = Util::normalizeClassName($name);

        $type = $this->metadata->get(['scopes', $name, 'type']);
        $isNotRemovable = $this->metadata->get(['scopes', $name, 'isNotRemovable']);
        $templateDefs = $this->metadata->get(['app', 'entityTemplates', $type], []);

        if ((!empty($templateDefs['isNotRemovable']) || $isNotRemovable) && empty($params['forceRemove'])) {
            throw new Error('Type \''.$type.'\' is not removable.');
        }

        $this->metadata->delete('entityDefs', $name);
        $this->metadata->delete('clientDefs', $name);
        $this->metadata->delete('recordDefs', $name);
        $this->metadata->delete('selectDefs', $name);
        $this->metadata->delete('scopes', $name);

        foreach ($this->metadata->get(['entityDefs', $name, 'links'], []) as $link => $item) {
            try {
                $this->deleteLink(['entity' => $name, 'link' => $link]);
            }
            catch (Exception) {}
        }

        $this->fileManager->removeFile("custom/Espo/Custom/Resources/metadata/entityDefs/$name.json");
        $this->fileManager->removeFile("custom/Espo/Custom/Resources/metadata/clientDefs/$name.json");
        $this->fileManager->removeFile("custom/Espo/Custom/Resources/metadata/recordDefs/$name.json");
        $this->fileManager->removeFile("custom/Espo/Custom/Resources/metadata/selectDefs/$name.json");
        $this->fileManager->removeFile("custom/Espo/Custom/Resources/metadata/scopes/$name.json");

        $this->fileManager->removeFile("custom/Espo/Custom/Entities/$normalizedName.php");
        $this->fileManager->removeFile("custom/Espo/Custom/Services/$normalizedName.php");
        $this->fileManager->removeFile("custom/Espo/Custom/Controllers/$normalizedName.php");
        $this->fileManager->removeFile("custom/Espo/Custom/Repositories/$normalizedName.php");

        if (file_exists("custom/Espo/Custom/SelectManagers/$normalizedName.php")) {
            $this->fileManager->removeFile("custom/Espo/Custom/SelectManagers/$normalizedName.php");
        }

        $this->fileManager->removeInDir("custom/Espo/Custom/Resources/layouts/$normalizedName");
        $this->fileManager->removeDir("custom/Espo/Custom/Resources/layouts/$normalizedName");

        $languageList = $this->metadata->get(['app', 'language', 'list'], []);

        foreach ($languageList as $language) {
            $filePath = 'custom/Espo/Custom/Resources/i18n/' . $language . '/' . $normalizedName . '.json';

            if (!file_exists($filePath)) {
                continue;
            }

            $this->fileManager->removeFile($filePath);
        }

        try {
            $this->language->delete('Global', 'scopeNames', $name);
            $this->language->delete('Global', 'scopeNamesPlural', $name);

            $this->baseLanguage->delete('Global', 'scopeNames', $name);
            $this->baseLanguage->delete('Global', 'scopeNamesPlural', $name);
        }
        catch (Exception) {}

        $this->metadata->save();
        $this->language->save();

        if ($this->isLanguageNotBase()) {
            $this->baseLanguage->save();
        }

        if ($type) {
            $this->processHook('afterRemove', $type, $name);
        }

        $this->deleteEntityTypeFromConfigParams($name);

        $this->dataManager->clearCache();
    }

    private function deleteEntityTypeFromConfigParams(string $entityType): void
    {
        $paramList = $this->metadata->get(['app', 'config', 'entityTypeListParamList']) ?? [];

        foreach ($paramList as $param) {
            $this->deleteEntityTypeFromConfigParam($entityType, $param);
        }

        $this->configWriter->save();
    }

    private function deleteEntityTypeFromConfigParam(string $entityType, string $param): void
    {
        $list = $this->config->get($param) ?? [];

        if (($key = array_search($entityType, $list)) !== false) {
            unset($list[$key]);

            $list = array_values($list);
        }

        $this->configWriter->set($param, $list);
    }

    protected function isCustom(string $name): bool
    {
        return (bool) $this->metadata->get('scopes.' . $name . '.isCustom');
    }

    /**
     * @param array{
     *   linkType: string,
     *   entity: string,
     *   link: string,
     *   entityForeign: string,
     *   linkForeign: string,
     *   label: string,
     *   labelForeign: string,
     *   relationName?: ?string,
     *   linkMultipleField?: bool,
     *   linkMultipleFieldForeign?: bool,
     *   audited?: bool,
     *   auditedForeign?: bool,
     * } $params
     * @throws BadRequest
     * @throws Error
     * @throws Conflict
     */
    public function createLink(array $params): void
    {
        $linkType = $params['linkType'];

        $entity = $params['entity'];
        $link = trim($params['link']);

        $entityForeign = $params['entityForeign'];
        $linkForeign = trim($params['linkForeign']);

        $label = $params['label'];
        $labelForeign = $params['labelForeign'];

        $relationName = null;
        $dataRight = null;

        if (empty($linkType)) {
            throw new BadRequest("No link type.");
        }

        if (empty($entity)) {
            throw new BadRequest("No entity.");
        }

        if (empty($link) || empty($linkForeign)) {
            throw new BadRequest("No link or link-foreign.");
        }

        if ($linkType === 'manyToMany') {
            $relationName = !empty($params['relationName']) ?
                $params['relationName'] :
                lcfirst($entity) . $entityForeign;

            if (
                strlen($relationName) > NameUtil::MAX_LINK_NAME_LENGTH
            ) {
                throw new Error("Relation name should not be longer than " . NameUtil::MAX_LINK_NAME_LENGTH . ".");
            }

            if (preg_match('/[^a-z]/', $relationName[0])) {
                throw new Error("Relation name should start with a lower case letter.");
            }

            if ($this->metadata->get(['scopes', ucfirst($relationName)])) {
                throw new Conflict("Entity with the same name '$relationName' exists.");
            }

            if ($this->nameUtil->relationshipExists($relationName)) {
                throw new Conflict("Relationship with the same name '$relationName' exists.");
            }
        }

        $linkParams = LinkParams::createBuilder()
            ->setType($linkType)
            ->setEntityType($entity)
            ->setForeignEntityType($entityForeign)
            ->setLink($link)
            ->setForeignLink($linkForeign)
            ->setName($relationName)
            ->build();

        if (strlen($link) > NameUtil::MAX_LINK_NAME_LENGTH || strlen($linkForeign) > NameUtil::MAX_LINK_NAME_LENGTH) {
            throw new Error("Link name should not be longer than " . NameUtil::MAX_LINK_NAME_LENGTH . ".");
        }

        if (is_numeric($link[0]) || is_numeric($linkForeign[0])) {
            throw new Error('Bad link name.');
        }

        if (preg_match('/[^a-z]/', $link[0])) {
            throw new Error("Link name should start with a lower case letter.");
        }

        if (preg_match('/[^a-z]/', $linkForeign[0])) {
            throw new Error("Link name should start with a lower case letter.");
        }

        if (in_array($link, NameUtil::LINK_FORBIDDEN_NAME_LIST)) {
            throw new Conflict("Link name '$link' is not allowed.");
        }

        if (in_array($linkForeign, NameUtil::LINK_FORBIDDEN_NAME_LIST)) {
            throw new Conflict("Link name '$linkForeign' is not allowed.");
        }

        foreach ($this->routeUtil->getFullList() as $route) {
            if ($route->getRoute() === "/$entity/:id/$link") {
                throw new Conflict("Link name '$link' conflicts with existing API endpoint.");
            }
        }

        if ($entityForeign) {
            foreach ($this->routeUtil->getFullList() as $route) {
                if ($route->getRoute() === "/$entityForeign/:id/$linkForeign") {
                    throw new Conflict("Link name '$linkForeign' conflicts with existing API endpoint.");
                }
            }
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

        if ($linkType !== 'childrenToParent') {
            if (empty($entityForeign)) {
                throw new Error();
            }
        }

        if ($this->metadata->get('entityDefs.' . $entity . '.links.' . $link)) {
            throw new Conflict("Link $entity::$link already exists.");
        }

        if ($entityForeign) {
            if ($this->metadata->get('entityDefs.' . $entityForeign . '.links.' . $linkForeign)) {
                throw new Conflict("Link $entityForeign::$linkForeign already exists.");
            }
        }

        if ($entity === $entityForeign) {
            if (
                $link === lcfirst($entity) ||
                $linkForeign === lcfirst($entity) ||
                $link === $linkForeign
            ) {
                throw new Conflict("Link names $entityForeign, $linkForeign conflict.");
            }
        }

        if ($linkForeign === lcfirst($entityForeign)) {
            throw new Conflict("Link $entityForeign::$linkForeign must not match entity type name.");
        }

        if ($link === lcfirst($entity)) {
            throw new Conflict("Link $entity::$link must not match entity type name.");
        }

        switch ($linkType) {
            case 'oneToOneRight':
            case 'oneToOneLeft':

                if (
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign) ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Name')
                ) {
                    throw new Conflict("Field $entityForeign::$linkForeign already exists.");
                }

                if (
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link) ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Name')
                ) {
                    throw new Conflict("Field $entity::$link already exists.");
                }

                if ($linkType === 'oneToOneLeft') {
                    $dataLeft = [
                        'fields' => [
                            $link => [
                                'type' => 'linkOne',
                            ],
                        ],
                        'links' => [
                            $link => [
                                'type' => Entity::HAS_ONE,
                                'foreign' => $linkForeign,
                                'entity' => $entityForeign,
                                'isCustom' => true,
                            ],
                        ],
                    ];

                    $dataRight = [
                        'fields' => [
                            $linkForeign => [
                                'type' => 'link',
                            ],
                        ],
                        'links' => [
                            $linkForeign => [
                                'type' => Entity::BELONGS_TO,
                                'foreign' => $link,
                                'entity' => $entity,
                                'isCustom' => true,
                            ],
                        ],
                    ];
                }
                else {
                    $dataLeft = [
                        'fields' => [
                            $link => [
                                'type' => 'link',
                                'isCustom' => true,
                            ],
                        ],
                        'links' => [
                            $link => [
                                'type' => Entity::BELONGS_TO,
                                'foreign' => $linkForeign,
                                'entity' => $entityForeign,
                                'isCustom' => true,
                            ],
                        ],
                    ];

                    $dataRight = [
                        'fields' => [
                            $linkForeign => [
                                'type' => 'linkOne',
                                'isCustom' => true,
                            ],
                        ],
                        'links' => [
                            $linkForeign => [
                                'type' => Entity::HAS_ONE,
                                'foreign' => $link,
                                'entity' => $entity,
                                'isCustom' => true,
                            ],
                        ],
                    ];
                }

                break;

            case 'oneToMany':

                if (
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign) ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entityForeign . '.fields.' . $linkForeign . 'Name')
                ) {
                    throw new Conflict("Field $entityForeign::$linkForeign already exists.");
                }

                $dataLeft = [
                    'fields' => [
                        $link => [
                            'type' => 'linkMultiple',
                            'layoutDetailDisabled' => !$linkMultipleField,
                            'layoutMassUpdateDisabled'  => !$linkMultipleField,
                            'layoutListDisabled' => !$linkMultipleField,
                            'noLoad' => !$linkMultipleField,
                            'importDisabled' => !$linkMultipleField,
                            'exportDisabled' => !$linkMultipleField,
                            'customizationDisabled' => !$linkMultipleField,
                            'isCustom' => true,
                        ],
                    ],
                    'links' => [
                        $link => [
                            'type' => Entity::HAS_MANY,
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true,
                       ],
                    ],
                ];

                $dataRight = [
                    'fields' => [
                        $linkForeign => [
                            'type' => 'link',
                        ],
                    ],
                    'links' => [
                        $linkForeign => [
                            'type' => Entity::BELONGS_TO,
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true,
                        ],
                    ],
                ];

                break;

            case 'manyToOne':

                if (
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link) ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Id') ||
                    $this->metadata->get('entityDefs.' . $entity . '.fields.' . $link . 'Name')
                ) {
                    throw new Conflict("Field $entity::$link already exists.");
                }

                $dataLeft = [
                    'fields' => [
                        $link => [
                            'type' => 'link',
                        ],
                    ],
                    'links' => [
                        $link => [
                            'type' => Entity::BELONGS_TO,
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                $dataRight = [
                    'fields' => [
                        $linkForeign => [
                            'type' => 'linkMultiple',
                            'layoutDetailDisabled' => !$linkMultipleFieldForeign,
                            'layoutMassUpdateDisabled' => !$linkMultipleFieldForeign,
                            'layoutListDisabled' => !$linkMultipleFieldForeign,
                            'noLoad' => !$linkMultipleFieldForeign,
                            'importDisabled' => !$linkMultipleFieldForeign,
                            'exportDisabled' => !$linkMultipleFieldForeign,
                            'customizationDisabled' => !$linkMultipleField,
                            'isCustom' => true,
                        ]
                    ],
                    'links' => [
                        $linkForeign => [
                            'type' => Entity::HAS_MANY,
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true,
                        ],
                    ],
                ];

                break;

            case 'manyToMany':
                $dataLeft = [
                    'fields' => [
                        $link => [
                            'type' => 'linkMultiple',
                            'layoutDetailDisabled' => !$linkMultipleField,
                            'layoutMassUpdateDisabled' => !$linkMultipleField,
                            'layoutListDisabled' => !$linkMultipleField,
                            'noLoad' => !$linkMultipleField,
                            'importDisabled' => !$linkMultipleField,
                            'exportDisabled' => !$linkMultipleField,
                            'customizationDisabled' => !$linkMultipleField,
                            'isCustom' => true,
                        ]
                    ],
                    'links' => [
                        $link => [
                            'type' => Entity::HAS_MANY,
                            'relationName' => $relationName,
                            'foreign' => $linkForeign,
                            'entity' => $entityForeign,
                            'audited' => $auditedForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                $dataRight = [
                    'fields' => [
                        $linkForeign => [
                            'type' => 'linkMultiple',
                            'layoutDetailDisabled' => !$linkMultipleFieldForeign,
                            'layoutMassUpdateDisabled' => !$linkMultipleFieldForeign,
                            'layoutListDisabled' => !$linkMultipleFieldForeign,
                            'noLoad'  => !$linkMultipleFieldForeign,
                            'importDisabled' => !$linkMultipleFieldForeign,
                            'exportDisabled' => !$linkMultipleFieldForeign,
                            'customizationDisabled' => !$linkMultipleField,
                            'isCustom' => true,
                        ]
                    ],
                    'links' => [
                        $linkForeign => [
                            'type' => Entity::HAS_MANY,
                            'relationName' => $relationName,
                            'foreign' => $link,
                            'entity' => $entity,
                            'audited' => $audited,
                            'isCustom' => true,
                        ]
                    ]
                ];

                if ($entityForeign == $entity) {
                    $dataLeft['links'][$link]['midKeys'] = ['leftId', 'rightId'];

                    $dataRight['links'][$linkForeign]['midKeys'] = ['rightId', 'leftId'];
                }

                break;

            case 'childrenToParent':
                $dataLeft = [
                    'fields' => [
                        $link => [
                            'type' => 'linkParent',
                            'entityList' => $params['parentEntityTypeList'] ?? null,
                        ],
                    ],
                    'links' => [
                        $link => [
                            'type' => Entity::BELONGS_TO_PARENT,
                            'foreign' => $linkForeign,
                            'isCustom' => true,
                        ],
                    ],
                ];

                break;

            default:
                throw new BadRequest();
        }

        $this->metadata->set('entityDefs', $entity, $dataLeft);

        if ($entityForeign) {
            $this->metadata->set('entityDefs', $entityForeign, $dataRight);
        }

        $this->metadata->save();

        $this->language->set($entity, 'fields', $link, $label);
        $this->language->set($entity, 'links', $link, $label);

        if ($entityForeign) {
            $this->language->set($entityForeign, 'fields', $linkForeign, $labelForeign);
            $this->language->set($entityForeign, 'links', $linkForeign, $labelForeign);
        }

        $this->language->save();

        if ($this->isLanguageNotBase()) {
            $this->baseLanguage->set($entity, 'fields', $link, $label);
            $this->baseLanguage->set($entity, 'links', $link, $label);

            if ($entityForeign) {
                $this->baseLanguage->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                $this->baseLanguage->set($entityForeign, 'links', $linkForeign, $labelForeign);
            }

            $this->baseLanguage->save();
        }

        if ($linkType === 'childrenToParent') {
            $foreignLinkEntityTypeList = $params['foreignLinkEntityTypeList'] ?? null;

            if (is_array($foreignLinkEntityTypeList)) {
                $this->updateParentForeignLinks($entity, $link, $linkForeign, $foreignLinkEntityTypeList);
            }
        }

        $this->linkHookProcessor->processCreate($linkParams);

        $this->dataManager->rebuild();
    }

    /**
     * @param array{
     *   entity: string,
     *   link: string,
     *   entityForeign?: ?string,
     *   linkForeign?: ?string,
     *   label?: string,
     *   labelForeign?: string,
     *   linkMultipleField?: bool,
     *   linkMultipleFieldForeign?: bool,
     *   audited?: bool,
     *   auditedForeign?: bool,
     *   parentEntityTypeList?: string[],
     *   foreignLinkEntityTypeList?: string[],
     * } $params
     * @throws BadRequest
     * @throws Error
     */
    public function updateLink(array $params): void
    {
        $entity = $params['entity'];
        $link = $params['link'];
        $entityForeign = $params['entityForeign'] ?? null;
        $linkForeign = $params['linkForeign'] ?? null;

        if (empty($link)) {
            throw new BadRequest();
        }

        if (empty($entity)) {
            throw new BadRequest();
        }

        $linkType = $this->metadata->get("entityDefs.$entity.links.$link.type");
        $isCustom = $this->metadata->get("entityDefs.$entity.links.$link.isCustom");

        if ($linkType !== Entity::BELONGS_TO_PARENT) {
            if (empty($entityForeign)) {
                throw new BadRequest();
            }

            if (empty($linkForeign)) {
                throw new BadRequest();
            }
        }

        if (
            $this->metadata->get("entityDefs.$entity.links.$link.type") == Entity::HAS_MANY &&
            $this->metadata->get("entityDefs.$entity.links.$link.isCustom")
        ) {
            if (array_key_exists('linkMultipleField', $params)) {
                $linkMultipleField = $params['linkMultipleField'];

                $dataLeft = [
                    'fields' => [
                        $link => [
                            'type' => 'linkMultiple',
                            'layoutDetailDisabled' => !$linkMultipleField,
                            'layoutMassUpdateDisabled' => !$linkMultipleField,
                            'layoutListDisabled' => !$linkMultipleField,
                            'noLoad' => !$linkMultipleField,
                            'importDisabled' => !$linkMultipleField,
                            'exportDisabled' => !$linkMultipleField,
                            'customizationDisabled' => !$linkMultipleField,
                            'isCustom' => true,
                        ]
                    ]
                ];

                $this->metadata->set('entityDefs', $entity, $dataLeft);

                $this->metadata->save();
            }
        }

        if (
            $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign.type") == Entity::HAS_MANY &&
            $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign.isCustom")
        ) {
            /** @var string $entityForeign */

            if (array_key_exists('linkMultipleFieldForeign', $params)) {
                $linkMultipleFieldForeign = $params['linkMultipleFieldForeign'];

                $dataRight = [
                    'fields' => [
                        $linkForeign => [
                            'type' => 'linkMultiple',
                            'layoutDetailDisabled' => !$linkMultipleFieldForeign,
                            'layoutMassUpdateDisabled' => !$linkMultipleFieldForeign,
                            'layoutListDisabled' => !$linkMultipleFieldForeign,
                            'noLoad' => !$linkMultipleFieldForeign,
                            'importDisabled' => !$linkMultipleFieldForeign,
                            'exportDisabled' => !$linkMultipleFieldForeign,
                            'customizationDisabled' => !$linkMultipleFieldForeign,
                            'isCustom' => true,
                        ]
                    ]
                ];

                $this->metadata->set('entityDefs', $entityForeign, $dataRight);
                $this->metadata->save();
            }
        }

        if (
            in_array($this->metadata->get("entityDefs.$entity.links.$link.type"), [
                Entity::HAS_MANY,
                Entity::HAS_CHILDREN,
            ])
        ) {
            if (array_key_exists('audited', $params)) {
                $audited = $params['audited'];

                $dataLeft = [
                    'links' => [
                        $link => [
                            "audited" => $audited,
                        ],
                    ],
                ];
                $this->metadata->set('entityDefs', $entity, $dataLeft);
                $this->metadata->save();
            }
        }

        if (
            $linkForeign &&
            in_array(
                $this->metadata->get("entityDefs.$entityForeign.links.$linkForeign.type"),
                [
                    Entity::HAS_MANY,
                    Entity::HAS_CHILDREN,
                ]
            )
        ) {
            /** @var string $entityForeign */

            if (array_key_exists('auditedForeign', $params)) {
                $auditedForeign = $params['auditedForeign'];

                $dataRight = [
                    'links' => [
                        $linkForeign => [
                            "audited" => $auditedForeign,
                        ],
                    ],
                ];

                $this->metadata->set('entityDefs', $entityForeign, $dataRight);
                $this->metadata->save();
            }
        }

        if ($linkType === Entity::BELONGS_TO_PARENT) {
            $parentEntityTypeList = $params['parentEntityTypeList'] ?? null;

            if (is_array($parentEntityTypeList)) {
                $data = [
                    'fields' => [
                        $link => [
                            'entityList' => $parentEntityTypeList,
                        ],
                    ],
                ];

                $this->metadata->set('entityDefs', $entity, $data);
                $this->metadata->save();
            }

            $foreignLinkEntityTypeList = $params['foreignLinkEntityTypeList'] ?? null;

            if ($linkForeign && is_array($foreignLinkEntityTypeList)) {
                $this->updateParentForeignLinks($entity, $link, $linkForeign, $foreignLinkEntityTypeList);
            }
        }

        $label = null;

        if (isset($params['label'])) {
            $label = $params['label'];
        }

        if ($label) {
            $this->language->set($entity, 'fields', $link, $label);
            $this->language->set($entity, 'links', $link, $label);
        }

        $labelForeign = null;

        if ($linkType !== Entity::BELONGS_TO_PARENT) {
            /** @var string $linkForeign */
            /** @var string $entityForeign */

            if (isset($params['labelForeign'])) {
                $labelForeign = $params['labelForeign'];
            }

            if ($labelForeign) {
                $this->language->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                $this->language->set($entityForeign, 'links', $linkForeign, $labelForeign);
            }
        }

        $this->language->save();

        if ($isCustom) {
            if ($this->language->getLanguage() !== $this->baseLanguage->getLanguage()) {

                if ($label) {
                    $this->baseLanguage->set($entity, 'fields', $link, $label);
                    $this->baseLanguage->set($entity, 'links', $link, $label);
                }

                if ($labelForeign && $linkType !== Entity::BELONGS_TO_PARENT) {
                    /** @var string $linkForeign */
                    /** @var string $entityForeign */

                    $this->baseLanguage->set($entityForeign, 'fields', $linkForeign, $labelForeign);
                    $this->baseLanguage->set($entityForeign, 'links', $linkForeign, $labelForeign);
                }

                $this->baseLanguage->save();
            }
        }

        $this->dataManager->clearCache();
    }

    /**
     * @param array{
     *   entity?: string,
     *   link?: string,
     * } $params
     * @throws Error
     * @throws BadRequest
     */
    public function deleteLink(array $params): void
    {
        $entity = $params['entity'] ?? null;
        $link = $params['link'] ?? null;

        if (!$this->metadata->get("entityDefs.$entity.links.$link.isCustom")) {
            throw new Error("Could not delete link $entity.$link. Not isCustom.");
        }

        if (empty($entity) || empty($link)) {
            throw new BadRequest();
        }

        $entityForeign = $this->metadata->get("entityDefs.$entity.links.$link.entity");
        $linkForeign = $this->metadata->get("entityDefs.$entity.links.$link.foreign");
        $linkType = $this->metadata->get("entityDefs.$entity.links.$link.type");

        if (!$this->metadata->get(['entityDefs', $entity, 'links', $link, 'isCustom'])) {
            throw new Error("Can't remove not custom link.");
        }

        if ($linkType === Entity::HAS_CHILDREN) {
            $this->metadata->delete('entityDefs', $entity, [
                'links.' . $link,
            ]);

            $this->metadata->save();

            return;
        }

        if ($linkType === Entity::BELONGS_TO_PARENT) {
            $this->metadata->delete('entityDefs', $entity, [
                'fields.' . $link,
                'links.' . $link,
            ]);

            $this->metadata->save();

            if ($linkForeign) {
                $this->updateParentForeignLinks($entity, $link, $linkForeign, []);
            }

            return;
        }

        if (empty($entityForeign) || empty($linkForeign)) {
            throw new BadRequest();
        }

        $foreignLinkType = $this->metadata->get(['entityDefs', $entityForeign, 'links', $linkForeign, 'type']);

        $type = null;

        if ($linkType === Entity::HAS_MANY && $foreignLinkType === Entity::HAS_MANY) {
            $type = LinkType::MANY_TO_MANY;
        }
        else if ($linkType === Entity::HAS_MANY && $foreignLinkType === Entity::BELONGS_TO) {
            $type = LinkType::ONE_TO_MANY;
        }
        else if ($linkType === Entity::BELONGS_TO && $foreignLinkType === Entity::HAS_MANY) {
            $type = LinkType::MANY_TO_ONE;
        }
        else if ($linkType === Entity::HAS_ONE && $foreignLinkType === Entity::BELONGS_TO) {
            $type = LinkType::ONE_TO_ONE_LEFT;
        }
        else if ($linkType === Entity::BELONGS_TO && $foreignLinkType === Entity::HAS_ONE) {
            $type = LinkType::ONE_TO_ONE_RIGHT;
        }

        $name = $this->metadata->get(['entityDefs', $entity, $link, 'relationName']) ??
            $this->metadata->get(['entityDefs', $entityForeign, $linkForeign, 'relationName']);

        $linkParams = null;

        if ($type) {
            $linkParams = LinkParams::createBuilder()
                ->setType($type)
                ->setName($name)
                ->setEntityType($entity)
                ->setForeignEntityType($entityForeign)
                ->setLink($link)
                ->setForeignLink($linkForeign)
                ->build();
        }

        $this->metadata->delete('entityDefs', $entity, [
            'fields.' . $link,
            'links.' . $link
        ]);

        $this->metadata->delete('entityDefs', $entityForeign, [
            'fields.' . $linkForeign,
            'links.' . $linkForeign
        ]);

        $this->metadata->save();

        if ($linkParams) {
            $this->linkHookProcessor->processDelete($linkParams);
        }

        $this->dataManager->clearCache();
    }

    /**
     * @param array<string, string> $data
     * @throws Error
     */
    public function setFormulaData(string $scope, array $data): void
    {
        $this->metadata->set('formula', $scope, $data);
        $this->metadata->save();

        $this->dataManager->clearCache();
    }

    /**
     * @param ?array<string, mixed> $params
     */
    protected function processHook(string $methodName, string $type, string $name, &$params = null): void
    {
        $hook = $this->getHook($type);

        if (!$hook) {
            return;
        }

        if (!method_exists($hook, $methodName)) {
            return;
        }

        $hook->$methodName($name, $params);
    }

    protected function getHook(string $type): ?object
    {
        $templateDefs = $this->metadata->get(['app', 'entityTemplates', $type], []);

        $className = 'Espo\\Tools\\EntityManager\\Hooks\\' . $type . 'Type';

        if (!empty($templateDefs['module'])) {
            $templateModuleName = $templateDefs['module'];

            $normalizedTemplateModuleName = Util::normalizeClassName($templateModuleName);

            $className =
                'Espo\\Modules\\' . $normalizedTemplateModuleName .
                '\\Tools\\EntityManager\\Hooks\\' . $type . 'Type';
        }

        $className = $this->metadata->get(['app', 'entityTemplates', $type, 'hookClassName'], $className);

        if (class_exists($className)) {
            return $this->injectableFactory->create($className);
        }

        return null;
    }

    /**
     * @throws Error
     */
    public function resetToDefaults(string $scope): void
    {
        if ($this->isCustom($scope)) {
            throw new Error("Can't reset to defaults custom entity type '$scope.'");
        }

        $this->metadata->delete('scopes', $scope, [
            'disabled',
            'stream',
            'statusField',
            'kanbanStatusIgnoreList',
        ]);

        $this->metadata->delete('clientDefs', $scope, [
            'iconClass',
            'statusField',
            'kanbanViewMode',
        ]);

        $this->metadata->delete('entityDefs', $scope, [
            'collection.sortBy',
            'collection.asc',
            'collection.orderBy',
            'collection.order',
            'collection.textFilterFields',
            'collection.fullTextSearch',
        ]);

        foreach ($this->getAdditionalParamLocationMap($scope) as $param => $location) {
            $this->metadata->delete($location, $scope, [$param]);
        }

        $this->metadata->save();

        $this->language->delete('Global', 'scopeNames', $scope);
        $this->language->delete('Global', 'scopeNamesPlural', $scope);
        $this->language->save();

        $this->dataManager->clearCache();
    }

    /**
     * @param string[] $foreignLinkEntityTypeList
     */
    protected function updateParentForeignLinks(
        string $entityType,
        string $link,
        string $linkForeign,
        array $foreignLinkEntityTypeList
    ): void {

        $toCreateList = [];

        foreach ($foreignLinkEntityTypeList as $foreignEntityType) {
            $linkDefs = $this->metadata->get(['entityDefs', $foreignEntityType, 'links']) ?? [];

            foreach ($linkDefs as $kLink => $defs) {
                $kForeign = $defs['foreign'] ?? null;
                $kIsCustom = $defs['isCustom'] ?? false;
                $kEntity = $defs['entity'] ?? null;

                if (
                    $kForeign === $link && !$kIsCustom && $kEntity == $entityType
                ) {
                    continue 2;
                }

                if ($kLink == $linkForeign) {
                    if ($defs['type'] !== Entity::HAS_CHILDREN) {
                        continue 2;
                    }
                }
            }

            $toCreateList[] = $foreignEntityType;
        }

        /** @var string[] $entityTypeList */
        $entityTypeList = array_keys($this->metadata->get('entityDefs') ?? []);

        foreach ($entityTypeList as $itemEntityType) {
            $linkDefs = $this->metadata->get(['entityDefs', $itemEntityType, 'links']) ?? [];

            foreach ($linkDefs as $kLink => $defs) {
                $kForeign = $defs['foreign'] ?? null;
                $kIsCustom = $defs['isCustom'] ?? false;
                $kEntity = $defs['entity'] ?? null;

                if (
                    $kForeign === $link && $kIsCustom && $kEntity == $entityType &&
                    $defs['type'] == Entity::HAS_CHILDREN && $kLink === $linkForeign
                ) {
                    if (!in_array($itemEntityType, $toCreateList)) {
                        $this->metadata->delete('entityDefs', $itemEntityType, [
                            'links.' . $linkForeign,
                        ]);

                        $this->language->delete($itemEntityType, 'links', $linkForeign);

                        if (
                            $this->isLanguageNotBase()
                        ) {
                            $this->baseLanguage->delete($itemEntityType, 'links', $linkForeign);
                        }
                    }

                    break;
                }
            }
        }

        foreach ($toCreateList as $itemEntityType) {
            $this->metadata->set('entityDefs', $itemEntityType, [
                'links' => [
                    $linkForeign => [
                        'type' => Entity::HAS_CHILDREN,
                        'foreign' => $link,
                        'entity' => $entityType,
                        'isCustom' => true,
                    ],
                ],
            ]);

            $label = $this->language->translate($entityType, 'scopeNamesPlural');

            $this->language->set($itemEntityType, 'links', $linkForeign, $label);

            if ($this->isLanguageNotBase()) {
                $this->baseLanguage->set($itemEntityType, 'links', $linkForeign, $label);
            }
        }

        $this->metadata->save();

        $this->language->save();

        if ($this->isLanguageNotBase()) {
            $this->baseLanguage->save();
        }
    }

    private function isLanguageNotBase(): bool
    {
        return $this->language->getLanguage() !== $this->baseLanguage->getLanguage();
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    private function setAdditionalParamsInMetadata(string $entityType, array $data): void
    {
        $params = $this->getAdditionalParamLocationMap($entityType);

        foreach ($params as $param => $location) {
            if (!array_key_exists($param, $data)) {
                continue;
            }

            $value = $data[$param];

            $this->metadata->set($location, $entityType, [$param => $value]);
        }
    }

    /**
     * @return array<string, string>
     * @throws Error
     */
    private function getAdditionalParamLocationMap(string $entityType): array
    {
        $templateType = $this->metadata->get(['scopes', $entityType, 'type']);

        $map1 = $this->metadata->get(['app', 'entityManagerParams', 'Global']) ?? [];
        $map2 = $this->metadata->get(['app', 'entityManagerParams', '@' . ($templateType ?? '_')]) ?? [];
        $map3 = $this->metadata->get(['app', 'entityManagerParams', $entityType]) ?? [];

        if (version_compare(PHP_VERSION, '8.1.0') < 0) {
            // @todo Remove.
            /** @var array<string, array<string, mixed>> $params */
            $params = array_merge($map1, $map2, $map3);
        }
        else {
            /** @var array<string, array<string, mixed>> $params */
            $params = [...$map1, ...$map2, ...$map3];
        }

        $result = [];

        foreach ($params as $param => $defs) {
            $defs['location'] ??= self::DEFAULT_PARAM_LOCATION;

            $location = $defs['location'] ?? self::DEFAULT_PARAM_LOCATION;

            if (!in_array($location, ['scopes', 'entityDefs', 'clientDefs', 'recordDefs'])) {
                throw new Error("Param location `$location` is not supported.");
            }

            $result[$param] = $location;
        }

        return $result;
    }
}
