<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Utils\Util;
use Espo\Core\Utils\System;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Upgrades\ActionManager;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Database\Helper as DatabaseHelper;
use Espo\Core\Utils\File\ZipArchive;
use Espo\Core\Utils\Log;

use Composer\Semver\Semver;

use Throwable;

abstract class Base
{
    /**
     * @var ?\Espo\Core\Upgrades\Actions\Helper
     */
    private $helper;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var array<string, mixed>
     */
    protected $params = null;

    /**
     * @var \Espo\Core\Container
     */
    private $container;

    /**
     * @var ?ActionManager
     */
    private $actionManager;

    /**
     * @var ZipArchive
     */
    private $zipUtil;

    /**
     * @var ?DatabaseHelper
     */
    private $databaseHelper;

    /**
     * @var ?string
     */
    protected $processId = null;

    /**
     * @var ?string
     */
    protected $parentProcessId = null;

    /**
     * @var string
     */
    protected $manifestName = 'manifest.json';

    /**
     * @var string
     */
    protected $packagePostfix = 'z';

    /**
     * @var array<string, mixed>
     */
    protected $scriptParams = [];

    /**
     * Directory name of files in a package.
     */
    const FILES = 'files';

    /**
     * Directory name of scripts in a package.
     */
    const SCRIPTS = 'scripts';

    /**
     * Package types.
     *
     * @var array<string, string>
     */
    protected $packageTypes = array(
        'upgrade' => 'upgrade',
        'extension' => 'extension',
    );

    /**
     * Default package type.
     *
     * @var string
     */
    protected $defaultPackageType = 'extension';

    /**
     * @var string
     */
    protected $vendorDirName = 'vendor';

    public function __construct(Container $container, ActionManager $actionManager)
    {
        $this->container = $container;
        $this->actionManager = $actionManager;
        $this->params = $actionManager->getParams();

        /** @var FileManager $fileManager */
        $fileManager = $container->get('fileManager');

        $this->zipUtil = new ZipArchive($fileManager);
    }

    /**
     * @return \Espo\Core\Container
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        assert($this->actionManager !== null);

        return $this->actionManager;
    }

    /**
     * @param string $name
     * @param mixed $returns
     * @return mixed
     */
    protected function getParams($name, $returns = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return $returns;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @return ZipArchive
     */
    protected function getZipUtil()
    {
        return $this->zipUtil;
    }

    /**
     * @return DatabaseHelper
     */
    protected function getDatabaseHelper()
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
        /** @var Log */
        return $this->getContainer()->get('log');
    }

    /**
     * @return FileManager
     */
    protected function getFileManager()
    {
        /** @var FileManager */
        return $this->getContainer()->get('fileManager');
    }

    /**
     * @return \Espo\Core\Utils\Config
     */
    protected function getConfig()
    {
        /** @var \Espo\Core\Utils\Config */
        return $this->getContainer()->get('config');
    }

    /**
     * @return \Espo\ORM\EntityManager
     */
    public function getEntityManager()
    {
        /** @var \Espo\ORM\EntityManager */
        return $this->getContainer()->get('entityManager');
    }

    public function createConfigWriter(): ConfigWriter
    {
        /** @var \Espo\Core\InjectableFactory $injectableFactory */
        $injectableFactory = $this->getContainer()->get('injectableFactory');

        return $injectableFactory->create(ConfigWriter::class);
    }

    /**
     *
     * @param string $errorMessage
     * @param bool $deletePackage
     * @param bool $systemRebuild
     * @return void
     * @throws Error
     */
    public function throwErrorAndRemovePackage($errorMessage = '', $deletePackage = true, $systemRebuild = true)
    {
        if ($deletePackage) {
            $this->deletePackageFiles();
            $this->deletePackageArchive();
        }

        $this->disableMaintenanceMode(true);

        if ($systemRebuild) {
            $this->systemRebuild();
        }

        throw new Error($errorMessage);
    }

    /**
     * @param never $data
     * @return mixed
     */
    abstract public function run($data);

    /**
     * @return string
     * @throws Error
     */
    protected function createProcessId()
    {
        if (isset($this->processId)) {
            throw new Error('Another installation process is currently running.');
        }

        $this->processId = Util::generateId();

        return $this->processId;
    }

    /**
     * @return string
     * @throws Error
     */
    protected function getProcessId()
    {
        if (!isset($this->processId)) {
            throw new Error('Installation ID was not specified.');
        }

        return $this->processId;
    }

    /**
     * @return ?string
     */
    protected function getParentProcessId()
    {
        return $this->parentProcessId;
    }

