<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace EspoDev;

use Espo\Core\Application;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Config\ConfigWriterFileManager;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Json;

/**
 * Creates the config file if does not exists. Sets the 'version' from the `package.json` in the config.
 */
class SetConfigParams
{
    public static function process(): void
    {
        if (substr(php_sapi_name(), 0, 3) !== 'cli') {
            return;
        }

        $fileManager = new FileManager();

        $packageData = Json::decode(
            $fileManager->getContents('package.json')
        );

        $version = $packageData->version ?? null;

        if (!$version) {
            return;
        }

        $configPath = 'data/config.php';

        $configWriterFileManager = new ConfigWriterFileManager();

        if (!$configWriterFileManager->isFile($configPath)) {
            $configWriterFileManager->putPhpContents($configPath, []);
        }

        $app = new Application();

        /** @var \Espo\Core\InjectableFactory $injectableFactory */
        $injectableFactory = $app->getContainer()->get('injectableFactory');

        /** @var \Espo\Core\Utils\Config $config */
        $config = $app->getContainer()->get('config');

        if ($config->get('version') === $version) {
            return;
        }

        /** @var ConfigWriter $configWriter */
        $configWriter = $injectableFactory->create(ConfigWriter::class);

        $configWriter->set('version', $version);

        $configWriter->save();
    }
}
