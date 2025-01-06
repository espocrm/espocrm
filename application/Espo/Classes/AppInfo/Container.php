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

namespace Espo\Classes\AppInfo;

use Espo\Core\Console\Command\Params;
use Espo\Core\Container as ContainerService;
use Espo\Core\Utils\Metadata;

class Container
{
    public function __construct(private ContainerService $container, private Metadata $metadata)
    {}

    public function process(Params $params): string
    {
        $nameOnly = $params->hasFlag('nameOnly');

        $result = '';

        $serviceList = [
            'injectableFactory',
            'config',
            'log',
            'fileManager',
            'dataManager',
            'metadata',
            'user',
        ];

        /** @var string[] $fileList */
        $fileList = scandir('application/Espo/Core/Loaders');

        if (file_exists('custom/Espo/Custom/Core/Loaders')) {
            $fileList = array_merge($fileList, scandir('custom/Espo/Custom/Core/Loaders') ?: []);
        }

        foreach ($fileList as $file) {
            if (substr($file, -4) === '.php') {
                $name = lcfirst(substr($file, 0, -4));

                if (!in_array($name, $serviceList) && $this->container->has($name)) {
                    $serviceList[] = $name;
                }
            }
        }

        foreach ($this->metadata->get(['app', 'containerServices']) ?? [] as $name => $data) {
            if (!in_array($name, $serviceList)) {
                $serviceList[] = $name;
            }
        }

        sort($serviceList);

        if ($nameOnly) {
            foreach ($serviceList as $name) {
                $result .= $name . "\n";
            }

            return $result;
        }

        foreach ($serviceList as $name) {
            $result .= $name . "\n";

            $obj = $this->container->get($name);
            $result .= get_class($obj) . "\n";

            $result .= "\n";
        }

        return $result;
    }
}
