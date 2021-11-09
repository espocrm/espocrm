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

namespace Espo\Core\Upgrades\Actions;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\System;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;

use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Core\{
    Container,
    Upgrades\ActionManager,
    Utils\File\ZipArchive,
    Utils\Config\ConfigWriter,
    Utils\Database\Helper as DatabaseHelper,
    Utils\Log,
};

use Composer\Semver\Semver;

use Throwable;

abstract class Base
{
    private $helper;

    protected $data;

    protected $params = null;

    private $container;

    private $actionManager;

    private $zipUtil;

    private $databaseHelper;

    protected $processId = null;

    protected $parentProcessId = null;

    protected $manifestName = 'manifest.json';

    protected $packagePostfix = 'z';

    protected $scriptParams = [];

    /**
     * Directory name of files in a package
     */
    const FILES = 'files';

    /**
     * Directory name of scripts in a package
     */
    const SCRIPTS = 'scripts';

    /**
     * Package types
     */
    protected $packageTypes = array(
        'upgrade' => 'upgrade',
        'extension' => 'extension',
    );

    /**
     * Default package type
     */
    protected $defaultPackageType = 'extension';

    protected $vendorDirName = 'vendor';

    public function __construct(Container $container, ActionManager $actionManager)
    {
        $this->container = $container;
        $this->actionManager = $actionManager;
        $this->params = $actionManager->getParams();

        $this->zipUtil = new ZipArchive($container->get('fileManager'));
    }

    public function __destruct()
    {
        $this->processId = null;
        $this->data = null;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getActionManager()
    {
        return $this->actionManager;
    }

    protected function getParams($name, $returns = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return $returns;
    }

    protected function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    protected function getZipUtil()
    {
        return $this->zipUtil;
    }

    protected function getDatabaseHelper()
    {
        if (!isset($this->databaseHelper)) {
            $this->databaseHelper = new DatabaseHelper($this->getConfig());
        }

        return $this->databaseHelper;
    }

    protected function getLog(): Log
    {
        return $this->getContainer()->get('log');
    }

    /**
     * @return FileManager
     */
    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    public function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    public function createConfigWriter(): ConfigWriter
    {
        return $this->getContainer()->get('injectableFactory')->create(ConfigWriter::class);
    }

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

    abstract public function run($data);

    protected function createProcessId()
    {
        if (isset($this->processId)) {
            throw new Error('Another installation process is currently running.');
        }

        $this->processId = Util::generateId();

        return $this->processId;
    }

    protected function getProcessId()
    {
        if (!isset($this->processId)) {
            throw new Error('Installation ID was not specified.');
        }

        return $this->processId;
    }

    protected function getParentProcessId()
    {
        return $this->parentProcessId;
    }

    public function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    public function setParentProcessId($processId)
    {
        $this->parentProcessId = $processId;
    }

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM
     *
     * @return bool
     */
    protected function isAcceptable()
    {
        $manifest = $this->getManifest();

        $res = $this->checkPackageType();

        //check php version
        if (isset($manifest['php'])) {
            $res &= $this->checkVersions(
                $manifest['php'], System::getPhpVersion(),
                'Your PHP version ({version}) is not supported. Required version: {requiredVersion}.'
            );
        }

        //check database version
        if (isset($manifest['database'])) {
            $databaseHelper = $this->getDatabaseHelper();
            $databaseType = $databaseHelper->getDatabaseType();
            $databaseTypeLc = strtolower($databaseType);

            if (isset($manifest['database'][$databaseTypeLc])) {
                $databaseVersion = $databaseHelper->getDatabaseVersion();

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

        //check acceptableVersions
        if (isset($manifest['acceptableVersions'])) {
            $res &= $this->checkVersions(
                $manifest['acceptableVersions'],
                $this->getConfig()->get('version'),
                'Your EspoCRM version ({version}) is not supported. Required version: {requiredVersion}.'
            );
        }

        //check dependencies
        if (!empty($manifest['dependencies'])) {
            $res &= $this->checkDependencies($manifest['dependencies']);
        }

        return (bool) $res;
    }

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

        $errorMessage = preg_replace('/\{version\}/', $currentVersion, $errorMessage);
        $errorMessage = preg_replace('/\{requiredVersion\}/', $version, $errorMessage);

        $this->throwErrorAndRemovePackage($errorMessage);
    }

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

    protected function getPackageType()
    {
        $manifest = $this->getManifest();

        if (isset($manifest['type'])) {
            return strtolower($manifest['type']);
        }

        return $this->defaultPackageType;
    }

    protected function checkDependencies($dependencyList)
    {
        return true;
    }

    /**
     * Run a script by a type
     * @param  string $type Ex. "before", "after"
     * @return void
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
                $script->run($this->getContainer(), $this->scriptParams);
            }
            catch (Throwable $e) {
                $this->throwErrorAndRemovePackage($e->getMessage());
            }
        }
    }

    protected function getScriptPath($type)
    {
        $packagePath = $this->getPackagePath();
        $scriptNames = $this->getParams('scriptNames');

        $scriptName = $scriptNames[$type];

        if (!isset($scriptName)) {
            return;
        }

        $beforeInstallScript = Util::concatPath(array($packagePath, self::SCRIPTS, $scriptName)) . '.php';

        if (file_exists($beforeInstallScript)) {
            return $beforeInstallScript;
        }
    }

    /**
     * Get package path
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name = 'packagePath', $isPackage = false)
    {
        $postfix = $isPackage ? $this->packagePostfix : '';

        $processId = $this->getProcessId();
        $path = Util::concatPath($this->getParams($name), $processId);

        return $path . $postfix;
    }

    protected function getPackagePath($isPackage = false)
    {
        return $this->getPath('packagePath', $isPackage);
    }

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
     * Get a list of files defined in manifest.json
     *
     * @return array
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

            foreach ($deleteList as $key => $itemPath) {
                if (is_dir($itemPath)) {
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
     * @return bool
     */
    protected function deleteFiles($type = 'delete', $withEmptyDirs = false)
    {
        $deleteList = $this->getDeleteList($type);

        if (!empty($deleteList)) {
            return $this->getFileManager()->remove($deleteList, null, $withEmptyDirs);
        }

        return true;
    }

    protected function getCopyFileList()
    {
        if (!isset($this->data['fileList'])) {
            $packagePath = $this->getPackagePath();

            $this->data['fileList'] = $this->getFileList($packagePath);
        }

        return $this->data['fileList'];
    }

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
     * @param string $parentDirPath
     * @return array
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
     * @param  string $dirPath
     * @return array
     */
    protected function getFileList($dirPath, $skipVendorFileList = false)
    {
        $fileList = array();

        $paths = $this->getFileDirs($dirPath);

        foreach ($paths as $filesPath) {
            if (file_exists($filesPath)) {
                $files = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
                $fileList = array_merge($fileList, $files);
            }
        }

        if (!$skipVendorFileList) {
            $vendorFileList = $this->getVendorFileList('copy');
            if (!empty($vendorFileList)) {
                $fileList = array_merge($fileList, $vendorFileList);
            }
        }

        return $fileList;
    }

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
     * @param  string $type
     * @return boolean
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
                $list = $this->getFileManager()->getFileList($filesPath, true, '', true, true);

                break;

            case 'delete':
                $list = $this->getFileManager()->getFileList($filesPath, false, '', null, true);

                break;
        }

        foreach ($list as &$path) {
            $path = Util::concatPath($this->vendorDirName, $path);
        }

        return $list;
    }

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

