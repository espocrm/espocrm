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

namespace Espo\Core\Utils;

use Espo\Core\{
    Exceptions\Error,
    Utils\File\Manager as FileManager,
    Utils\Module,
    Utils\Metadata\Helper,
    Utils\DataCache,
    Utils\Resource\Reader as ResourceReader,
    Utils\Resource\Reader\Params as ResourceReaderParams,
};

use stdClass;

/**
 * Application metadata.
 */
class Metadata
{
    private $data = null;

    private $objData = null;

    private $useCache;

    private $cacheKey = 'metadata';

    private $objCacheKey = 'objMetadata';

    private $customPath = 'custom/Espo/Custom/Resources/metadata';

    private $deletedData = [];

    private $changedData = [];

    private $metadataHelper;

    private $module;

    private $fileManager;

    private $dataCache;

    private $resourceReader;

    public function __construct(
        FileManager $fileManager,
        DataCache $dataCache,
        ResourceReader $resourceReader,
        Module $module,
        bool $useCache = false
    ){
        $this->fileManager = $fileManager;
        $this->dataCache = $dataCache;
        $this->resourceReader = $resourceReader;
        $this->module = $module;

        $this->useCache = $useCache;
    }

    private function getMetadataHelper(): Helper
    {
        if (!isset($this->metadataHelper)) {
            $this->metadataHelper = new Helper($this);
        }

        return $this->metadataHelper;
    }

    /**
     * Init metadata.
     */
    public function init(bool $reload = false): void
    {
        if (!$this->useCache) {
            $reload = true;
        }

        if ($this->dataCache->has($this->cacheKey) && !$reload) {
            $this->data = $this->dataCache->get($this->cacheKey);

            return;
        }

        $this->clearVars();

        $objData = $this->getAllObjects(false, $reload);

        $this->data = Util::objectToArray($objData);

        if ($this->useCache) {
            $this->dataCache->store($this->cacheKey, $this->data);
        }
    }

    /**
     * Get metadata array.
     *
     * @return array
     */
    private function getData(): array
    {
        if (empty($this->data) || !is_array($this->data)) {
            $this->init();
        }

        return $this->data;
    }

    /**
    * Get metadata by key.
    *
    * @param string|array $key
    * @param mixed $default
    *
    * @return mixed
    */
    public function get($key = null, $default = null)
    {
        $result = Util::getValueByKey($this->getData(), $key, $default);

        return $result;
    }

    /**
    * Get all metadata.
    *
    * @param bool $isJSON
    * @param bool $reload
    *
    * @return array|string
    */
    public function getAll(bool $isJSON = false, bool $reload = false)
    {
        if ($reload) {
            $this->init($reload);
        }

        if ($isJSON) {
            return Json::encode($this->data);
        }

        return $this->data;
    }

    private function objInit(bool $reload = false): void
    {
        if (!$this->useCache) {
            $reload = true;
        }

        if ($this->dataCache->has($this->objCacheKey) && !$reload) {
            $this->objData = $this->dataCache->get($this->objCacheKey);

            return;
        }

        $this->objData = $this->resourceReader->read('metadata', ResourceReaderParams::create());

        $this->objData = $this->addAdditionalFieldsObj($this->objData);

        if ($this->useCache) {
            $this->dataCache->store($this->objCacheKey, $this->objData);
        }
    }

    private function getObjData(bool $reload = false): stdClass
    {
        if (!isset($this->objData) || $reload) {
            $this->objInit($reload);
        }

        return $this->objData;
    }

    /**
    * Get metadata with stdClass items.
    *
    * @param string|array $key
    * @param mixed $default
    *
    * @return mixed
    */
    public function getObjects($key = null, $default = null)
    {
        $objData = $this->getObjData();

        return Util::getValueByKey($objData, $key, $default);
    }

    public function getAllObjects($isJSON = false, $reload = false)
    {
        $objData = $this->getObjData($reload);

        if ($isJSON) {
            return Json::encode($objData);
        }

        return $objData;
    }

    public function getAllForFrontend(): stdClass
    {
        $data = $this->getAllObjects();

        $frontendHiddenPathList = $this->get(['app', 'metadata', 'frontendHiddenPathList'], []);

        foreach ($frontendHiddenPathList as $row) {
            $this->removeDataByPath($row, $data);
        }

        return $data;
    }

