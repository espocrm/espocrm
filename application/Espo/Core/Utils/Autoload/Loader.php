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

namespace Espo\Core\Utils\Autoload;

class Loader
{
    private $config;

    private $fileManager;

    private $namespaceLoader;

    public function __construct(\Espo\Core\Utils\Config $config, \Espo\Core\Utils\File\Manager $fileManager)
    {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->namespaceLoader = new NamespaceLoader($config, $fileManager);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getFileManager()
    {
        return $this->fileManager;
    }

    public function getNamespaceLoader()
    {
        return $this->namespaceLoader;
    }

    public function register(array $autoloadList)
    {
        /* load "psr-4", "psr-0", "classmap" */
        $this->getNamespaceLoader()->register($autoloadList);

        /* load "autoloadFileList" */
        $this->registerAutoloadFileList($autoloadList);

        /* load "files" */
        $this->registerFiles($autoloadList);
    }

    protected function registerAutoloadFileList(array $autoloadList)
    {
        $keyName = 'autoloadFileList';

        if (!isset($autoloadList[$keyName])) return;

        foreach ($autoloadList[$keyName] as $filePath) {
            if (file_exists($filePath)) {
                require_once($filePath);
            }
        }
    }

    protected function registerFiles(array $autoloadList)
    {
        $keyName = 'files';

        if (!isset($autoloadList[$keyName])) return;

        foreach ($autoloadList[$keyName] as $id => $filePath) {
            if (file_exists($filePath)) {
                require_once($filePath);
            }
        }
    }
}
