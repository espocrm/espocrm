<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Utils;

use Espo\Core\Binding\Attributes\Qualify;
use Espo\Core\Utils\Cache\DataCacheAccess;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata\Builder;
use Espo\Core\Utils\Metadata\BuilderHelper;
use stdClass;
use LogicException;
use RuntimeException;

/**
 * Application metadata.
 */
class Metadata
{
    private string $cacheKey = 'metadata';
    private string $objCacheKey = 'objMetadata';
    private string $customPath = 'custom/Espo/Custom/Resources/metadata';

    /** @var array<string, array<string, mixed>>  */
    private $deletedData = [];

    /** @var array<string, array<string, mixed>> */
    private $changedData = [];

    /**
     * @param DataCacheAccess<array<string, mixed>> $data
     * @param DataCacheAccess<stdClass> $objectData
     */
    public function __construct(
        private FileManager $fileManager,
        private Module $module,
        private Builder $builder,
        private BuilderHelper $builderHelper,
        #[Qualify(DataCache::QUALIFIER_SYSTEM)]
        private DataCacheAccess $data,
        #[Qualify(DataCache::QUALIFIER_SYSTEM)]
        private DataCacheAccess $objectData,
    ) {

        $this->objectData->init(
            key: $this->objCacheKey,
            loader: fn () => $this->builder->build(),
        );

        $this->data->init(
            key: $this->cacheKey,
            loader: fn () => $this->getObjectConvertedToAssoc(),
        );
    }

    /**
     * Init metadata.
     *
     * @internal
     */
    public function init(): void
    {
        $this->reloadObject();
        $this->reload();
    }

    /**
     * Get metadata array.
     *
     * @return array<string, mixed>
     */
    private function getData(): array
    {
        return $this->data->get();
    }

    /**
    * Get metadata by key.
    *
    * @param string|string[] $key
    * @param mixed $default
    * @return mixed
    */
    public function get($key = null, $default = null)
    {
        $data = $this->data->get();

        return Util::getValueByKey($data, $key, $default);
    }

    /**
    * Get metadata with stdClass items.
    *
    * @param string|string[] $key
    * @param mixed $default
    * @return mixed
    */
    public function getObjects($key = null, $default = null)
    {
        return Util::getValueByKey($this->getAll(), $key, $default);
    }

    /**
     * Important. Do not modify without cloning.
     */
    public function getAll(): stdClass
    {
        return $this->objectData->get();
    }

    /**
     * Get metadata definition in custom directory.
     *
     * @param mixed $default
     * @return mixed
     */
    public function getCustom(string $key1, string $key2, $default = null)
    {
        $filePath = "$this->customPath/$key1/$key2.json";

        if (!$this->fileManager->isFile($filePath)) {
            return $default;
        }

        $fileContent = $this->fileManager->getContents($filePath);

        return Json::decode($fileContent);
    }

    /**
     * Set and save metadata in custom directory.
     * The data is not merging with existing data. Use getCustom() to get existing data.
     *
     * @param array<string, mixed>|stdClass $data
     */
    public function saveCustom(string $key1, string $key2, $data): void
    {
        if (is_object($data)) {
            foreach (get_object_vars($data) as $key => $item) {
                if (
                    $item instanceof stdClass &&
                    count(get_object_vars($data)) === 0
                ) {
                    unset($data->$key);
                }
            }
        }

        $filePath = "$this->customPath/$key1/$key2.json";

        $changedData = Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->fileManager->putContents($filePath, $changedData);

        $this->init();
    }

    /**
     * Set metadata. Will be merged with the current data.
     *
     * @param array<string, mixed>|scalar|null $data
     */
    public function set(string $key1, string $key2, $data): void
    {
        $this->setInternal($key1, $key2, $data);
    }

    /**
     * Set a first-level param. Allows setting empty arrays.
     *
     * @since 8.0.6
     */
    public function setParam(string $key1, string $key2, string $param, mixed $value): void
    {
        $this->setInternal($key1, $key2, [$param => $value], true);
    }

