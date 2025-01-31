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

namespace Espo\Core\Upgrades\Migration;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager;
use RuntimeException;

class VersionDataProvider
{
    private string $defaultConfigPath = 'application/Espo/Resources/defaults/config.php';

    public function __construct(
        private Manager $fileManager,
        private Config\SystemConfig $systemConfig,
    ) {}

    public function getPreviousVersion(): string
    {
        $version = $this->systemConfig->getVersion();

        if (!$version) {
            throw new RuntimeException("No or bad 'version' in config.");
        }

        return $version;
    }

    public function getTargetVersion(): string
    {
        $data = $this->fileManager->getPhpContents($this->defaultConfigPath);

        if (!is_array($data)) {
            throw new RuntimeException("No default config.");
        }

        $version = $data['version'] ?? null;

        if (!$version || !is_string($version)) {
            throw new RuntimeException("No or bad 'version' parameter in default config.");
        }

        return $version;
    }
}
