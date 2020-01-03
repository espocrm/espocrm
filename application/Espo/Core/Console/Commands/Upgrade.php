<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Error;

class Upgrade extends Base
{
    protected $upgradeManager;

    protected $upgradeStepList = [
        'copyBefore',
        'rebuild',
        'beforeUpgradeScript',
        'rebuild',
        'copy',
        'rebuild',
        'copyAfter',
        'rebuild',
        'afterUpgradeScript',
        'rebuild',
    ];

    public function run($options, $flagList, $argumentList)
    {
        $params = $this->normalizeParams($options, $flagList, $argumentList);

        switch ($params['mode']) {
            case 'local':
                $this->runLocalUpgrade($params);
                break;

            default:
            case 'remote':
                $this->runRemoteUpgrade($params);
                break;
        }
    }

    /**
     * Normalize params. Permitted options and flags and $arguments:
     * -y - without confirmation
     * -s - single process
     * --file="EspoCRM-upgrade.zip"
     * --step="beforeUpgradeScript"
     * @param  array $options
     * @param  array $flagList
     * @param  array $argumentList
     * @return array
     */
    protected function normalizeParams($options, $flagList, $argumentList)
    {
        $params = [
            'mode' => 'remote',
            'skipConfirmation' => false,
            'singleProcess' => false,
        ];

        if (!empty($options['file'])) {
            $params['mode'] = 'local';
            $params['file'] = $options['file'];
        }

        if (in_array('y', $flagList)) {
            $params['skipConfirmation'] = true;
        }

        if (in_array('s', $flagList)) {
            $params['singleProcess'] = true;
        }

        if (!empty($options['step'])) {
            $params['step'] = $options['step'];
        }

        return $params;
    }

    protected function runLocalUpgrade(array $params)
    {
        if (empty($params['file']) || !file_exists($params['file'])) {
            echo "Upgrade package is not found.\n";
            return;
        }

        $packageFile = $params['file'];
        $fromVersion = $this->getConfig()->get('version');

        fwrite(\STDOUT, "Current version is {$fromVersion}.\n");

        $upgradeId = $this->upload($packageFile);
        $manifest = $this->getUpgradeManager()->getManifestById($upgradeId);
        $nextVersion = $manifest['version'];

        if (!$params['skipConfirmation']) {
            fwrite(\STDOUT, "EspoCRM will be upgraded to version {$nextVersion} now. Enter [Y] to continue.\n");

            if (!$this->confirm()) {
                echo "Upgrade canceled.\n";
                return;
            }
        }

        fwrite(\STDOUT, "Upgrading... This may take a while...");

        try {
            $this->runUpgradeProcess($upgradeId, $params);
        } catch (\Exception $e) {
            fwrite(\STDOUT, "\n");
            fwrite(\STDOUT, $e->getMessage() . "\n");
            return;
        }

        fwrite(\STDOUT, "\n");

        $app = new \Espo\Core\Application();
        $currentVerison = $app->getContainer()->get('config')->get('version');

        fwrite(\STDOUT, "Upgrade is complete. Current version is {$currentVerison}.\n");

        $infoData = $this->getVersionInfo();
        $lastVersion = $infoData->lastVersion ?? null;

        if ($lastVersion && $lastVersion !== $currentVerison && $fromVersion !== $currentVerison) {
            fwrite(\STDOUT, "Newer version is available.\n");
            return;
        }

        if ($lastVersion && $lastVersion === $currentVerison) {
            fwrite(\STDOUT, "You have the latest version.\n");
            return;
        }
    }

    protected function runRemoteUpgrade(array $params)
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

        if (!$params['skipConfirmation']) {
            fwrite(\STDOUT, "EspoCRM will be upgraded to version {$nextVersion} now. Enter [Y] to continue.\n");

            if (!$this->confirm()) {
                echo "Upgrade canceled.\n";
                return;
            }
        }

        fwrite(\STDOUT, "Downloading...");

        $upgradePackageFilePath = $this->downloadFile($infoData->nextPackage);
        if (!$upgradePackageFilePath) return;

        fwrite(\STDOUT, "\n");

        fwrite(\STDOUT, "Upgrading... This may take a while...");

        $upgradeId = $this->upload($upgradePackageFilePath);

        try {
            $this->runUpgradeProcess($upgradeId, $params);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $this->getFileManager()->unlink($upgradePackageFilePath);

        fwrite(\STDOUT, "\n");

        if (!empty($error)) {
            echo $error;
            return;
        }

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

    protected function upload($filePath)
    {
        try {
            $fileData = file_get_contents($filePath);
            $fileData = 'data:application/zip;base64,' . base64_encode($fileData);
            $upgradeId = $this->getUpgradeManager()->upload($fileData);
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage() . "\n");
        }

        return $upgradeId;
    }

    protected function runUpgradeProcess($upgradeId, array $params = [])
    {
        $useSingleProcess = array_key_exists('singleProcess', $params) ? $params['singleProcess'] : false;

        $stepList = !empty($params['step']) ? [$params['step']] : $this->upgradeStepList;
        array_unshift($stepList, 'init');
        array_push($stepList, 'finalize');

        if (!$useSingleProcess && $this->isShellEnabled()) {
            return $this->runSteps($upgradeId, $stepList);
        }

        return $this->runStepsInSingleProcess($upgradeId, $stepList);
    }

    protected function runStepsInSingleProcess($upgradeId, array $stepList)
    {
        $GLOBALS['log']->debug('Installation process ['.$upgradeId.']: Single process mode.');

        try {
            foreach ($stepList as $stepName) {
                $upgradeManager = $this->getUpgradeManager(true);
                $upgradeManager->runInstallStep($stepName, ['id' => $upgradeId]);
            }
        } catch (\Exception $e) {
            $GLOBALS['log']->error('Upgrade Error: ' . $e->getMessage());
            throw new Error($e->getMessage());
        }

        return true;
    }

    protected function runSteps($upgradeId, array $stepList)
    {
        $phpExecutablePath = $this->getPhpExecutablePath();

        foreach ($stepList as $stepName) {
            $command = $phpExecutablePath . " command.php upgrade-step --step=". ucfirst($stepName) ." --id=". $upgradeId;

            $shellResult = shell_exec($command);
            if ($shellResult !== 'true') {
                $GLOBALS['log']->error('Upgrade Error: ' . $shellResult);
                throw new Error($shellResult);
            }
        }

        return true;
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

    protected function getUpgradeManager($reload = false)
    {
        if (!$this->upgradeManager || $reload) {
            $app = new \Espo\Core\Application();
            $app->setupSystemUser();

            $this->upgradeManager = new \Espo\Core\UpgradeManager($app->getContainer());
        }

        return $this->upgradeManager;
    }

    protected function getPhpExecutablePath()
    {
        $phpExecutablePath = $this->getConfig()->get('phpExecutablePath');

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new \Symfony\Component\Process\PhpExecutableFinder)->find();
        }

        return $phpExecutablePath;
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

    protected function isShellEnabled()
    {
        if (!function_exists('exec') || !is_callable('shell_exec')) {
            return false;
        }

        $result = shell_exec("echo test");
        if (empty($result)) {
            return false;
        }

        return true;
    }
}
