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
};

use JsonException;

class Unifier
{
    private $fileManager;

    private $module;

    protected $useObjects = false;

    private $unsetFileName = 'unset.json';

    private $moduleList = null;

    public function __construct(FileManager $fileManager, Module $module)
    {
        $this->fileManager = $fileManager;
        $this->module = $module;
    }

    /**
     * Unite file content to the file.
     *
     * @param array $paths
     * @param boolean $recursively Note: only for first level of sub directory,
     * other levels of sub directories will be ignored.
     *
     * @return array|object
     */
    public function unify(array $paths, bool $recursively = true)
    {
        $data = $this->unifySingle($paths['corePath'], $recursively);

        if (!empty($paths['modulePath'])) {
            foreach ($this->getModuleList() as $moduleName) {
                $curPath = str_replace('{*}', $moduleName, $paths['modulePath']);

                if ($this->useObjects) {
                    $data = DataUtil::merge(
                        $data,
                        $this->unifySingle($curPath, $recursively, $moduleName)
                    );
                }
                else {
                    $data = Util::merge(
                        $data,
                        $this->unifySingle($curPath, $recursively, $moduleName)
                    );
                }
            }
        }

        if (!empty($paths['customPath'])) {
            if ($this->useObjects) {
                $data = DataUtil::merge(
                    $data,
                    $this->unifySingle($paths['customPath'], $recursively)
                );
            }
            else {
                $data = Util::merge(
                    $data,
                    $this->unifySingle($paths['customPath'], $recursively)
                );
            }
        }

        return $data;
    }

    /**
     * Unite file content to the file for one directory.
     *
     * @param string $dirPath
     * @param string $type Name of type array("metadata", "layouts"), ex. $this->name.
     * @param bool $recursively Note: only for first level of sub directory,
     * other levels of sub directories will be ignored.
     * @param string $moduleName Name of module if exists.
     *
     * @return array|object Content of the files.
     */
    private function unifySingle(string $dirPath, bool $recursively, string $moduleName = '')
    {
        $data = [];
        $unsets = [];

        if ($this->useObjects) {
            $data = (object) [];
        }

        if (empty($dirPath) || !file_exists($dirPath)) {
            return $data;
        }

        $fileList = $this->fileManager->getFileList($dirPath, $recursively, '\.json$');

        $dirName = $this->fileManager->getDirName($dirPath, false);

        foreach ($fileList as $dirName => $fileName) {
            if (is_array($fileName)) { // only first level of a sub-directory
                $itemValue = $this->unifySingle(
                    Util::concatPath($dirPath, $dirName),
                    false,
                    $moduleName
                );

                if ($this->useObjects) {
                    $data->$dirName = $itemValue;
                }
                else {
                    $data[$dirName] = $itemValue;
                }

                continue;
            }

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
            }
            else {
                $data[$name] = $itemValue;
            }
        }

        if ($this->useObjects) {
            $data = DataUtil::unsetByKey($data, $unsets);
        }
        else {
            $data = Util::unsetInArray($data, $unsets);
        }

        return $data;
    }

    /**
     * Get content from files for unite files.
     */
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
        if (!isset($this->moduleList)) {
           $this->moduleList = $this->module->getOrderedList();
        }

        return $this->moduleList;
    }
}