    protected function setManifest()
    {

    }

    /**
     * Check if the manifest is correct.
     *
     * @param  array  $manifest
     * @return boolean
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
     * @return void
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
     */
    protected function deletePackageArchive()
    {
        $packageArchive = $this->getPackagePath(true);

        $res = $this->getFileManager()->removeFile($packageArchive);

        return $res;
    }

    protected function systemRebuild()
    {
        try {
            $this->getContainer()->get('dataManager')->rebuild();

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
     * @param string|array<string,mixed> $data
     * @return void
     */
    protected function executeAction($actionName, $data)
    {
        $actionManager = $this->getActionManager();

        $currentAction = $actionManager->getAction();

        $actionManager->setAction($actionName);
        $actionManager->run($data);

        $actionManager->setAction($currentAction);
    }

    protected function initialize()
    {

    }

    protected function finalize()
    {

    }

    protected function beforeRunAction()
    {

    }

    protected function afterRunAction()
    {

    }

    protected function clearCache()
    {
        return $this->getContainer()->get('dataManager')->clearCache();
    }

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

    protected function backupExistingFiles()
    {
        $fullFileList = array_merge($this->getDeleteFileList(), $this->getCopyFileList());

        $backupPath = $this->getPath('backupPath');

        $destination = Util::concatPath($backupPath, self::FILES);

        return $this->copy('', $destination, false, $fullFileList);
    }

    protected function getHelper()
    {
        if (!isset($this->helper)) {
            $this->helper = new Helper();
        }

        $this->helper->setActionObject($this);

        return $this->helper;
    }

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

    protected function enableMaintenanceMode()
    {
        $config = $this->getConfig();
        $configWriter = $this->createConfigWriter();

        $configParamName = $this->getTemporaryConfigParamName();
        $parentConfigParamName = $this->getTemporaryConfigParamName(true);

        if ($config->has($configParamName) || ($parentConfigParamName && $config->has($parentConfigParamName))) {
            return;
        }

        $actualParams = [
            'maintenanceMode' => $config->get('maintenanceMode'),
            'cronDisabled' => $config->get('cronDisabled'),
            'useCache' => $config->get('useCache'),
        ];

        // @todo Maybe to romove this line?
        $configWriter->set($configParamName, $actualParams);

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

    protected function getTemporaryConfigParamName($isParentProcess = false)
    {
        $processId = $this->getProcessId();

        if ($isParentProcess) {
            $processId = $this->getParentProcessId();

            if (!$processId) {
                return;
            }
        }

        return 'temporaryUpgradeParams' . $processId;
    }

    protected function isCli()
    {
        if (substr(php_sapi_name(), 0, 3) == 'cli') {
            return true;
        }

        return false;
    }
}
