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

namespace Espo\Core\Console\Commands;

use Espo\Core\{
    Exceptions\Error,
    Application,
    Upgrades\UpgradeManager,
    Utils\Util,
    Utils\File\Manager as FileManager,
    Utils\Config,
    Utils\Log,
    Console\Command,
    Console\Command\Params,
    Console\IO,
};

use Symfony\Component\Process\PhpExecutableFinder;

use Exception;
use Throwable;

class Upgrade implements Command
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

    private $fileManager;

    private $config;

    private $log;

    public function __construct(FileManager $fileManager, Config $config, Log $log)
    {
        $this->fileManager = $fileManager;
        $this->config = $config;
        $this->log = $log;
    }

    public function run(Params $params, IO $io): void
    {
        $options = $params->getOptions();
        $flagList = $params->getFlagList();
        $argumentList = $params->getArgumentList();

        $upgradeParams = $this->normalizeParams($options, $flagList, $argumentList);

        $fromVersion = $this->config->get('version');
        $toVersion = $upgradeParams->toVersion ?? null;

        $versionInfo = $this->getVersionInfo($toVersion);

        $nextVersion = $versionInfo->nextVersion ?? null;
        $lastVersion = $versionInfo->lastVersion ?? null;

        $packageFile = $this->getPackageFile($upgradeParams, $versionInfo);

        if (!$packageFile) {
            return;
        }

        if ($upgradeParams->localMode) {
            $upgradeId = $this->upload($packageFile);

            $manifest = $this->getUpgradeManager()->getManifestById($upgradeId);

            $nextVersion = $manifest['version'];
        }

        fwrite(\STDOUT, "Current version is {$fromVersion}.\n");

        if (!$upgradeParams->skipConfirmation) {
            fwrite(\STDOUT, "EspoCRM will be upgraded to version {$nextVersion} now. Enter [Y] to continue.\n");

            if (!$this->confirm()) {
                echo "Upgrade canceled.\n";

                return;
            }
        }

        if (filter_var($packageFile, \FILTER_VALIDATE_URL)) {
            fwrite(\STDOUT, "Downloading...");

            $packageFile = $this->downloadFile($packageFile);

            fwrite(\STDOUT, "\n");

            if (!$packageFile) {
                fwrite(\STDOUT, "Error: Unable to download upgrade package.\n");

                return;
            }
        }

        $upgradeId = $upgradeId ?? $this->upload($packageFile);

        fwrite(\STDOUT, "Upgrading... This may take a while...");

        try {
            $this->runUpgradeProcess($upgradeId, $upgradeParams);
        }
        catch (Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        fwrite(\STDOUT, "\n");

        if (!$upgradeParams->keepPackageFile) {
            $this->fileManager->unlink($packageFile);
        }

        if (isset($errorMessage)) {
            $errorMessage = !empty($errorMessage) ? $errorMessage : "Error: An unexpected error occurred.";

            fwrite(\STDOUT, $errorMessage . "\n");

            return;
        }

        $currentVersion = $this->getCurrentVersion();

        fwrite(\STDOUT, "Upgrade is complete. Current version is {$currentVersion}.\n");

        if ($lastVersion && $lastVersion !== $currentVersion && $fromVersion !== $currentVersion) {
            fwrite(\STDOUT, "Newer version is available. Run command again to upgrade.\n");

            return;
        }

        if ($lastVersion && $lastVersion === $currentVersion) {
            fwrite(\STDOUT, "You have the latest version.\n");

            return;
        }
    }

    /**
     * Normalize params. Permitted options and flags and $arguments:
     * -y - without confirmation
     * -s - single process
     * --file="EspoCRM-upgrade.zip"
     * --step="beforeUpgradeScript"
     */
    protected function normalizeParams(array $options, array $flagList, array $argumentList): object
    {
        $params = (object) [
            'localMode' => false,
            'skipConfirmation' => false,
            'singleProcess' => false,
            'keepPackageFile' => false,
        ];

        if (!empty($options['file'])) {
            $params->localMode = true;
            $params->file = $options['file'];
            $params->keepPackageFile = true;
        }

        if (in_array('y', $flagList)) {
            $params->skipConfirmation = true;
        }

        if (in_array('s', $flagList)) {
            $params->singleProcess = true;
        }

        if (in_array('patch', $flagList)) {
            $currentVersion = $this->config->get('version');

            if (preg_match('/^(.*)\.(.*)\..*$/', $currentVersion, $match)) {
                $options['toVersion'] = $match[1] . '.' . $match[2];
            }
        }

        if (!empty($options['step'])) {
            $params->step = $options['step'];
        }

        if (!empty($options['toVersion'])) {
            $params->toVersion = $options['toVersion'];
        }

        return $params;
    }

    /**
     * @param \stdClass $params
     * @param \stdClass|null $versionInfo
     * @return string|null
     */
    protected function getPackageFile(object $params, ?object $versionInfo)
    {
        $packageFile = $params->file ?? null;

        if (!$params->localMode) {
            if (empty($versionInfo)) {
                fwrite(\STDOUT, "Error: Upgrade server is currently unavailable. Please try again later.\n");

                return null;
            }

            if (!isset($versionInfo->nextVersion)) {
                fwrite(\STDOUT, "There are no available upgrades.\n");

                return null;
            }

            if (!isset($versionInfo->nextPackage)) {
                fwrite(\STDOUT, "Error: Upgrade package is not found.\n");

                return null;
            }

            return $versionInfo->nextPackage;
        }

        if (!$packageFile || !file_exists($packageFile)) {
            fwrite(\STDOUT, "Error: Upgrade package is not found.\n");

            return null;
        }

        return $packageFile;
    }

    protected function upload(string $filePath)
    {
        try {
            $fileData = file_get_contents($filePath);
            $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

            $upgradeId = $this->getUpgradeManager()->upload($fileData);
        }
        catch (Exception $e) {
            die("Error: " . $e->getMessage() . "\n");
        }

        return $upgradeId;
    }

    protected function runUpgradeProcess(string $upgradeId, object $params = null)
    {
        $params = $params ?? (object) [];

        $useSingleProcess = property_exists($params, 'singleProcess') ? $params->singleProcess : false;

        $stepList = !empty($params->step) ? [$params->step] : $this->upgradeStepList;

        array_unshift($stepList, 'init');
        array_push($stepList, 'finalize');

        if (!$useSingleProcess && $this->isShellEnabled()) {
            return $this->runSteps($upgradeId, $stepList);
        }

        return $this->runStepsInSingleProcess($upgradeId, $stepList);
    }

    protected function runStepsInSingleProcess(string $upgradeId, array $stepList)
    {
        $this->log->debug('Installation process ['.$upgradeId.']: Single process mode.');

        try {
            foreach ($stepList as $stepName) {
                fwrite(\STDOUT, ".");

                $upgradeManager = $this->getUpgradeManager(true);
                $upgradeManager->runInstallStep($stepName, ['id' => $upgradeId]);
            }
        } catch (Throwable $e) {
            try {
                $this->log->error('Upgrade Error: ' . $e->getMessage());
            }
            catch (Throwable $t) {}

            throw new Error($e->getMessage());
        }

        return true;
    }

    protected function runSteps(string $upgradeId, array $stepList)
    {
        $phpExecutablePath = $this->getPhpExecutablePath();

        foreach ($stepList as $stepName) {
            fwrite(\STDOUT, ".");

            $command = $phpExecutablePath . " command.php upgrade-step --step=". ucfirst($stepName) ." --id=". $upgradeId;

            $shellResult = shell_exec($command);

            if ($shellResult !== 'true') {
                try {
                    $this->log->error('Upgrade Error: ' . $shellResult);
                }
                catch (Throwable $t) {}

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

    protected function getUpgradeManager(bool $reload = false)
    {
        if (!$this->upgradeManager || $reload) {
            $app = new Application();

            $app->setupSystemUser();

            $this->upgradeManager = new UpgradeManager($app->getContainer());
        }

        return $this->upgradeManager;
    }

    protected function getPhpExecutablePath()
    {
        $phpExecutablePath = $this->config->get('phpExecutablePath');

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new PhpExecutableFinder)->find();
        }

        return $phpExecutablePath;
    }

    protected function getVersionInfo($toVersion = null)
    {
        $url = 'https://s.espocrm.com/upgrade/next/';
        $url = $this->config->get('upgradeNextVersionUrl', $url);
        $url .= '?fromVersion=' . $this->config->get('version');

        if ($toVersion) {
            $url .= '&toVersion=' . $toVersion;
        }

        $ch = curl_init();

        curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_URL, $url);

        $result = curl_exec($ch);

        curl_close($ch);

        try {
            $data = json_decode($result);
        }
        catch (Exception $e) { /** @phpstan-ignore-line */
            echo "Could not parse info about next version.\n";

            return null;
        }

        if (!$data) {
            echo "Could not get info about next version.\n";

            return null;
        }

        return $data;
    }

    protected function downloadFile(string $url)
    {
        $localFilePath = 'data/upload/upgrades/' . Util::generateId() . '.zip';

        $this->fileManager->putContents($localFilePath, '');

        if (is_file($url)) {
            copy($url, $localFilePath);
        } else {
            $options = [
                \CURLOPT_FILE => fopen($localFilePath, 'w'),
                \CURLOPT_TIMEOUT => 3600,
                \CURLOPT_URL => $url,
            ];

            $ch = curl_init();

            curl_setopt_array($ch, $options);

            curl_exec($ch);

            curl_close($ch);
        }

        if (!$this->fileManager->isFile($localFilePath)) {
            echo "\nCould not download upgrade file.\n";

            $this->fileManager->unlink($localFilePath);

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

    protected function getCurrentVersion()
    {
        $configData = include "data/config.php";

        if (!$configData) {
            return null;
        }

        return $configData['version'] ?? null;
    }
}
