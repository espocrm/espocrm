<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Utils\File;

use Espo\Core\Utils\DataUtil;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Module;
use Espo\Core\Utils\Resource\PathProvider;
use Espo\Core\Utils\Util;

use JsonException;
use LogicException;
use stdClass;

class Unifier
{
    protected bool $useObjects = false;
    private string $unsetFileName = 'unset.json';
    private const APPEND_VALUE = '__APPEND__';
    private const ANY_KEY = '__ANY__';

    public function __construct(
        private FileManager $fileManager,
        private Module $module,
        private PathProvider $pathProvider
    ) {}

    /**
     * Merge data of resource files.
     *
     * @param array<int, string[]> $forceAppendPathList
     * @return array<string, mixed>|stdClass
     */
    public function unify(string $path, bool $noCustom = false, array $forceAppendPathList = [])
    {
        if ($this->useObjects) {
            return $this->unifyObject($path, $noCustom, $forceAppendPathList);
        }

        return $this->unifyArray($path, $noCustom);
    }

    /**
     * @return array<string, mixed>
     */
    private function unifyArray(string $path, bool $noCustom = false)
    {
        /** @var array<string, mixed> $data */
        $data = $this->unifySingle($this->pathProvider->getCore() . $path, true);

        foreach ($this->getModuleList() as $moduleName) {
            $filePath = $this->pathProvider->getModule($moduleName) . $path;

            /** @var array<string, mixed> $newData */
            $newData = $this->unifySingle($filePath, true);

            /** @var array<string, mixed> $data */
            $data = Util::merge($data, $newData);
        }

        if ($noCustom) {
            return $data;
        }

        $customFilePath = $this->pathProvider->getCustom() . $path;

        /** @var array<string, mixed> $newData */
        $newData = $this->unifySingle($customFilePath, true);

        /** @var array<string, mixed> */
        return Util::merge($data, $newData);
    }

    /**
     * @param array<int, string[]> $forceAppendPathList
     * @return stdClass
     */
    private function unifyObject(string $path, bool $noCustom = false, array $forceAppendPathList = [])
    {
        /** @var stdClass $data */
        $data = $this->unifySingle($this->pathProvider->getCore() . $path, true);

        foreach ($this->getModuleList() as $moduleName) {
            $filePath = $this->pathProvider->getModule($moduleName) . $path;

            /** @var stdClass $itemData */
            $itemData = $this->unifySingle($filePath, true);

            $this->prepareItemDataObject($itemData, $forceAppendPathList);

            /** @var stdClass $data */
            $data = DataUtil::merge($data, $itemData);
        }

        if ($noCustom) {
            return $data;
        }

        $customFilePath = $this->pathProvider->getCustom() . $path;

        /** @var stdClass $itemData */
        $itemData = $this->unifySingle($customFilePath, true);

        $this->prepareItemDataObject($itemData, $forceAppendPathList);

        /** @var stdClass */
        return DataUtil::merge($data, $itemData);
    }

    /**
     * @return array<string, mixed>|stdClass
     */
    private function unifySingle(string $dirPath, bool $recursively)
    {
        $data = [];
        $unsets = [];

        if ($this->useObjects) {
            $data = (object) [];
        }

        if (empty($dirPath) || !$this->fileManager->exists($dirPath)) {
            return $data;
        }

        $fileList = $this->fileManager->getFileList($dirPath, $recursively, '\.json$');

        //$dirName = $this->fileManager->getDirName($dirPath, false);

        foreach ($fileList as $dirName => $item) {
            if (is_array($item)) {
                /** @var string $dirName */
                // Only a first level of a subdirectory.
                $itemValue = $this->unifySingle(
                    Util::concatPath($dirPath, $dirName),
                    false
                );

                if ($this->useObjects) {
                    /** @var stdClass $data */

                    $data->$dirName = $itemValue;

                    continue;
                }

                /** @var array<string, mixed> $data */

                $data[$dirName] = $itemValue;

                continue;
            }

            /** @var string $item */

            $fileName = $item;

            if ($fileName === $this->unsetFileName) {
                $fileContent = $this->fileManager->getContents($dirPath . '/' . $fileName);

                $unsets = Json::decode($fileContent, true);

                continue;
            }

            $itemValue = $this->getContents($dirPath . '/' . $fileName);

            if (empty($itemValue)) {
                continue;
            }

            $name = $this->fileManager->getFileName($fileName, '.json');

            if ($this->useObjects) {
                /** @var stdClass $data */

                $data->$name = $itemValue;

                continue;
            }

            /** @var array<string, mixed> $data */

            $data[$name] = $itemValue;
        }

        if ($this->useObjects) {
            /** @var stdClass $data */

            /** @var stdClass */
            return DataUtil::unsetByKey($data, $unsets);
        }

        /** @var array<string, mixed> $data */

        /** @var array<string, mixed> */
        return Util::unsetInArray($data, $unsets);
    }

    /**
     * @return stdClass|array<string, mixed>
     * @throws JsonException
     */
    private function getContents(string $path)
    {
        $fileContent = $this->fileManager->getContents($path);

        try {
            return Json::decode($fileContent, !$this->useObjects);
        } catch (JsonException) {
            throw new JsonException("JSON syntax error in '$path'.");
        }
    }

    /**
     * @return string[]
     */
    private function getModuleList(): array
    {
        return $this->module->getOrderedList();
    }

    /**
     * @param array<int, string[]> $forceAppendPathList
     */
    private function prepareItemDataObject(stdClass $data, array $forceAppendPathList): void
    {
        foreach ($forceAppendPathList as $path) {
            $this->addAppendToData($data, $path);
        }
    }

    /**
     * @param string[] $path
     */
    private function addAppendToData(stdClass $data, array $path): void
    {
        if (count($path) === 0) {
            return;
        }

        $nextPath = array_slice($path, 1);

        $key = $path[0];

        if ($key === self::ANY_KEY) {
            foreach (array_keys(get_object_vars($data)) as $itemKey) {
                $this->addAppendToDataItem($data, $itemKey, $nextPath);
            }

            return;
        }

        $this->addAppendToDataItem($data, $key, $nextPath);
    }

    /**
     * @param string[] $path
     */
    private function addAppendToDataItem(stdClass $data, string $key, array $path): void
    {
        $item = $data->$key ?? null;

        if (count($path) === 0) {
            if ($item === null) {
                $item = [];
            }

            if (!is_array($item)) {
                throw new LogicException("Expected array in metadata, but non-array is set.");
            }

            if (($item[0] ?? null) === self::APPEND_VALUE) {
                return;
            }

            $data->$key = array_merge([self::APPEND_VALUE], $item);

            return;
        }

        if (!$item instanceof stdClass) {
            return;
        }

        $this->addAppendToData($item, $path);
    }
}
