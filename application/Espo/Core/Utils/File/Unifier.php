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

namespace Espo\Core\Utils\File;

use Espo\Core\{
    Utils\File\Manager as FileManager,
    Utils\Module,
    Utils\Util,
    Utils\DataUtil,
    Utils\Json,
    Utils\Resource\PathProvider,
};

use JsonException;

class Unifier
{
    private $fileManager;

    private $module;

    private $pathProvider;

    protected $useObjects = false;

    private $unsetFileName = 'unset.json';

    private $moduleList = null;

    public function __construct(FileManager $fileManager, Module $module, PathProvider $pathProvider)
    {
        $this->fileManager = $fileManager;
        $this->module = $module;
        $this->pathProvider = $pathProvider;
    }

    /**
     * Merge data of resource files.
     *
     * @return array|object
     */
    public function unify(string $path, bool $noCustom = false)
    {
        // @todo Remove variable.
        $recursively = true;

        $data = $this->unifySingle($this->pathProvider->getCore() . $path, $recursively);

        foreach ($this->getModuleList() as $moduleName) {
            $filePath = $this->pathProvider->getModule($moduleName) . $path;

            if ($this->useObjects) {
                $data = DataUtil::merge(
                    $data,
                    $this->unifySingle($filePath, $recursively)
                );

                continue;
            }

            $data = Util::merge(
                $data,
                $this->unifySingle($filePath, $recursively)
            );
        }

        if ($noCustom) {
            return $data;
        }

        $customFilePath = $this->pathProvider->getCustom() . $path;

        if ($this->useObjects) {
            return DataUtil::merge(
                $data,
                $this->unifySingle($customFilePath, $recursively)
            );
        }

        return Util::merge(
            $data,
            $this->unifySingle($customFilePath, $recursively)
        );
    }

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

        $dirName = $this->fileManager->getDirName($dirPath, false);

        foreach ($fileList as $dirName => $item) {
            if (is_array($item)) {
                // Only a first level of a sub-directory.
                $itemValue = $this->unifySingle(
                    Util::concatPath($dirPath, $dirName),
                    false
                );

                if ($this->useObjects) {
                    $data->$dirName = $itemValue;

                    continue;
                }

                $data[$dirName] = $itemValue;

                continue;
            }

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
                $data->$name = $itemValue;

                continue;
            }

            $data[$name] = $itemValue;
        }

        if ($this->useObjects) {
            return DataUtil::unsetByKey($data, $unsets);
        }

        return Util::unsetInArray($data, $unsets);
    }

    private function getContents(string $path)
    {
        $fileContent = $this->fileManager->getContents($path);

        try {
            return Json::decode($fileContent, !$this->useObjects);
        }
        catch (JsonException $e) {
            throw new JsonException(
                "JSON syntax error in '{$path}'."
            );
        }
    }

    /**
     * @return string[]
     */
    private function getModuleList(): array
    {
        return $this->module->getOrderedList();
    }
}
