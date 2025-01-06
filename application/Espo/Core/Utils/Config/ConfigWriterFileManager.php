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

namespace Espo\Core\Utils\Config;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;

use RuntimeException;

class ConfigWriterFileManager
{
    private FileManager $fileManager;

    /**
     * @param ?array{
     *   dir: string|int|null,
     *   file: string|int|null,
     *   user: string|int|null,
     *   group: string|int|null,
     * } $defaultPermissions
     */
    public function __construct(?Config $config = null, ?array $defaultPermissions = null)
    {
        $defaultPermissionsToSet = null;

        if ($defaultPermissions) {
            $defaultPermissionsToSet = $defaultPermissions;
        } else if ($config) {
            $defaultPermissionsToSet = $config->get('defaultPermissions');
        }

        $this->fileManager = new FileManager($defaultPermissionsToSet);
    }

    public function setConfig(Config $config): void
    {
        $this->fileManager = new FileManager(
            $config->get('defaultPermissions')
        );
    }

    public function isFile(string $filePath): bool
    {
        return $this->fileManager->isFile($filePath);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function putPhpContentsInternal(string $path, array $data, bool $useRenaming = false): void
    {
        $result = $this->fileManager->putPhpContents($path, $data, true, $useRenaming);

        if ($result === false) {
            throw new RuntimeException();
        }
    }

    /**
     * @param array<string, mixed> $data $data
     */
    public function putPhpContents(string $path, array $data): void
    {
        $this->putPhpContentsInternal($path, $data, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function putPhpContentsNoRenaming(string $path, array $data): void
    {
        $this->putPhpContentsInternal($path, $data, false);
    }


    /**
     * Supposed to return array. False means the file is being written or corrupted.
     * @return array<string, mixed>|false
     */
    public function getPhpContents(string $path)
    {
        try {
            $data = $this->fileManager->getPhpContents($path);
        } catch (RuntimeException) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        /** @var array<string, mixed> */
        return $data;
    }
}
