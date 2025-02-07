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

namespace Espo\Core\Upgrades\Actions;

use Espo\Core\DataManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\System;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Upgrades\ActionManager;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Database\Helper as DatabaseHelper;
use Espo\Core\Utils\File\ZipArchive;
use Espo\Core\Utils\Log;

use Composer\Semver\Semver;

use Espo\ORM\EntityManager;
use Throwable;

abstract class Base
{
    /** Directory name of files in a package. */
    protected const FILES = 'files';
    /** Directory name of scripts in a package. */
    protected const SCRIPTS = 'scripts';

    private string $defaultPackageType = 'extension';
    private string $vendorDirName = 'vendor';
    protected string $manifestName = 'manifest.json';
    private string $packagePostfix = 'z';

    /** @var array<string, mixed> */
    protected mixed $data = [];
    /** @var array<string, mixed> */
    private array $params;
    protected ?string $processId = null;
    protected ?string $parentProcessId = null;
    /** @var array<string, mixed> */
    protected array $scriptParams = [];

    /** @var array<string, string> */
    private array $packageTypes = [
        'upgrade' => 'upgrade',
        'extension' => 'extension',
    ];

    private ZipArchive $zipUtil;
    private ?DatabaseHelper $databaseHelper = null;
    private ?Helper $helper = null;

    public function __construct(
        private Container $container,
        private ActionManager $actionManager
    ) {
        $this->params = $actionManager->getParams();

        $fileManager = $container->getByClass(FileManager::class);
        $this->zipUtil = new ZipArchive($fileManager);
    }

    private function getContainer(): Container
    {
        return $this->container;
    }

