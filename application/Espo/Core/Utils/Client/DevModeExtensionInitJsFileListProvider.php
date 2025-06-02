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

namespace Espo\Core\Utils\Client;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Module;
use Espo\Core\Utils\Util;

/**
 * Allows bundled extensions to work when the system is in the developer mode.
 */
class DevModeExtensionInitJsFileListProvider
{
    public function __construct(
        private Module $module,
        private FileManager $fileManager,
        private Config $config,
    ) {}

    /**
     * @return string[]
     */
    public function get(): array
    {
        $developedModule = $this->config->get('developedModule');

        if (!$developedModule) {
            return [];
        }

        $output = [];

        foreach ($this->getBundledModuleList() as $module) {
            if ($module === $developedModule) {
                continue;
            }

            $file = "client/custom/modules/$module/lib/init.js";

            if ($this->fileManager->exists($file)) {
                $output[] = $file;
            }
        }

        return $output;
    }

    /**
     * @return string[]
     */
    private function getBundledModuleList(): array
    {
        $modules = array_values(array_filter(
            $this->module->getList(),
            fn ($item) => $this->module->get([$item, 'bundled'])
        ));

        return array_map(
            fn ($item) => Util::fromCamelCase($item, '-'),
            $modules
        );
    }
}
