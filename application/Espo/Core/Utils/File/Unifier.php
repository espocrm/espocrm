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
    Utils\Metadata,
    Utils\Util,
    Utils\DataUtil,
    Utils\Json,
};

class Unifier
{
    private $fileManager;

    private $metadata;

    protected $useObjects;

    protected $unsetFileName = 'unset.json';

    public function __construct(
        FileManager $fileManager,
        Metadata $metadata = null,
        bool $useObjects = false
    ) {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->useObjects = $useObjects;
    }

    /**
     * Unite file content to the file.
     *
     * @param string $name
     * @param array $paths
     * @param boolean $recursively Note: only for first level of sub directory,
     * other levels of sub directories will be ignored.
     *
     * @return array|object
     */
    public function unify(string $name, array $paths, bool $recursively = false)
    {
        $content = $this->unifySingle($paths['corePath'], $name, $recursively);

        if (!empty($paths['modulePath'])) {
            $customDir = strstr($paths['modulePath'], '{*}', true);

            $moduleList = isset($this->metadata) ?
                $this->metadata->getModuleList() :
                $this->fileManager->getFileList($customDir, false, '', false);

            foreach ($moduleList as $moduleName) {
                $curPath = str_replace('{*}', $moduleName, $paths['modulePath']);

                if ($this->useObjects) {
                    $content = DataUtil::merge(
                        $content,
                        $this->unifySingle($curPath, $name, $recursively, $moduleName)
                    );
                }
                else {
                    $content = Util::merge(
                        $content,
                        $this->unifySingle($curPath, $name, $recursively, $moduleName)
                    );
                }
            }
        }

        if (!empty($paths['customPath'])) {
            if ($this->useObjects) {
                $content = DataUtil::merge(
                    $content,
                    $this->unifySingle($paths['customPath'], $name, $recursively)
                );
            }
            else {
                $content = Util::merge(
                    $content,
                    $this->unifySingle($paths['customPath'], $name, $recursively)
                );
            }
        }

        return $content;
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
     * @return string Content of the files.
     */
    protected function unifySingle($dirPath, $type, $recursively = false, $moduleName = '')
    {
        $content = [];
        $unsets = [];

        if ($this->useObjects) {
            $content = (object) [];
            $unsets = (object) [];
        }

        if (empty($dirPath) || !file_exists($dirPath)) {
            return $content;
        }

        $fileList = $this->fileManager->getFileList($dirPath, $recursively, '\.json$');

        $dirName = $this->fileManager->getDirName($dirPath, false);

        foreach ($fileList as $dirName => $fileName) {
            if (is_array($fileName)) { /*only first level of a sub directory*/
                if ($this->useObjects) {
                    $content->$dirName = $this->unifySingle(
                        Util::concatPath($dirPath, $dirName),
                        $type,
                        false,
                        $moduleName
                    );
                }
                else {
                    $content[$dirName] = $this->unifySingle(
                        Util::concatPath($dirPath, $dirName),
                        $type,
                        false,
                        $moduleName
                    );
                }

            } else {
                if ($fileName === $this->unsetFileName) {
                    $fileContent = $this->fileManager->getContents(array($dirPath, $fileName));

                    if ($this->useObjects) {
                        $unsets = Json::decode($fileContent);
                    }
                    else {
                        $unsets = Json::getArrayData($fileContent);
                    }

                    continue;
                }

                $mergedValues = $this->unifyGetContents([$dirPath, $fileName]);

                if (!empty($mergedValues)) {
                    $name = $this->fileManager->getFileName($fileName, '.json');

                    if ($this->useObjects) {
                        $content->$name = $mergedValues;
                    }
                    else {
                        $content[$name] = $mergedValues;
                    }
                }
            }
        }

        if ($this->useObjects) {
            $content = DataUtil::unsetByKey($content, $unsets);
        }
        else {
            $content = Util::unsetInArray($content, $unsets);
        }

        return $content;
    }

    /**
     * Get content from files for unite files.
     */
    protected function unifyGetContents(array $paths)
    {
        $fileContent = $this->fileManager->getContents($paths);

        return Json::decode($fileContent, !$this->useObjects);
    }
}
