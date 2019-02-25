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
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;

class FileUnifier
{
    private $fileManager;
    private $metadata;

    public function __construct(\Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Metadata $metadata = null)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
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
     * Unite files content
     *
     * @param array $paths
     * @param bool $isReturnModuleNames - If need to return data with module names
     *
     * @return array
     */
    public function unify(array $paths, $isReturnModuleNames = false)
    {
        $data = $this->loadData($paths['corePath']);

        if (!empty($paths['modulePath'])) {
            $moduleDir = strstr($paths['modulePath'], '{*}', true);
            $moduleList = isset($this->metadata) ? $this->getMetadata()->getModuleList() : $this->getFileManager()->getFileList($moduleDir, false, '', false);

            foreach ($moduleList as $moduleName) {
                $moduleFilePath = str_replace('{*}', $moduleName, $paths['modulePath']);

                if ($isReturnModuleNames) {
                    if (!isset($data[$moduleName])) {
                        $data[$moduleName] = array();
                    }
                    $data[$moduleName] = Util::merge($data[$moduleName], $this->loadData($moduleFilePath));
                    continue;
                }

                $data = Util::merge($data, $this->loadData($moduleFilePath));
            }
        }

        if (!empty($paths['customPath'])) {
            $data = Util::merge($data, $this->loadData($paths['customPath']));
        }

        return $data;
    }

    /**
     * Load data from a file
     *
     * @param  string $filePath
     * @param  array  $returns
     * @return array
     */
    protected function loadData($filePath, $returns = array())
    {
        if (file_exists($filePath)) {
            $content = $this->getFileManager()->getContents($filePath);
            $data = Json::getArrayData($content);
            if (empty($data)) {
                $GLOBALS['log']->warning('FileUnifier::unify() - Empty file or syntax error - ['.$filePath.']');
                return $returns;
            }

            return $data;
        }

        return $returns;
    }
}
