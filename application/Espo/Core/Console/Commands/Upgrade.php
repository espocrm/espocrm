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

namespace Espo\Core\Console\Commands;

use Espo\Core\Application;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Exceptions\Error;
use Espo\Core\Upgrades\UpgradeManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Util;

use Symfony\Component\Process\PhpExecutableFinder;

use Exception;
use Throwable;
use stdClass;

use const CURLOPT_FILE;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const FILTER_VALIDATE_URL;
use const STDOUT;

/**
 * @noinspection PhpUnused
 */
class Upgrade implements Command
{
    private ?UpgradeManager $upgradeManager = null;

    /** @var string[] */
    private $upgradeStepList = [
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

    /** @var array<string, string> */
    private $upgradeStepLabels = [
        'init' => 'Initialization',
        'copyBefore' => 'Copying before upgrade files',
        'rebuild' => 'Rebuilding',
        'beforeUpgradeScript' => 'Before upgrade script execution',
        'copy' => 'Copying files',
        'copyAfter' => 'Copying after upgrade files',
        'afterUpgradeScript' => 'After upgrade script execution',
        'finalize' => 'Finalization',
        'revert' => 'Reverting',
    ];

    public function __construct(
        private FileManager $fileManager,
        private Config $config,
        private Log $log
    ) {}

    /**
     * @throws Error
     */
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

        fwrite(STDOUT, "Current version is $fromVersion.\n");

        if (!$upgradeParams->skipConfirmation) {
            fwrite(STDOUT, "EspoCRM will be upgraded to version $nextVersion now. Enter [Y] to continue.\n");

            if (!$this->confirm()) {
                echo "Upgrade canceled.\n";

                return;
            }
        }

        fwrite(STDOUT, "This may take a while. Do not close the terminal.\n");

        if (filter_var($packageFile, FILTER_VALIDATE_URL)) {
            fwrite(STDOUT, "Downloading...");

            $packageFile = $this->downloadFile($packageFile);

            fwrite(STDOUT, "\n");

            if (!$packageFile) {
                fwrite(STDOUT, "Error: Unable to download upgrade package.\n");

                $io->setExitStatus(1);

                return;
            }
        }

        $upgradeId = $upgradeId ?? $this->upload($packageFile);

        fwrite(STDOUT, "Upgrading...");

        try {
            $this->runUpgradeProcess($upgradeId, $upgradeParams);
        } catch (Throwable $e) {
            $this->displayStep('revert');
            $errorMessage = $e->getMessage();
        }

        fwrite(STDOUT, "\n");

        if (!$upgradeParams->keepPackageFile) {
            $this->fileManager->unlink($packageFile);
        }

        if (isset($errorMessage)) {
            $errorMessage = !empty($errorMessage) ? $errorMessage : "Error: An unexpected error occurred.";

            fwrite(STDOUT, $errorMessage . "\n");

            $io->setExitStatus(1);

            return;
        }

        $currentVersion = $this->getCurrentVersion();

        fwrite(STDOUT, "Upgrade is complete. Current version is $currentVersion.\n");

        if ($lastVersion && $lastVersion !== $currentVersion && $fromVersion !== $currentVersion) {
            fwrite(STDOUT, "Newer version is available. Run command again to upgrade.\n");

            return;
        }

        if ($lastVersion && $lastVersion === $currentVersion) {
            fwrite(STDOUT, "You have the latest version.\n");
        }
    }

    /**
     * Normalize params. Permitted options and flags and $arguments:
     * -y - without confirmation
     * -s - single process
     * --file="EspoCRM-upgrade.zip"
     * --step="beforeUpgradeScript"
     *
     * @param array<string, string> $options
     * @param string[] $flagList
     * @param string[] $argumentList
     * @return stdClass
     * @noinspection PhpUnusedParameterInspection
     */
    private function normalizeParams(array $options, array $flagList, array $argumentList): object
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

    private function getPackageFile(stdClass $params, ?stdClass $versionInfo): ?string
    {
        $packageFile = $params->file ?? null;

        if (!$params->localMode) {
            if (empty($versionInfo)) {
                fwrite(STDOUT, "Error: Upgrade server is currently unavailable. Please try again later.\n");

                return null;
            }

            if (!isset($versionInfo->nextVersion)) {
                fwrite(STDOUT, "There are no available upgrades.\n");

                return null;
            }

            if (!isset($versionInfo->nextPackage)) {
                fwrite(STDOUT, "Error: Upgrade package is not found.\n");

                return null;
            }

            return $versionInfo->nextPackage;
        }

        if (!$packageFile || !file_exists($packageFile)) {
            fwrite(STDOUT, "Error: Upgrade package is not found.\n");

            return null;
        }

        return $packageFile;
    }

    private function upload(string $filePath): string
    {
        try {
            /** @var string $fileData */
            $fileData = file_get_contents($filePath);
            $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

            $upgradeId = $this->getUpgradeManager()->upload($fileData);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage() . "\n");
        }