    /**
     * @param string $processId
     * @return void
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    /**
     * @param string $processId
     * @return void
     */
    public function setParentProcessId($processId)
    {
        $this->parentProcessId = $processId;
    }

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM.
     *
     * @return bool
     * @throws Error
     */
    protected function isAcceptable()
    {
        $manifest = $this->getManifest();

        $res = $this->checkPackageType();

        // check php version
        if (isset($manifest['php'])) {
            $res &= $this->checkVersions(
                $manifest['php'], System::getPhpVersion(),
                'Your PHP version ({version}) is not supported. Required version: {requiredVersion}.'
            );
        }

        //check database version
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

        // check acceptableVersions
        if (isset($manifest['acceptableVersions'])) {
            $res &= $this->checkVersions(
                $manifest['acceptableVersions'],
                $this->getConfig()->get('version'),
                'Your EspoCRM version ({version}) is not supported. Required version: {requiredVersion}.'
            );
        }

        // check dependencies
        if (!empty($manifest['dependencies'])) {
            $res &= $this->checkDependencies($manifest['dependencies']);
        }

        return (bool) $res;
    }

    /**
     * @param string[]|string $versionList
     * @param string $currentVersion
     * @param string $errorMessage
     * @return bool
     * @throws Error
     */
    public function checkVersions($versionList, $currentVersion, $errorMessage = '')
    {
        if (empty($versionList)) {
            return true;
        }

        if (is_string($versionList)) {
            $versionList = (array) $versionList;
        }

        $version = null;

        foreach ($versionList as $version) {
            $isInRange = false;

            try {
                $isInRange = Semver::satisfies($currentVersion, $version);
            }
            catch (Throwable $e) {
                $this->getLog()->error('SemVer: Version identification error: '.$e->getMessage().'.');
            }

            if ($isInRange) {
                return true;
            }
        }

        /** @var string $errorMessage */
        $errorMessage = preg_replace('/\{version\}/', $currentVersion, $errorMessage);
        /** @var string $errorMessage */
        $errorMessage = preg_replace('/\{requiredVersion\}/', $version, $errorMessage);

        $this->throwErrorAndRemovePackage($errorMessage);

        return false;
    }

    /**
     * @return bool
     * @throws Error
     */
    protected function checkPackageType()
    {
        $manifest = $this->getManifest();

        /** check package type */
        $type = strtolower($this->getParams('name'));

        $manifestType = isset($manifest['type']) ? strtolower($manifest['type']) : $this->defaultPackageType;

        if (!in_array($manifestType, $this->packageTypes)) {
            $this->throwErrorAndRemovePackage('Unknown package type.');
        }

        if ($type != $manifestType) {
            $this->throwErrorAndRemovePackage(
                'Wrong package type. You cannot install '.$manifestType.' package via '.ucfirst($type).' Manager.'
            );
        }

        return true;
    }

    /**
     * @return string
     * @throws Error
     */
    protected function getPackageType()
    {
        $manifest = $this->getManifest();

        if (isset($manifest['type'])) {
            return strtolower($manifest['type']);
        }

        return $this->defaultPackageType;
    }

    /**
     * @param array<string, string[]> $dependencyList
     * @return bool
     */
    protected function checkDependencies($dependencyList)
    {
        return true;
    }

    /**
     * Run a script by a type.
     * @param string $type Ex. "before", "after".
     * @return void
     * @throws Error
     */
    protected function runScript($type)
    {
        $beforeInstallScript = $this->getScriptPath($type);

        if ($beforeInstallScript) {
            $scriptNames = $this->getParams('scriptNames');
            $scriptName = $scriptNames[$type];

            require_once($beforeInstallScript);

            $script = new $scriptName();

            try {
                assert(method_exists($script, 'run'));

                $script->run($this->getContainer(), $this->scriptParams);
            }
            catch (Throwable $e) {
                $this->throwErrorAndRemovePackage($e->getMessage());
            }
        }
    }


    /**
     * @param string $type
     * @return ?string
     * @throws Error
     */
    protected function getScriptPath($type)
    {
        $packagePath = $this->getPackagePath();
        $scriptNames = $this->getParams('scriptNames');

        $scriptName = $scriptNames[$type];

        if (!isset($scriptName)) {
            return null;
        }

        $beforeInstallScript = Util::concatPath(array($packagePath, self::SCRIPTS, $scriptName)) . '.php';

        if (file_exists($beforeInstallScript)) {
            return $beforeInstallScript;
        }

        return null;
    }