    private function getParam(string $name): mixed
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return null;
    }

    private function getZipUtil(): ZipArchive
    {
        return $this->zipUtil;
    }

    private function getDatabaseHelper(): DatabaseHelper
    {
        if (!isset($this->databaseHelper)) {
            /** @var InjectableFactory $injectableFactory */
            $injectableFactory = $this->getContainer()->get('injectableFactory');

            $this->databaseHelper = $injectableFactory->create(DatabaseHelper::class);
        }

        return $this->databaseHelper;
    }

    protected function getLog(): Log
    {
        return $this->getContainer()->getByClass(Log::class);
    }

    protected function getFileManager(): FileManager
    {
        return $this->getContainer()->getByClass(FileManager::class);
    }

    protected function getConfig(): Config
    {
        return $this->getContainer()->getByClass(Config::class);
    }

    protected function getSystemConfig(): Config\SystemConfig
    {
        return $this->getContainer()->getByClass(Config\SystemConfig::class);
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->getByClass(EntityManager::class);
    }

    protected function getInjectableFactory(): InjectableFactory
    {
        return $this->getContainer()->getByClass(InjectableFactory::class);
    }

    protected function createConfigWriter(): ConfigWriter
    {
        $injectableFactory = $this->getContainer()->getByClass(InjectableFactory::class);

        return $injectableFactory->create(ConfigWriter::class);
    }

    /**
     * @throws Error
     */
    public function throwErrorAndRemovePackage(
        string $errorMessage = '',
        bool $deletePackage = true,
        bool $systemRebuild = true,
        ?Throwable $exception = null
    ): void {

        if ($deletePackage) {
            $this->deletePackageFiles();
            $this->deletePackageArchive();
        }

        $this->disableMaintenanceMode(true);

        if ($systemRebuild) {
            $this->systemRebuild();
        }

        if ($exception && !$errorMessage) {
            $errorMessage = $exception->getMessage();
        }

        throw new Error($errorMessage, 0, $exception);
    }

    abstract public function run(mixed $data): mixed;

    /**
     * @throws Error
     */
    protected function createProcessId(): string
    {
        if (isset($this->processId)) {
            throw new Error('Another installation process is currently running.');
        }

        $recordIdGenerator = $this->getInjectableFactory()->createResolved(RecordIdGenerator::class);

        $this->processId = $recordIdGenerator->generate();

        return $this->processId;
    }

    /**
     * @throws Error
     */
    protected function getProcessId(): string
    {
        if (!isset($this->processId)) {
            throw new Error('Installation ID was not specified.');
        }

        return $this->processId;
    }

    private function getParentProcessId(): ?string
    {
        return $this->parentProcessId;
    }

    public function setProcessId(string $processId): void
    {
        $this->processId = $processId;
    }

    protected function setParentProcessId(string $processId): void
    {
        $this->parentProcessId = $processId;
    }

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM.
     *
     * @throws Error
     */
    protected function isAcceptable(): bool
    {
        $manifest = $this->getManifest();

        $res = $this->checkPackageType();

        if (isset($manifest['php'])) {
            $res &= $this->checkVersions(
                $manifest['php'], System::getPhpVersion(),
                'Your PHP version ({version}) is not supported. Required version: {requiredVersion}.'
            );
        }

        if (isset($manifest['database'])) {
            $databaseHelper = $this->getDatabaseHelper();
            $databaseType = $databaseHelper->getType();
            $databaseTypeLc = strtolower($databaseType);

            if (isset($manifest['database'][$databaseTypeLc])) {
                $databaseVersion = $databaseHelper->getVersion();

                if ($databaseVersion) {
                    $res &= $this->checkVersions(
                        $manifest['database'][$databaseTypeLc],
                        $databaseVersion,
                        'Your '. $databaseType .
                        ' version ({version}) is not supported. Required version: {requiredVersion}.'
                    );
                }
            }
        }

        $version = $this->getSystemConfig()->getVersion();

        // Skip @@version for extension development.
        if (isset($manifest['acceptableVersions']) && $version !== '@@version') {
            $res &= $this->checkVersions(
                $manifest['acceptableVersions'],
                $version,
                'Your EspoCRM version ({version}) is not supported. Required version: {requiredVersion}.'
            );
        }

        if (!empty($manifest['dependencies'])) {
            $res &= $this->checkDependencies($manifest['dependencies']);
        }

        return (bool) $res;
    }

    /**
     * @param string[]|string $versionList
     * @throws Error
     */
    public function checkVersions($versionList, ?string $currentVersion, string $errorMessage = ''): bool
    {
        if (empty($versionList)) {
            return true;
        }

        if (!$currentVersion) {
            return false;
        }

        if (is_string($versionList)) {
            $versionList = (array) $versionList;
        }

        $version = null;

        foreach ($versionList as $version) {
            $isInRange = false;

            try {
                $isInRange = Semver::satisfies($currentVersion, $version);
            } catch (Throwable $e) {
                $this->getLog()->error("SemVer: Version identification error: {$e->getMessage()}.");
            }

            if ($isInRange) {
                return true;
            }
        }

        /** @noinspection RegExpRedundantEscape */
        $errorMessage = preg_replace('/\{version\}/', $currentVersion, $errorMessage);

        if (!is_string($errorMessage)) {
            $errorMessage = '?';
        }

        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        /** @noinspection RegExpRedundantEscape */
        $errorMessage = preg_replace('/\{requiredVersion\}/', $version, $errorMessage);

        if (!is_string($errorMessage)) {
            $errorMessage = '?';
        }

        $this->throwErrorAndRemovePackage($errorMessage);

        return false;
    }

    /**
     * @throws Error
     */
    private function checkPackageType(): bool
    {
        $manifest = $this->getManifest();

        /** check package type */
        $type = strtolower($this->getParam('name'));

        $manifestType = isset($manifest['type']) ? strtolower($manifest['type']) : $this->defaultPackageType;

        if (!in_array($manifestType, $this->packageTypes)) {
            $this->throwErrorAndRemovePackage('Unknown package type.');
        }

        if ($type != $manifestType) {
            $uType = ucfirst($type);

            $this->throwErrorAndRemovePackage(
                "Wrong package type. You cannot install $manifestType package via $uType Manager.");
        }

        return true;
    }

    /**
     * @param array<string, string[]> $dependencyList
     */
    protected function checkDependencies(array $dependencyList): bool
    {
        return true;
    }

    /**
     * Run a script by a type.
     *
     * @param string $type Ex. "before", "after".
     * @throws Error
     */
    protected function runScript(string $type): void
    {
        $beforeInstallScript = $this->getScriptPath($type);

        if (!$beforeInstallScript) {
            return;
        }

        $scriptNames = $this->getParam('scriptNames');
        $scriptName = $scriptNames[$type];

        require_once($beforeInstallScript);

        $script = new $scriptName();

        try {
            assert(method_exists($script, 'run'));

            $script->run($this->getContainer(), $this->scriptParams);
        } catch (Throwable $e) {
            $this->throwErrorAndRemovePackage(exception: $e);
        }
    }

    /**
     * @throws Error
     */
    protected function getScriptPath(string $type): ?string
    {
        $packagePath = $this->getPackagePath();
        $scriptNames = $this->getParam('scriptNames');

        $scriptName = $scriptNames[$type];

        if (!isset($scriptName)) {
            return null;
        }

        $beforeInstallScript = Util::concatPath([$packagePath, self::SCRIPTS, $scriptName]) . '.php';

        if (file_exists($beforeInstallScript)) {
            return $beforeInstallScript;
        }

        return null;
    }

    /**
     * Get package path,
     *
     * @throws Error
     */
    protected function getPath(string $name = 'packagePath', bool $isPackage = false): string
    {
        $postfix = $isPackage ? $this->packagePostfix : '';

        $processId = $this->getProcessId();
        $path = Util::concatPath($this->getParam($name), $processId);

        return $path . $postfix;
    }

    /**
     * @throws Error
     */
    protected function getPackagePath(bool $isPackage = false): string
    {
        return $this->getPath('packagePath', $isPackage);
    }

    /**
     * @return string[]
     * @throws Error
     */
    protected function getDeleteList(string $type = 'delete'): array
    {
        $manifest = $this->getManifest();

        switch ($type) {
            case 'delete':
            case 'deleteBeforeCopy':
                if (isset($manifest[$type])) {
                    return $manifest[$type];
                }

                break;

            case 'vendor':
                return $this->getVendorFileList('delete');
        }

        return [];
    }

    /**
     * Get a list of files defined in manifest.
     *
     * @return string[]
     * @throws Error
     */
    private function getDeleteFileList(): array
    {
        if (!isset($this->data['deleteFileList'])) {
            $deleteFileList = [];

            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $deleteList = array_merge(
                $this->getDeleteList('delete'),
                $this->getDeleteList('deleteBeforeCopy'),
                $this->getDeleteList('vendor')
            );

            foreach ($deleteList as $itemPath) {
                if (is_dir($itemPath)) {
                    /** @var string[] $fileList */
                    $fileList = $this->getFileManager()->getFileList($itemPath, true, '', true, true);

                    $fileList = $this->concatStringWithArray($itemPath, $fileList);

                    $deleteFileList = array_merge($deleteFileList, $fileList);

                    continue;
                }

                $deleteFileList[] = $itemPath;
            }

            $this->data['deleteFileList'] = $deleteFileList;
        }

        return $this->data['deleteFileList'];
    }

    /**
     * Delete files defined in a manifest.
     *
     * @throws Error
     */
    protected function deleteFiles(string $type = 'delete', bool $withEmptyDirs = false): bool
    {
        $deleteList = $this->getDeleteList($type);

        if (!empty($deleteList)) {
            return $this->getFileManager()->remove($deleteList, null, $withEmptyDirs);
        }

        return true;
    }

    /**
     * @return string[]
     * @throws Error
     */
    protected function getCopyFileList(): array
    {
        if (!isset($this->data['fileList'])) {
            $packagePath = $this->getPackagePath();

            $this->data['fileList'] = $this->getFileList($packagePath);
        }

        return $this->data['fileList'];
    }

    /**
     * @return string[]
     * @throws Error
     */
    protected function getRestoreFileList(): array
    {
        if (!isset($this->data['restoreFileList'])) {
            $backupPath = $this->getPath('backupPath');

            $this->data['restoreFileList'] = $this->getFileList($backupPath, true);
        }

        return $this->data['restoreFileList'];
    }

    /**
     * Get file directories (files, beforeInstallFiles, afterInstallFiles).
     *
     * @return string[]
     */
    protected function getFileDirs(?string $parentDirPath = null): array
    {
        $dirNames = $this->getParam('customDirNames');

        $paths = [self::FILES, $dirNames['before'], $dirNames['after']];

        if (isset($parentDirPath)) {
            foreach ($paths as &$path) {
                $path = Util::concatPath($parentDirPath, $path);
            }
        }

        return $paths;
    }

    /**
     * Get file list from directories: files, beforeUpgradeFiles, afterUpgradeFiles.
     *
     * @return string[]
     * @throws Error
     */
    private function getFileList(string $dirPath, bool $skipVendorFileList = false): array
    {
        $fileList = [];

        $paths = $this->getFileDirs($dirPath);

        foreach ($paths as $filesPath) {
            if (file_exists($filesPath)) {
                /** @var string[] $files */
                $files = $this->getFileManager()->getFileList($filesPath, true, '', true, true);

                /** @var string[] $fileList */
                $fileList = array_merge($fileList, $files);
            }
        }

        if (!$skipVendorFileList) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $vendorFileList = $this->getVendorFileList('copy');

            if (!empty($vendorFileList)) {
                /** @var string[] $fileList */
                $fileList = array_merge($fileList, $vendorFileList);
            }
        }

        return $fileList;
    }

    /**
     * @param ?string[] $fileList
     * @throws Error
     */
    protected function copy(
        string $sourcePath,
        string $destPath,
        bool $recursively = false,
        ?array $fileList = null,
        bool $copyOnlyFiles = false
    ): bool {

        try {
            return $this->getFileManager()->copy($sourcePath, $destPath, $recursively, $fileList, $copyOnlyFiles);
        } catch (Throwable $e) {
            $this->throwErrorAndRemovePackage(exception: $e);
        }

        return false;
    }

    /**
     * Copy files from upgrade/extension package.
     *
     * @throws Error
     */
    protected function copyFiles(?string $type = null, string $dest = ''): bool
    {
        $filesPath = $this->getCopyFilesPath($type);

        if ($filesPath) {
            switch ($type) {
                case 'vendor':
                    $dest = $this->vendorDirName;

                    break;
            }

            return $this->copy($filesPath, $dest, true);
        }

        return true;
    }

    /**
     * Get needed file list based on type. E.g. file list for "beforeCopy" action.
     *
     * @throws Error
     */
    protected function getCopyFilesPath(?string $type = null): ?string
    {
        switch ($type) {
            case 'before':
            case 'after':
                $dirNames = $this->getParam('customDirNames');

                $dirPath = $dirNames[$type];

                break;

            case 'vendor':
                $dirNames = $this->getParam('customDirNames');

                if (isset($dirNames['vendor'])) {
                    $dirPath = $dirNames['vendor'];
                }

                break;

            default:
                $dirPath = self::FILES;

                break;
        }

        if (isset($dirPath)) {
            $packagePath = $this->getPackagePath();
            $filesPath = Util::concatPath($packagePath, $dirPath);

            if (file_exists($filesPath)) {
                return $filesPath;
            }
        }

        return null;
    }

    /**
     * @return string[]
     * @throws Error
     */
    private function getVendorFileList(string $type = 'copy'): array
    {
        $list = [];

        $packagePath = $this->getPackagePath();
        $dirNames = $this->getParam('customDirNames');

        if (!isset($dirNames['vendor'])) {
            return $list;
        }

        $filesPath = Util::concatPath($packagePath, $dirNames['vendor']);

        if (!file_exists($filesPath)) {
            return $list;
        }

        switch ($type) {
            case 'copy':
                /** @var string[] $list */
                $list = $this->getFileManager()->getFileList($filesPath, true, '', true, true);

                break;

            case 'delete':
                /** @var string[] $list */
                $list = $this->getFileManager()->getFileList($filesPath, false, '', null, true);

                break;
        }

        foreach ($list as &$path) {
            $path = Util::concatPath($this->vendorDirName, $path);
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function getManifest(): array
    {
        if (!isset($this->data['manifest'])) {
            $packagePath = $this->getPackagePath();

            $manifestPath = Util::concatPath($packagePath, $this->manifestName);

            if (!file_exists($manifestPath)) {
                $this->throwErrorAndRemovePackage("It's not an Installation package.");
            }

            $manifestJson = $this->getFileManager()->getContents($manifestPath);

            $this->data['manifest'] = Json::decode($manifestJson, true);

            if (!$this->data['manifest']) {
                $this->throwErrorAndRemovePackage('Syntax error in manifest.json.');
            }

            if (!$this->checkManifest($this->data['manifest'])) {
                $this->throwErrorAndRemovePackage('Unsupported package.');
            }
        }

        return $this->data['manifest'];
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function checkManifest(array $manifest): bool
    {
        $requiredFields = [
            'name',
            'version',
        ];

        foreach ($requiredFields as $fieldName) {
            if (empty($manifest[$fieldName])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Error
     */
    protected function getManifestParam(string $name): mixed
    {
        $manifest = $this->getManifest();

        if (array_key_exists($name, $manifest)) {
            return $manifest[$name];
        }

        return null;
    }

    /**
     * Unzip a package archive.
     *
     * @throws Error
     */
    protected function unzipArchive(?string $packagePath = null): void
    {
        $packagePath = $packagePath ?? $this->getPackagePath();
        $packageArchivePath = $this->getPackagePath(true);

        if (!file_exists($packageArchivePath)) {
            $this->throwErrorAndRemovePackage('Package Archive does not exist.', false, false);
        }

        $res = $this->getZipUtil()->unzip($packageArchivePath, $packagePath);

        if ($res === false) {
            $this->throwErrorAndRemovePackage("Unable to unzip the file $packagePath.", false, false);
        }
    }

    /**
     * Delete temporary package files.
     *
     * @throws Error
     */
    protected function deletePackageFiles(): bool
    {
        $packagePath = $this->getPackagePath();

        return $this->getFileManager()->removeInDir($packagePath, true);
    }

    /**
     * Delete temporary package archive.
     *
     * @throws Error
     */
    protected function deletePackageArchive(): bool
    {
        $packageArchive = $this->getPackagePath(true);

        return $this->getFileManager()->removeFile($packageArchive);
    }

    protected function systemRebuild(): bool
    {
        try {
            $dataManager = $this->getContainer()->getByClass(DataManager::class);

            $dataManager->rebuild();
            $dataManager->updateAppTimestamp();

            return true;
        } catch (Throwable $e) {
            try {
                $this->getLog()->error("Database rebuild failure, details: {$e->getMessage()}.", ['exception' => $e]);
            } catch (Throwable) {}
        }

        return false;
    }

    /**
     * Execute an action. For ex., execute uninstall action in install.
     *
     * @param string|array<string, mixed> $data
     * @throws Error
     */
    protected function executeAction(string $actionName, $data): void
    {
        $actionManager = $this->actionManager;

        $currentAction = $actionManager->getAction();

        $actionManager->setAction($actionName);
        $actionManager->run($data);
        $actionManager->setAction($currentAction);
    }

    protected function initialize(): void
    {}

    protected function finalize(): void
    {}

    protected function beforeRunAction(): void
    {}

    protected function afterRunAction(): void
    {}

    /**
     * @throws Error
     */
    protected function clearCache(): void
    {
        $dataManager = $this->getContainer()->getByClass(DataManager::class);

        $dataManager->clearCache();
    }

    /**
     * @throws Error
     */
    protected function checkIsWritable(): void
    {
        $backupPath = $this->getPath('backupPath');
        $fullFileList = array_merge([$backupPath], $this->getDeleteFileList(), $this->getCopyFileList());

        $result = $this->getFileManager()->isWritableList($fullFileList);

        if ($result) {
            return;
        }

        $permissionDeniedList = $this->getFileManager()->getLastPermissionDeniedList();

        $delimiter = $this->isCli() ? "\n" : "<br>";

        $this->throwErrorAndRemovePackage(
            "Permission denied: " . $delimiter . implode($delimiter, $permissionDeniedList),
            false,
            false
        );
    }

    /**
     * @throws Error
     */
    protected function backupExistingFiles(): bool
    {
        $fullFileList = array_merge($this->getDeleteFileList(), $this->getCopyFileList());

        $backupPath = $this->getPath('backupPath');

        $destination = Util::concatPath($backupPath, self::FILES);

        return $this->copy('', $destination, false, $fullFileList);
    }

    protected function getHelper(): Helper
    {
        if (!$this->helper) {
            $this->helper = new Helper($this->getEntityManager());
        }

        $this->helper->setActionObject($this);

        assert($this->helper !== null);

        return $this->helper;
    }

    /**
     * @param string $string
     * @param string[] $array
     * @return string[]
     */
    private function concatStringWithArray(string $string, array $array): array
    {
        foreach ($array as &$value) {
            if (!str_ends_with($string, '/')) {
                $string .= '/';
            }

            $value = $string . $value;
        }

        return $array;
    }

    /**
     * @throws Error
     */
    protected function enableMaintenanceMode(): void
    {
        $config = $this->getConfig();
        $configWriter = $this->createConfigWriter();

        $configParamName = $this->getTemporaryConfigParamName();
        $parentConfigParamName = $this->getTemporaryConfigParamName(true);

        if (
            ($configParamName && $config->has($configParamName)) ||
            ($parentConfigParamName && $config->has($parentConfigParamName))
        ) {
            return;
        }

        $actualParams = [
            'maintenanceMode' => $config->get('maintenanceMode'),
            'cronDisabled' => $config->get('cronDisabled'),
            'useCache' => $config->get('useCache'),
        ];

        if ($configParamName) {
            // @todo Maybe to remove this line?
            $configWriter->set($configParamName, $actualParams);
        }

        $save = false;

        if (!$actualParams['maintenanceMode']) {
            $configWriter->set('maintenanceMode', true);

            $save = true;
        }

        if (!$actualParams['cronDisabled']) {
            $configWriter->set('cronDisabled', true);

            $save = true;
        }

        if ($actualParams['useCache']) {
            $configWriter->set('useCache', false);

            $save = true;
        }

        if ($save) {
            $configWriter->save();
        }
    }

    /**
     * @throws Error
     */
    protected function disableMaintenanceMode(bool $force = false): void
    {
        $config = $this->getConfig();
        $configWriter = $this->createConfigWriter();

        $configParamList = [
            $this->getTemporaryConfigParamName(),
        ];

        if ($force && $this->getTemporaryConfigParamName(true)) {
            $configParamList[] = $this->getTemporaryConfigParamName(true);
        }

        $save = false;

        foreach ($configParamList as $configParamName) {
            if ($configParamName === null) {
                continue;
            }

            if (!$config->has($configParamName)) {
                continue;
            }

            foreach ($config->get($configParamName, []) as $paramName => $paramValue) {
                if ($config->get($paramName) != $paramValue) {
                    $configWriter->set($paramName, $paramValue);
                }
            }

            $configWriter->remove($configParamName);

            $save = true;
        }

        if ($save) {
            $configWriter->save();
        }
    }

    /**
     * @throws Error
     */
    private function getTemporaryConfigParamName(bool $isParentProcess = false): ?string
    {
        $processId = $this->getProcessId();

        if ($isParentProcess) {
            $processId = $this->getParentProcessId();

            if (!$processId) {
                return null;
            }
        }

        return 'temporaryUpgradeParams' . $processId;
    }

    private function isCli(): bool
    {
        if (str_starts_with(php_sapi_name() ?: '', 'cli')) {
            return true;
        }

        return false;
    }
}