        return $upgradeId;
    }

    /**
     * @throws Error
     */
    private function runUpgradeProcess(string $upgradeId, ?stdClass $params = null): void
    {
        $params = $params ?? (object) [];

        $useSingleProcess = property_exists($params, 'singleProcess') ? $params->singleProcess : false;

        $stepList = !empty($params->step) ? [$params->step] : $this->upgradeStepList;

        array_unshift($stepList, 'init');
        $stepList[] = 'finalize';

        if (!$useSingleProcess && $this->isShellEnabled()) {
            $this->runSteps($upgradeId, $stepList);

            return;
        }

        $this->runStepsInSingleProcess($upgradeId, $stepList);
    }

    /**
     * @param string[] $stepList
     * @throws Error
     */
    private function runStepsInSingleProcess(string $upgradeId, array $stepList): void
    {
        $this->log->debug('Installation process ['.$upgradeId.']: Single process mode.');

        try {
            foreach ($stepList as $stepName) {
                $this->displayStep($stepName);

                $upgradeManager = $this->getUpgradeManager(true);

                $upgradeManager->runInstallStep($stepName, ['id' => $upgradeId]);
            }
        } catch (Throwable $e) {
            try {
                $this->log->error('Upgrade Error: ' . $e->getMessage());
            } catch (Throwable) {}

            throw new Error($e->getMessage());
        }
    }

    /**
     * @param string[] $stepList
     * @throws Error
     */
    private function runSteps(string $upgradeId, array $stepList): void
    {
        $phpExecutablePath = $this->getPhpExecutablePath();

        foreach ($stepList as $stepName) {
            $this->displayStep($stepName);

            $command = $phpExecutablePath . " command.php upgrade-step --step=" . ucfirst($stepName) .
                " --id=" . $upgradeId;

            /** @var string $shellResult */
            $shellResult = shell_exec($command);

            if ($shellResult !== 'true') {
                try {
                    $this->log->error('Upgrade Error: ' . $shellResult);
                } catch (Throwable) {}

                throw new Error($shellResult ?: 'Unknown error on shell_exec.');
            }
        }
    }

    private function displayStep(string $stepName): void
    {
        $stepLabel = $this->upgradeStepLabels[$stepName] ?? "";

        fwrite(STDOUT, "\n  $stepLabel...");
    }

    private function confirm(): bool
    {
        /** @var resource $fh */
        $fh = fopen('php://stdin', 'r');

        $inputLine = trim(fgets($fh)); /** @phpstan-ignore-line */

        fclose($fh);

        if (strtolower($inputLine) !== 'y'){
            return false;
        }

        return true;
    }

    private function getUpgradeManager(bool $reload = false): UpgradeManager
    {
        if (!$this->upgradeManager || $reload) {
            $app = new Application();

            $app->setupSystemUser();

            $this->upgradeManager = new UpgradeManager($app->getContainer());
        }

        return $this->upgradeManager;
    }

    private function getPhpExecutablePath(): string
    {
        $phpExecutablePath = $this->config->get('phpExecutablePath');

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new PhpExecutableFinder)->find();
        }

        return $phpExecutablePath;
    }

    private function getVersionInfo(?string $toVersion = null): ?stdClass
    {
        $url = 'https://s.espocrm.com/upgrade/next/';
        $url = $this->config->get('upgradeNextVersionUrl', $url);
        $url .= '?fromVersion=' . $this->config->get('version');

        if ($toVersion) {
            $url .= '&toVersion=' . $toVersion;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $result = curl_exec($ch);

        curl_close($ch);

        try {
            $data = json_decode($result); /** @phpstan-ignore-line */
        } catch (Exception) { /** @phpstan-ignore-line */
            echo "Could not parse info about next version.\n";

            return null;
        }

        if (!$data) {
            echo "Could not get info about next version.\n";

            return null;
        }

        return $data;
    }

    private function downloadFile(string $url): ?string
    {
        $localFilePath = 'data/upload/upgrades/' . Util::generateId() . '.zip';

        $this->fileManager->putContents($localFilePath, '');

        if (is_file($url)) {
            copy($url, $localFilePath);
        } else {
            $options = [
                CURLOPT_FILE => fopen($localFilePath, 'w'),
                CURLOPT_TIMEOUT => 3600,
                CURLOPT_URL => $url,
            ];

            $ch = curl_init();

            curl_setopt_array($ch, $options);

            curl_exec($ch);

            curl_close($ch);
        }

        if (!$this->fileManager->isFile($localFilePath)) {
            echo "\nCould not download upgrade file.\n";

            $this->fileManager->unlink($localFilePath);

            return null;
        }

        $path = realpath($localFilePath);

        assert($path !== false);

        return $path;
    }

    private function isShellEnabled(): bool
    {
        if (
            !function_exists('exec') ||
            !is_callable('shell_exec') /** @phpstan-ignore-line */
        ) {
            return false;
        }

        $result = shell_exec("echo test");

        if (empty($result)) {
            return false;
        }

        return true;
    }

    private function getCurrentVersion(): ?string
    {
        $configData = include "data/config.php"; /** @phpstan-ignore-line */

        if (!$configData) {
            return null;
        }

        return $configData['version'] ?? null;
    }
}