    /**
     * Get package path,
     *
     * @param string $name
     * @param bool $isPackage
     * @return string
     * @throws Error
     */
    protected function getPath($name = 'packagePath', $isPackage = false)
    {
        $postfix = $isPackage ? $this->packagePostfix : '';

        $processId = $this->getProcessId();
        $path = Util::concatPath($this->getParams($name), $processId);

        return $path . $postfix;
    }

    /**
     * @param bool $isPackage
     * @return string
     * @throws Error
     */
    protected function getPackagePath($isPackage = false)
    {
        return $this->getPath('packagePath', $isPackage);
    }

    /**
     * @param string $type
     * @return string[]
     * @throws Error
     */
    protected function getDeleteList($type = 'delete')
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
    protected function getDeleteFileList()
    {
        if (!isset($this->data['deleteFileList'])) {
            $deleteFileList = [];

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
     * @param string $type
     * @param bool $withEmptyDirs
     * @return bool
     * @throws Error
     */
    protected function deleteFiles($type = 'delete', $withEmptyDirs = false)
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
    protected function getCopyFileList()
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
    protected function getRestoreFileList()
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
     * @param ?string $parentDirPath
     * @return string[]
     */
    protected function getFileDirs($parentDirPath = null)
    {
        $dirNames = $this->getParams('customDirNames');

        $paths = array(self::FILES, $dirNames['before'], $dirNames['after']);

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
     * @param string $dirPath
     * @param bool $skipVendorFileList
     * @return string[]
     * @throws Error
     */
    protected function getFileList($dirPath, $skipVendorFileList = false)
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
            $vendorFileList = $this->getVendorFileList('copy');

            if (!empty($vendorFileList)) {
                /** @var string[] $fileList */
                $fileList = array_merge($fileList, $vendorFileList);
            }
        }

        return $fileList;
    }

    /**
     *
     * @param string $sourcePath
     * @param string $destPath
     * @param bool $recursively
     * @param string[] $fileList
     * @param bool $copyOnlyFiles
     * @return bool
     * @throws Error
     */
    protected function copy(
        $sourcePath,
        $destPath,
        $recursively = false,
        array $fileList = null,
        $copyOnlyFiles = false
    ) {
        try {
            return $this->getFileManager()->copy($sourcePath, $destPath, $recursively, $fileList, $copyOnlyFiles);
        }
        catch (Throwable $e) {
            $this->throwErrorAndRemovePackage($e->getMessage());
        }

        return false;
    }

    /**
     * Copy files from upgrade/extension package.
     *
     * @param ?string $type
     * @param string $dest
     * @return bool
     * @throws Error
     */
    protected function copyFiles($type = null, $dest = '')
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
     * @param string $type
     * @return string|null
     * @throws Error
     */
    protected function getCopyFilesPath($type = null)
    {
        switch ($type) {
            case 'before':
            case 'after':
                $dirNames = $this->getParams('customDirNames');

                $dirPath = $dirNames[$type];

                break;

            case 'vendor':
                $dirNames = $this->getParams('customDirNames');

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
     * @param string $type
     * @return string[]
     * @throws Error
     */
    protected function getVendorFileList($type = 'copy')
    {
        $list = [];

        $packagePath = $this->getPackagePath();
        $dirNames = $this->getParams('customDirNames');

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
    public function getManifest()
    {
        if (!isset($this->data['manifest'])) {
            $packagePath = $this->getPackagePath();

            $manifestPath = Util::concatPath($packagePath, $this->manifestName);

            if (!file_exists($manifestPath)) {
                $this->throwErrorAndRemovePackage('It\'s not an Installation package.');
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
     * @return void
     */
    protected function setManifest()
    {
    }

    /**
     * Check if the manifest is correct.
     *
     * @param array<string, mixed> $manifest
     * @return bool
     */
    protected function checkManifest(array $manifest)
    {
        $requiredFields = array(
            'name',
            'version',
        );

        foreach ($requiredFields as $fieldName) {
            if (empty($manifest[$fieldName])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     * @throws Error
     */
    protected function getManifestParam($name, $default = null)
    {
        $manifest = $this->getManifest();

        if (array_key_exists($name, $manifest)) {
            return $manifest[$name];
        }

        return $default;
    }

    /**
     * Unzip a package archive.
     *
     * @param ?string $packagePath
     * @return void
     * @throws Error
     */
    protected function unzipArchive($packagePath = null)
    {
        $packagePath = isset($packagePath) ? $packagePath : $this->getPackagePath();
        $packageArchivePath = $this->getPackagePath(true);

        if (!file_exists($packageArchivePath)) {
            $this->throwErrorAndRemovePackage('Package Archive doesn\'t exist.', false, false);
        }

        $res = $this->getZipUtil()->unzip($packageArchivePath, $packagePath);
        if ($res === false) {
            $this->throwErrorAndRemovePackage('Unable to unzip the file - '.$packagePath.'.', false, false);
        }
    }

    /**
     * Delete temporary package files.
     *
     * @return bool
     * @throws Error
     */
    protected function deletePackageFiles()
    {
        $packagePath = $this->getPackagePath();

        $res = $this->getFileManager()->removeInDir($packagePath, true);

        return $res;
    }

    /**
     * Delete temporary package archive.
     *
     * @return bool
     * @throws Error
     */
    protected function deletePackageArchive()
    {
        $packageArchive = $this->getPackagePath(true);

        $res = $this->getFileManager()->removeFile($packageArchive);

        return $res;
    }

    /**
     * @return bool
     */
    protected function systemRebuild()
    {
        try {
            /** @var \Espo\Core\DataManager $dataManager */
            $dataManager = $this->getContainer()->get('dataManager');

            $dataManager->rebuild();
            $dataManager->updateAppTimestamp();

            return true;
        }
        catch (Throwable $e) {

            try {
                $this->getLog()->error('Database rebuild failure, details: '. $e->getMessage() .'.');
            }
            catch (Throwable $e) {}
        }

        return false;
    }

    /**
     * Execute an action. For ex., execute uninstall action in install.
     *
     * @param string $actionName
     * @param string|array<string, mixed> $data
     * @return void
     * @throws Error
     */
    protected function executeAction($actionName, $data)
    {
        $actionManager = $this->getActionManager();

        $currentAction = $actionManager->getAction();

        $actionManager->setAction($actionName);
        $actionManager->run($data);

        $actionManager->setAction($currentAction);
    }

    /**
     * @return void
     */
    protected function initialize()
    {
    }

    /**
     * @return void
     */
    protected function finalize()
    {
    }

    /**
     * @return void
     */
    protected function beforeRunAction()
    {
    }

    /**
     * @return void
     */
    protected function afterRunAction()
    {
    }

    /**
     * @return void
     * @throws Error
     */
    protected function clearCache()
    {
        /** @var \Espo\Core\DataManager $dataManager */
        $dataManager = $this->getContainer()->get('dataManager');

        $dataManager->clearCache();
    }

    /**
     * @return void
     * @throws Error
     */
    protected function checkIsWritable()
    {
        $backupPath = $this->getPath('backupPath');
        $fullFileList = array_merge([$backupPath], $this->getDeleteFileList(), $this->getCopyFileList());

        $result = $this->getFileManager()->isWritableList($fullFileList);

        if (!$result) {
            $permissionDeniedList = $this->getFileManager()->getLastPermissionDeniedList();

            $delimiter = $this->isCli() ? "\n" : "<br>";

            $this->throwErrorAndRemovePackage(
                "Permission denied: " . $delimiter . implode($delimiter, $permissionDeniedList), false, false
            );
        }
    }

    /**
     * @return bool
     * @throws Error
     */
    protected function backupExistingFiles()
    {
        $fullFileList = array_merge($this->getDeleteFileList(), $this->getCopyFileList());

        $backupPath = $this->getPath('backupPath');

        $destination = Util::concatPath($backupPath, self::FILES);

        return $this->copy('', $destination, false, $fullFileList);
    }

    /**
     * @return \Espo\Core\Upgrades\Actions\Helper
     */
    protected function getHelper()
    {
        if (!isset($this->helper)) {
            $this->helper = new Helper();
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
    protected function concatStringWithArray($string, array $array)
    {
        foreach ($array as &$value) {
            if (substr($string, -1) != '/') {
                $string .= '/';
            }
            $value = $string . $value;
        }

        return $array;
    }

    /**
     * @return void
     * @throws Error
     */
    protected function enableMaintenanceMode()
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
            // @todo Maybe to romove this line?
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
     * @param bool $force
     * @return void
     * @throws Error
     */
    protected function disableMaintenanceMode($force = false)
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
     * @param bool $isParentProcess
     * @return ?string
     * @throws Error
     */
    protected function getTemporaryConfigParamName($isParentProcess = false)
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

    /**
     * @return bool
     */
    protected function isCli()
    {
        if (substr(php_sapi_name() ?: '', 0, 3) === 'cli') {
            return true;
        }

        return false;
    }
}
