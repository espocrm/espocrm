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
use Espo\Tools\EntityManager\Hook\CreateHook;
use Espo\Tools\EntityManager\Hook\DeleteHook;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Tools\EntityManager\Hook\UpdateHook;
use Espo\Tools\LinkManager\LinkManager;
use Exception;
use RuntimeException;

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
        private NameUtil $nameUtil,
        private LinkManager $linkManager
    ) {}

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $replaceData @todo Revise.
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
            "namespace Espo\Custom\Controllers;\n\n".
            "class $normalizedName extends $templateNamespace\Controllers\\$type\n".
            "{\n".
            "}\n";

        $this->fileManager->putContents("custom/Espo/Custom/Controllers/$normalizedName.php", $contents);

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

        $entityTypeParams = new Params($name, $type, $params);

        $this->processCreateHook($entityTypeParams);

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
        $type = $this->metadata->get(['scopes', $name, 'type']);

        if ($this->metadata->get(['scopes', $name, 'statusFieldLocked'])) {
            unset($data['statusField']);
        }

        $initialData = [
            'optimisticConcurrencyControl' =>
                $this->metadata->get(['entityDefs', $name, 'optimisticConcurrencyControl']) ?? false,
            'fullTextSearch' =>
                $this->metadata->get(['entityDefs', $name, 'collection', 'fullTextSearch']) ?? false,
        ];

        $entityTypeParams = new Params($name, $type, array_merge($this->getCurrentParams($name), $data));
        $previousEntityTypeParams = new Params($name, $type, $this->getCurrentParams($name));

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

        $this->processUpdateHook($entityTypeParams, $previousEntityTypeParams);

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

        $entityTypeParams = new Params($name, $type, $this->getCurrentParams($name));

        $this->metadata->delete('entityDefs', $name);
        $this->metadata->delete('clientDefs', $name);
        $this->metadata->delete('recordDefs', $name);
        $this->metadata->delete('selectDefs', $name);
        $this->metadata->delete('scopes', $name);

        foreach ($this->metadata->get(['entityDefs', $name, 'links'], []) as $link => $item) {
            try {
                $this->linkManager->delete(['entity' => $name, 'link' => $link]);
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
            $this->processDeleteHook($entityTypeParams);
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

    private function isCustom(string $name): bool
    {
        return (bool) $this->metadata->get('scopes.' . $name . '.isCustom');
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

    private function processUpdateHook(Params $params, Params $previousParams): void
    {
        /** @var class-string<UpdateHook>[] $classNameList */
        $classNameList = $this->metadata->get(['app', 'entityManager', 'updateHookClassNameList']) ?? [];

        foreach ($classNameList as $className) {
            $hook = $this->injectableFactory->create($className);

            $hook->process($params, $previousParams);
        }
    }

    private function processDeleteHook(Params $params): void
    {
        /** @var class-string<DeleteHook>[] $classNameList */
        $classNameList = $this->metadata->get(['app', 'entityManager', 'deleteHookClassNameList']) ?? [];

        foreach ($classNameList as $className) {
            $hook = $this->injectableFactory->create($className);

            $hook->process($params);
        }
    }

    private function processCreateHook(Params $params): void
    {
        /** @var class-string<CreateHook>[] $classNameList */
        $classNameList = $this->metadata->get(['app', 'entityManager', 'createHookClassNameList']) ?? [];

        foreach ($classNameList as $className) {
            $hook = $this->injectableFactory->create($className);

            $hook->process($params);
        }
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
     * @param array<string, mixed> $data
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
     * @return array<string, mixed>
     */
    private function getCurrentParams(string $entityType): array
    {
        $data = [];

        foreach ($this->getAdditionalParamLocationMap($entityType) as $param => $location) {
            $data[$param] = $this->metadata->get([$location, $entityType, $param]);
        }

        $data['statusField'] = $this->metadata->get(['scopes', $entityType, 'statusField']);
        $data['kanbanViewMode'] = $this->metadata->get(['scopes', $entityType, 'kanbanViewMode']);
        $data['disabled'] = $this->metadata->get(['scopes', $entityType, 'disabled']);

        return $data;
    }

    /**
     * @return array<string, string>
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
                throw new RuntimeException("Param location `$location` is not supported.");
            }

            $result[$param] = $location;
        }

        return $result;
    }

    private function isLanguageNotBase(): bool
    {
        return $this->language->getLanguage() !== $this->baseLanguage->getLanguage();
    }

    public function resetFormulaToDefault(string $scope, string $type): void
    {
        $this->metadata->delete('formula', $scope, $type);
        $this->metadata->save();
    }
}
