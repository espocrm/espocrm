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

class AfterUpgrade
{
    private $container;

    public function run($container)
    {
        $this->container = $container;

        $this->updateConfig();

        $this->removeUnnecessaryFiles();
        $this->removeUnnecessaryDirectories();
    }

    protected function updateConfig()
    {
        $config = $this->container->get('config');

        $actualTimeFormat = $config->get('timeFormat');

        if ($actualTimeFormat === 'hh:mm') {
            $config->set('timeFormat', 'HH:mm');
        }

        $config->set('pdfEngine', 'Tcpdf');

        $config->save();
    }

    protected function removeUnnecessaryFiles()
    {
        $fileList = [
            'vendor/spatie/async/.git/objects/pack/pack-14ab89d3ff365322e20cfd44252880928aaa4ed6.idx',
            'vendor/spatie/async/.git/objects/pack/pack-14ab89d3ff365322e20cfd44252880928aaa4ed6.pack',
            'vendor/zordius/lightncandy/.git/objects/pack/pack-8b009a4f84cb95d704fb194c5fee79c724dee033.pack',
            'vendor/zordius/lightncandy/.git/objects/pack/pack-8b009a4f84cb95d704fb194c5fee79c724dee033.idx',
        ];

        foreach ($fileList as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $result = unlink($file);

            if (!$result) {
                $this->container->get('fileManager')->getPermissionUtils()->chmod($file, [
                    'file' => '0664',
                    'dir' => '0775',
                ]);

                unlink($file);
            }
        }
    }

    protected function removeUnnecessaryDirectories()
    {
        $directoryList = [
            'vendor/spatie/async/.git',
            'vendor/zordius/lightncandy/.git',
        ];

        foreach ($directoryList as $directory) {
            if (!file_exists($directory)) {
                continue;
            }

            $this->container->get('fileManager')->removeInDir($directory, true);
        }
    }
}
