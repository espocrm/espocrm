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

namespace Espo\Core\Utils\File;

use Espo\Core\Utils;

class Unifier
{
    private $fileManager;

    private $metadata;

    protected $useObjects;

    protected $unsetFileName = 'unset.json';

    protected $pathToDefaults = 'application/Espo/Core/defaults';

    public function __construct(\Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Metadata $metadata = null, $useObjects = false)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->useObjects = $useObjects;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Unite file content to the file
     *
     * @param  string  $name
     * @param  array  $paths
     * @param  boolean $recursively Note: only for first level of sub directory, other levels of sub directories will be ignored
     *
     * @return array
     */
    public function unify($name, $paths, $recursively = false)
    {
        $content = $this->unifySingle($paths['corePath'], $name, $recursively);

        if (!empty($paths['modulePath'])) {
            $customDir = strstr($paths['modulePath'], '{*}', true);

            $moduleList = isset($this->metadata) ? $this->getMetadata()->getModuleList() : $this->getFileManager()->getFileList($customDir, false, '', false);

            foreach ($moduleList as $moduleName) {
                $curPath = str_replace('{*}', $moduleName, $paths['modulePath']);

                if ($this->useObjects) {
                    $content = Utils\DataUtil::merge($content, $this->unifySingle($curPath, $name, $recursively, $moduleName));
                } else {
                    $content = Utils\Util::merge($content, $this->unifySingle($curPath, $name, $recursively, $moduleName));
                }
            }
        }

        if (!empty($paths['customPath'])) {
            if ($this->useObjects) {
                $content = Utils\DataUtil::merge($content, $this->unifySingle($paths['customPath'], $name, $recursively));
            } else {
                $content = Utils\Util::merge($content, $this->unifySingle($paths['customPath'], $name, $recursively));
            }
        }

        return $content;
    }

    /**
     * Unite file content to the file for one directory [NOW ONLY FOR METADATA, NEED TO CHECK FOR LAYOUTS AND OTHERS]
     *
     * @param string $dirPath
     * @param string $type - name of type array("metadata", "layouts"), ex. $this->name
     * @param bool $recursively - Note: only for first level of sub directory, other levels of sub directories will be ignored
     * @param string $moduleName - name of module if exists
     *
     * @return string - content of the files
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

        $fileList = $this->getFileManager()->getFileList($dirPath, $recursively, '\.json$');

        $dirName = $this->getFileManager()->getDirName($dirPath, false);
        $defaultValues = $this->loadDefaultValues($dirName, $type);

        foreach ($fileList as $dirName => $fileName) {
            if (is_array($fileName)) { /*only first level of a sub directory*/
                if ($this->useObjects) {
                    $content->$dirName = $this->unifySingle(Utils\Util::concatPath($dirPath, $dirName), $type, false, $moduleName);
                } else {
                    $content[$dirName] = $this->unifySingle(Utils\Util::concatPath($dirPath, $dirName), $type, false, $moduleName);
                }

            } else {
                if ($fileName === $this->unsetFileName) {
                    $fileContent = $this->getFileManager()->getContents(array($dirPath, $fileName));
                    if ($this->useObjects) {
                        $unsets = Utils\Json::decode($fileContent);
                    } else {
                        $unsets = Utils\Json::getArrayData($fileContent);
                    }
                    continue;
                }

                $mergedValues = $this->unifyGetContents(array($dirPath, $fileName), $defaultValues);

                if (!empty($mergedValues)) {
                    $name = $this->getFileManager()->getFileName($fileName, '.json');

                    if ($this->useObjects) {
                        $content->$name = $mergedValues;
                    } else {
                        $content[$name] = $mergedValues;
                    }

                }
            }
        }

        if ($this->useObjects) {
            $content = Utils\DataUtil::unsetByKey($content, $unsets);
        } else {
            $content = Utils\Util::unsetInArray($content, $unsets);
        }

        return $content;
    }

    /**
     * Helpful method for get content from files for unite Files
     *
     * @param string | array $paths
     * @param string | array() $defaults - It can be a string like ["metadata","layouts"] OR an array with default values
     *
     * @return array
     */
    protected function unifyGetContents($paths, $defaults)
    {
        $fileContent = $this->getFileManager()->getContents($paths);

        if ($this->useObjects) {
            $decoded = Utils\Json::decode($fileContent);
        } else {
            $decoded = Utils\Json::getArrayData($fileContent, null);
        }

        if (!isset($decoded)) {
            $GLOBALS['log']->emergency('Syntax error in '.Utils\Util::concatPath($paths));
            if ($this->useObjects) {
                return (object) [];
            } else {
                return array();
            }
        }

        return $decoded;
    }

    /**
     * Load default values for selected type [metadata, layouts]
     *
     * @param string $name
     * @param string $type - [metadata, layouts]
     *
     * @return array
     */
    protected function loadDefaultValues($name, $type = 'metadata')
    {
        $defaultValue = $this->getFileManager()->getContents(array($this->pathToDefaults, $type, $name.'.json') );
        if ($defaultValue !== false) {
            if ($this->useObjects) {
                return Utils\Json::decode($defaultValue);
            } else {
                return Utils\Json::decode($defaultValue, true);
            }
        }
        if ($this->useObjects) {
            return (object) [];
        } else {
            return array();
        }
    }

}

?>