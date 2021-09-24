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

namespace Espo\Classes\AppInfo;

use Espo\Core\{
    Container as ContainerService,
    Utils\Metadata,
    Console\Command\Params,
};

class Container
{
    private $container;

    private $metadata;

    public function __construct(ContainerService $container, Metadata $metadata)
    {
        $this->container = $container;
        $this->metadata = $metadata;
    }

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

        $fileList = scandir('application/Espo/Core/Loaders');

        if (file_exists('custom/Espo/Custom/Core/Loaders')) {
            $fileList = array_merge($fileList, scandir('custom/Espo/Custom/Core/Loaders'));
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
