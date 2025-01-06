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

use Espo\Core\Exceptions\Error;

use Espo\Core\Container;

class BeforeUpgrade
{
    public function run(Container $container)
    {
        $this->container = $container;

        $this->processCheckExtensions();
        $this->processCheckCache();

        // Load to prevent fail if run in a single process.
        $container->get('entityManager')
            ->getQueryBuilder()
            ->update()
            ->in('Test')
            ->set(['test' => 'test'])
            ->build();
    }

    private function processCheckCache()
    {
        $isCli = (substr(php_sapi_name(), 0, 3) == 'cli') ? true : false;

        if (!$isCli) {
            return;
        }

        $cacheParam = 'opcache.enable_cli';

        $value = ini_get($cacheParam);

        if ($value === '1') {
            throw new Error("PHP parameter '{$cacheParam}' should be set to '0'.");
        }
    }

    private function processCheckExtensions(): void
    {
        $errorMessageList = [];

        $this->processCheckExtension('Advanced Pack', '2.8.0', $errorMessageList);
        $this->processCheckExtension('Sales Pack', '1.1.4', $errorMessageList);
        $this->processCheckExtension('Outlook Integration', '1.2.5', $errorMessageList);
        $this->processCheckExtension('MailChimp Integration', '1.0.8', $errorMessageList);
        $this->processCheckExtension('Real Estate', '1.5.0', $errorMessageList);
        $this->processCheckExtension('VoIP Integration', '1.17.3', $errorMessageList);

        if (!count($errorMessageList)) {
            return;
        }

        $message = implode("\n\n", $errorMessageList);

        throw new Error($message);
    }

    private function processCheckExtension(string $name, string $minVersion, array &$errorMessageList): void
    {
        $em = $this->container->get('entityManager');

        $extension = $em->getRepository('Extension')
            ->where([
                'name' => $name,
                'isInstalled' => true,
            ])
            ->findOne();

        if (!$extension) {
            return;
        }

        $version = $extension->get('version');

        if (version_compare($version, $minVersion, '>=')) {
            return;
        }

        $message =
            "EspoCRM 7.0 is not compatible with '{$name}' extension of a version lower than {$minVersion}. " .
            "Please upgrade the extension or uninstall it. Then run the upgrade command again.";

        $errorMessageList[] = $message;
    }
}
