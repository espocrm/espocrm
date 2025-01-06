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

namespace Espo\Core\Utils\Resource;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Resource\FileReader\Params;
use RuntimeException;

/**
 * Reads resource files.
 */
class FileReader
{
    public function __construct(
        private FileManager $fileManager,
        private Metadata $metadata,
        private PathProvider $pathProvider
    ) {}

    /**
     * Read a resource file. Returns NULL if the file does not exist.
     *
     * @throws RuntimeException If the resource does not exist.
     */
    public function read(string $path, Params $params): string
    {
        $exactPath = $this->findExactPath($path, $params);

        if (!$exactPath) {
            throw new RuntimeException("Resource file '$path' does not exist.");
        }

        return $this->fileManager->getContents($exactPath);
    }

    /**
     * Whether a resource file exists.
     */
    public function exists(string $path, Params $params): bool
    {
        return $this->findExactPath($path, $params) !== null;
    }

    private function findExactPath(string $path, Params $params): ?string
    {
        $customPath = $this->pathProvider->getCustom() . $path;

        if ($this->fileManager->isFile($customPath)) {
            return $customPath;
        }

        $moduleName = null;

        if ($params->getScope()) {
            $moduleName = $this->metadata->getScopeModuleName($params->getScope());
        }

        if ($moduleName) {
            $modulePath = $this->buildModulePath($path, $moduleName);

            if ($this->fileManager->isFile($modulePath)) {
                return $modulePath;
            }
        }

        if ($params->getModuleName()) {
            $modulePath = $this->buildModulePath($path, $params->getModuleName());

            if ($this->fileManager->isFile($modulePath)) {
                return $modulePath;
            }
        }

        $corePath = $this->pathProvider->getCore() . $path;

        if ($this->fileManager->isFile($corePath)) {
            return $corePath;
        }

        return null;
    }

    private function buildModulePath(string $path, string $moduleName): string
    {
        return $this->pathProvider->getModule($moduleName) . $path;
    }
}