    /**
     * @param array<string, mixed>|scalar|null $data
     */
    private function setInternal(string $key1, string $key2, $data, bool $allowEmptyArray = false): void
    {
        if (!$allowEmptyArray && is_array($data)) {
            foreach ($data as $key => $item) {
                if (is_array($item) && empty($item)) {
                    // @todo Revise.
                    unset($data[$key]);
                }
            }
        }

        $newData = [
            $key1 => [
                $key2 => $data,
            ],
        ];

        /** @var array<string, array<string, mixed>> $mergedChangedData */
        $mergedChangedData = Util::merge($this->changedData, $newData);

        /** @var array<string, mixed> $mergedData */
        $mergedData = Util::merge($this->getData(), $newData);

        $this->changedData = $mergedChangedData;

        $this->data->set($mergedData);

        if (is_array($data)) {
            $this->undelete($key1, $key2, $data);
        }
    }

    /**
     * Unset some fields and other stuff in metadata.
     *
     * @param string[]|string $unsets Example: `fields.name`.
     */
    public function delete(string $key1, string $key2, $unsets = null): void
    {
        if (!is_array($unsets)) {
            $unsets = (array) $unsets;
        }

        switch ($key1) {
            case 'entityDefs':
                // Unset related additional fields, e.g. a field with an 'address' type.
                $defs = $this->get('fields');

                $unsetList = $unsets;

                foreach ($unsetList as $unsetItem) {
                    if (!preg_match('/fields\.([^.]+)/', $unsetItem, $matches)) {
                        continue;
                    }

                    $field = $matches[1];
                    $fieldPath = [$key1, $key2, 'fields', $field];

                    // @todo Revise the need. Additional fields are supposed to exist only in the build?
                    $additionalFields = $this->builderHelper->getAdditionalFields(
                        field: $field,
                        params: $this->get($fieldPath, []),
                        defs: $defs,
                    );

                    if (is_array($additionalFields)) {
                        foreach ($additionalFields as $additionalFieldName => $additionalFieldParams) {
                            $unsets[] = 'fields.' . $additionalFieldName;
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

        /** @var array<string, array<string, mixed>> $mergedDeletedData */
        $mergedDeletedData = Util::merge($this->deletedData, $unsetData);
        $this->deletedData = $mergedDeletedData;

        /** @var array<string, array<string, mixed>> $unsetDeletedData */
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $unsetDeletedData = Util::unsetInArrayByValue('__APPEND__', $this->deletedData, true);
        $this->deletedData = $unsetDeletedData;

        /** @var array<string, mixed> $data */
        $data = Util::unsetInArray($this->getData(), $metadataUnsetData, true);

        $this->data->set($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function undelete(string $key1, string $key2, $data): void
    {
        if (!isset($this->deletedData[$key1][$key2])) {
            return;
        }

        foreach ($this->deletedData[$key1][$key2] as $unsetIndex => $unsetItem) {
            $value = Util::getValueByKey($data, $unsetItem);

            if (isset($value)) {
                unset($this->deletedData[$key1][$key2][$unsetIndex]);
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

        $this->init();
    }

    /**
     * Save changes.
     */
    public function save(): bool
    {
        $result = true;
        $problemPath = null;

        if ($this->changedData) {
            foreach ($this->changedData as $key1 => $keyData) {
                foreach ($keyData as $key2 => $data) {
                    if (empty($data)) {
                        continue;
                    }

                    $filePath = "$this->customPath/$key1/$key2.json";

                    $itemResult = $this->fileManager->mergeJsonContents($filePath, $data);

                    $result &= $itemResult;

                    if (!$itemResult && !$problemPath) {
                        $problemPath = $filePath;
                    }
                }
            }
        }

        if ($this->deletedData) {
            foreach ($this->deletedData as $key1 => $keyData) {
                foreach ($keyData as $key2 => $unsetData) {
                    if (empty($unsetData)) {
                        continue;
                    }

                    $filePath = "$this->customPath/$key1/$key2.json";

                    $rowResult = $this->fileManager->unsetJsonContents($filePath, $unsetData);

                    if (!$rowResult) {
                        throw new LogicException("Metadata items $key1.$key2 can be deleted for custom code only.");
                    }
                }
            }
        }

        if (!$result) {
            $message = "Error while saving metadata. Usually it's a file permission problem. " .
                "First problem path: `$problemPath`.";

            throw new RuntimeException($message);
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

    private function reloadObject(): void
    {
        $data = $this->builder->build();

        $this->objectData->set($data);
        $this->objectData->store();
    }

    private function reload(): void
    {
        $data = $this->getObjectConvertedToAssoc();

        $this->data->set($data);
        $this->data->store();
    }

    /**
     * @return array<string, mixed>
     */
    private function getObjectConvertedToAssoc(): array
    {
        $data = $this->objectData->get();

        return Util::objectToArray($data);
    }
}
