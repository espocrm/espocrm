<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Console\Commands;

class Upgrade extends Base
{
    public function run()
    {
        $infoData = $this->getVersionInfo();
        if (!$infoData) return;

        $nextVersion = $infoData->nextVersion ?? null;
        $lastVersion = $infoData->lastVersion ?? null;

        $fromVersion = $this->getConfig()->get('version');

        fwrite(\STDOUT, "Current version is {$fromVersion}.\n");

        if (!$nextVersion) {
            echo "There are no available upgrades.\n";
            return;
        }

        fwrite(\STDOUT, "EspoCRM will be upgraded to version {$nextVersion} now. Enter [Y] to continue.\n");

        if (!$this->confirm()) {
            echo "Upgrade canceled.\n";
            return;
        }

        fwrite(\STDOUT, "Downloading...");

        $upgradePackageFilePath = $this->downloadFile($infoData->nextPackage);
        if (!$upgradePackageFilePath) return;

        fwrite(\STDOUT, "\n");

        fwrite(\STDOUT, "Upgrading... This may take a while...");

        $this->upgrade($upgradePackageFilePath);

        fwrite(\STDOUT, "\n");

        fwrite(\STDOUT, $resultText);

        $this->getFileManager()->unlink($upgradePackageFilePath);

        $app = new \Espo\Core\Application();
        $currentVerison = $app->getContainer()->get('config')->get('version');

        fwrite(\STDOUT, "Upgrade is complete. Current version is {$currentVerison}.\n");

        if ($lastVersion && $lastVersion !== $currentVerison && $fromVersion !== $currentVerison) {
            fwrite(\STDOUT, "Newer version is available. Run command again to upgrade.\n");
            return;
        }

        if ($lastVersion && $lastVersion === $currentVerison) {
            fwrite(\STDOUT, "You have the latest version.\n");
            return;
        }
    }

    protected function upgrade($filePath)
    {
        $app = new \Espo\Core\Application();
        $app->setupSystemUser();

        $upgradeManager = new \Espo\Core\UpgradeManager($app->getContainer());

        try {
            $fileData = file_get_contents($filePath);
            $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

            $upgradeId = $upgradeManager->upload($fileData);
            $upgradeManager->install(['id' => $upgradeId]);
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage() . "\n");
        }
    }

    protected function confirm()
    {
        $fh = fopen('php://stdin', 'r');
        $inputLine = trim(fgets($fh));
        fclose($fh);
        if (strtolower($inputLine) !== 'y'){
            return false;
        }
        return true;
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    protected function getVersionInfo()
    {
        $url = 'https://s.espocrm.com/upgrade/next/';
        $url = $this->getConfig()->get('upgradeNextVersionUrl', $url);
        $url .= '?fromVersion=' . $this->getConfig()->get('version');

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);

        try {
            $data = json_decode($result);
        } catch (\Exception $e) {
            echo "Could not parse info about next version.\n";
            return;
        }

        if (!$data) {
            echo "Could not get info about next version.\n";
            return;
        }

        return $data;
    }

    protected function downloadFile($url)
    {
        $localFilePath = 'data/upload/upgrades/' . \Espo\Core\Utils\Util::generateId() . '.zip';
        $this->getFileManager()->putContents($localFilePath, '');

        if (is_file($url)) {
            copy($url, $localFilePath);
        } else {
            $options = [
                CURLOPT_FILE  => fopen($localFilePath, 'w'),
                CURLOPT_TIMEOUT => 3600,
                CURLOPT_URL => $url
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $options);
            curl_exec($ch);
            curl_close($ch);
        }

        if (!$this->getFileManager()->isFile($localFilePath)) {
            echo "\nCould not download upgrade file.\n";
            $this->getFileManager()->unlink($localFilePath);
            return;
        }

        return realpath($localFilePath);
    }
}
