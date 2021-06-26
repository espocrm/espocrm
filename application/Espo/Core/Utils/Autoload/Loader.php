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

namespace Espo\Core\Utils\Autoload;

use Espo\Core\Utils\File\Manager as FileManager;

class Loader
{
    private $namespaceLoader;

    private $fileManager;

    public function __construct(NamespaceLoader $namespaceLoader, FileManager $fileManager)
    {
        $this->namespaceLoader = $namespaceLoader;
        $this->fileManager = $fileManager;
    }

    public function register(array $data): void
    {
        /* load "psr-4", "psr-0", "classmap" */
        $this->namespaceLoader->register($data);

        /* load "autoloadFileList" */
        $this->registerAutoloadFileList($data);

        /* load "files" */
        $this->registerFiles($data);
    }

    private function registerAutoloadFileList(array $data): void
    {
        $keyName = 'autoloadFileList';

        if (!isset($data[$keyName])) {
            return;
        }

        foreach ($data[$keyName] as $filePath) {
            if ($this->fileManager->exists($filePath)) {
                require_once($filePath);
            }
        }
    }

    private function registerFiles(array $data): void
    {
        $keyName = 'files';

        if (!isset($data[$keyName])) {
            return;
        }

        foreach ($data[$keyName] as $filePath) {
            if ($this->fileManager->exists($filePath)) {
                require_once($filePath);
            }
        }
    }
}