    private function removeDataByPath($row, &$data): void
    {
        $p = &$data;
        $path = [&$p];

        foreach ($row as $i => $item) {
            if (is_array($item)) {
                break;
            }

            if ($item === '__ANY__') {
                foreach (get_object_vars($p) as &$v) {
                    $this->removeDataByPath(
                        array_slice($row, $i + 1), $v
                    );
                }

                return;
            }

            if (!property_exists($p, $item)) {
                break;
            }

            if ($i == count($row) - 1) {
                unset($p->$item);

                $o = &$p;

                for ($j = $i - 1; $j > 0; $j--) {
                    if (is_object($o) && !count(get_object_vars($o))) {
                        $o = &$path[$j];
                        $k = $row[$j];

                        unset($o->$k);
                    }
                    else {
                        break;
                    }
                }
            }
            else {
                $p = &$p->$item;
                $path[] = &$p;
            }
        }
    }

    /**
     * @param stdClass $data
     */
    private function addAdditionalFieldsObj($data)
    {
        if (!isset($data->entityDefs)) {
            return $data;
        }

        $fieldDefinitionList = Util::objectToArray($data->fields);

        foreach (get_object_vars($data->entityDefs) as $entityType => $entityDefsItem) {
            if (isset($data->entityDefs->$entityType->collection)) {
                /** @var stdClass $collectionItem */
                $collectionItem = $data->entityDefs->$entityType->collection;

                if (isset($collectionItem->orderBy)) {
                    $collectionItem->sortBy = $collectionItem->orderBy;
                }
                else if (isset($collectionItem->sortBy)) {
                    $collectionItem->orderBy = $collectionItem->sortBy;
                }

                if (isset($collectionItem->order)) {
                     $collectionItem->asc = $collectionItem->order === 'asc' ? true : false;
                }
                else if (isset($collectionItem->asc)) {
                    $collectionItem->order = $collectionItem->asc === true ? 'asc' : 'desc';
                }
            }

            if (!isset($entityDefsItem->fields)) {
                continue;
            }

            foreach (get_object_vars($entityDefsItem->fields) as $field => $fieldDefsItem) {
                $additionalFields = $this->getMetadataHelper()->getAdditionalFieldList(
                    $field,
                    Util::objectToArray($fieldDefsItem), $fieldDefinitionList
                );

                if (!$additionalFields) {
                    continue;
                }

                foreach ($additionalFields as $subFieldName => $subFieldParams) {
                    if (isset($entityDefsItem->fields->$subFieldName)) {
                        $data->entityDefs->$entityType->fields->$subFieldName = DataUtil::merge(
                            Util::arrayToObject($subFieldParams), $entityDefsItem->fields->$subFieldName
                        );
                    }
                    else {
                        $data->entityDefs->$entityType->fields->$subFieldName =
                            Util::arrayToObject($subFieldParams);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get metadata definition in custom directory.
     *
     * @param string $key1
     * @param string $key2
     * @param mixed $default
     *
     * @return stdClass
     */
    public function getCustom($key1, $key2, $default = null)
    {
        $filePath = $this->customPath . "/{$key1}/{$key2}.json";

        $fileContent = $this->fileManager->getContents($filePath);

        if ($fileContent) {
            return Json::decode($fileContent);
        }

        return $default;
    }

    /**
     * Set and save metadata in custom directory.
     * The data is not merging with existing data. Use getCustom() to get existing data.
     *
     * @param string $key1
     * @param string $key2
     * @param array|stdClass $data
     */
    public function saveCustom(string $key1, string $key2, $data): void
    {
        if (is_object($data)) {
            /** @phpstan-ignore-next-line */
            foreach ($data as $key => $item) {
                if ($item == new stdClass()) {
                    unset($data->$key);
                }
            }
        }

        $filePath = $this->customPath . "/{$key1}/{$key2}.json";

        $changedData = Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->fileManager->putContents($filePath, $changedData);

        $this->init(true);
    }

    /**
     * Set Metadata data.
     */
    public function set(string $key1, string $key2, $data): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                if (is_array($item) && empty($item)) {
                    unset($data[$key]);
                }
            }
        }

        $newData = [
            $key1 => [
                $key2 => $data,
            ],
        ];

        $this->changedData = Util::merge($this->changedData, $newData);
        $this->data = Util::merge($this->getData(), $newData);

        $this->undelete($key1, $key2, $data);
    }

    /**
     * Unset some fields and other stuff in metadata.
     *
     * @param array|string $unsets Example: `fields.name`.
     */
    public function delete(string $key1, string $key2, $unsets = null): void
    {
        if (!is_array($unsets)) {
            $unsets = (array) $unsets;
        }

        switch ($key1) {
            case 'entityDefs':
                //unset related additional fields, e.g. a field with "address" type
                $fieldDefinitionList = $this->get('fields');

                $unsetList = $unsets;

                foreach ($unsetList as $unsetItem) {
                    if (preg_match('/fields\.([^\.]+)/', $unsetItem, $matches) && isset($matches[1])) {
                        $fieldName = $matches[1];
                        $fieldPath = [$key1, $key2, 'fields', $fieldName];

                        $additionalFields = $this->getMetadataHelper()->getAdditionalFieldList(
                            $fieldName,
                            $this->get($fieldPath, []),
                            $fieldDefinitionList
                        );

                        if (is_array($additionalFields)) {
                            foreach ($additionalFields as $additionalFieldName => $additionalFieldParams) {
                                $unsets[] = 'fields.' . $additionalFieldName;
                            }
                        }
                    }
                }
                break;
        }

        $normalizedData = [
            '__APPEND__',
        ];

        $metadataUnsetData = [];

        foreach ($unsets as $unsetItem) {
            $normalizedData[] = $unsetItem;
            $metadataUnsetData[] = implode('.', [$key1, $key2, $unsetItem]);
        }

        $unsetData = [
            $key1 => [
                $key2 => $normalizedData
            ]
        ];

        $this->deletedData = Util::merge($this->deletedData, $unsetData);
        $this->deletedData = Util::unsetInArrayByValue('__APPEND__', $this->deletedData, true);

        $this->data = Util::unsetInArray($this->getData(), $metadataUnsetData, true);
    }

    private function undelete($key1, $key2, $data): void
    {
        if (isset($this->deletedData[$key1][$key2])) {
            foreach ($this->deletedData[$key1][$key2] as $unsetIndex => $unsetItem) {
                $value = Util::getValueByKey($data, $unsetItem);

                if (isset($value)) {
                    unset($this->deletedData[$key1][$key2][$unsetIndex]);
                }
            }
        }
    }

    /**
     * Clear not saved changes.
     */
    public function clearChanges(): void
    {
        $this->changedData = [];
        $this->deletedData = [];

        $this->init(true);
    }

    /**
     * Save changes.
     */
    public function save(): bool
    {
        $path = $this->customPath;

        $result = true;

        if (!empty($this->changedData)) {
            foreach ($this->changedData as $key1 => $keyData) {
                foreach ($keyData as $key2 => $data) {
                    if (empty($data)) {
                        continue;
                    }

                    $filePath = $path . "/{$key1}/{$key2}.json";

                    $result &= $this->fileManager->mergeJsonContents($filePath, $data);
                }
            }
        }

        if (!empty($this->deletedData)) {
            foreach ($this->deletedData as $key1 => $keyData) {
                foreach ($keyData as $key2 => $unsetData) {
                    if (empty($unsetData)) {
                        continue;
                    }

                    $filePath = $path . "/{$key1}/{$key2}.json";

                    $rowResult = $this->fileManager->unsetJsonContents($filePath, $unsetData);

                    if (!$rowResult) {
                        throw new Error(
                            "Metadata items {$key1}.{$key2} can be deleted for custom code only."
                        );
                    }

                    $result &= $rowResult;
                }
            }
        }

        if (!$result) {
            throw new Error("Error while saving metadata. See log file for details.");
        }

        $this->clearChanges();

        return (bool) $result;
    }

    /**
     * Get a module list.
     *
     * @return string[]
     */
    public function getModuleList(): array
    {
        return $this->module->getOrderedList();
    }

    /**
     * Get a module name a scope belongs to.
     */
    public function getScopeModuleName(string $scopeName): ?string
    {
        return $this->get(['scopes', $scopeName, 'module']);
    }

    private function clearVars(): void
    {
        $this->data = null;
    }
}
