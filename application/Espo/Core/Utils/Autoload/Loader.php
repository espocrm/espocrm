<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Utils\Autoload;

use Espo\Core\Utils\File\Manager as FileManager;

class Loader
{
    public function __construct(
        private NamespaceLoader $namespaceLoader,
        private FileManager $fileManager
    ) {}

    /**
     *
     * @param array{
     *   psr-4?: array<string, mixed>,
     *   psr-0?: array<string, mixed>,
     *   classmap?: array<string, mixed>,
     *   autoloadFileList?: array<string, mixed>,
     *   files?: array<string, mixed>,
     * } $data
     */
    public function register(array $data): void
    {
        /* load "psr-4", "psr-0", "classmap" */
        $this->namespaceLoader->register($data);

        /* load "autoloadFileList" */
        $this->registerAutoloadFileList($data);

        /* load "files" */
        $this->registerFiles($data);
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @param array<string, mixed> $data
     */
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
