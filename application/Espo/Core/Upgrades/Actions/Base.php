<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
use Composer\Semver\Semver;

abstract class Base
{
    private $config;

    private $entityManager;

    private $helper;

    protected $data;

    protected $params = null;

    private $container;

    private $actionManager;

    private $zipUtil;

    private $fileManager;

    protected $processId = null;

    protected $manifestName = 'manifest.json';

    protected $packagePostfix = 'z';

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


    public function __construct(\Espo\Core\Container $container, \Espo\Core\Upgrades\ActionManager $actionManager)
    {
        $this->container = $container;
        $this->actionManager = $actionManager;
        $this->params = $actionManager->getParams();

        $this->zipUtil = new \Espo\Core\Utils\File\ZipArchive($container->get('fileManager'));
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

    protected function getParams($name = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return $this->params;
    }

    protected function getZipUtil()
    {
        return $this->zipUtil;
    }

    protected function getFileManager()
    {
        if (!isset($this->fileManager)) {
            $this->fileManager = $this->getContainer()->get('fileManager');
        }
        return $this->fileManager;
    }

    protected function getConfig()
    {
        if (!isset($this->config)) {
            $this->config = $this->getContainer()->get('config');
        }
        return $this->config;
    }

    public function getEntityManager()
    {
        if (!isset($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('entityManager');
        }
        return $this->entityManager;
    }

    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        $this->deletePackageFiles();
        $this->deletePackageArchive();
        throw new Error($errorMessage);
    }

    abstract public function run($data);

    protected function createProcessId()
    {
        if (isset($this->processId)) {
            throw new Error('Another installation process is currently running.');
        }

        $this->processId = uniqid();

        return $this->processId;
    }

    protected function getProcessId()
    {
        if (!isset($this->processId)) {
            throw new Error('Installation ID was not specified.');
        }

        return $this->processId;
    }

    protected function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM
     *
     * @param  string  $version
     * @return boolean
     */
    protected function isAcceptable()
    {
        $manifest = $this->getManifest();

        $res = $this->checkPackageType();

        //check php version
        if (isset($manifest['php'])) {
            $res &= $this->checkVersions($manifest['php'], System::getPhpVersion(), 'Your PHP version does not support this installation package.');
        }

        //check acceptableVersions
        if (isset($manifest['acceptableVersions'])) {
            $res &= $this->checkVersions($manifest['acceptableVersions'], $this->getConfig()->get('version'), 'Your EspoCRM version doesn\'t match for this installation package.');
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

        foreach ($versionList as $version) {
            $isInRange = false;
            try {
                $isInRange = Semver::satisfies($currentVersion, $version);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('SemVer: Version identification error: '.$e->getMessage().'.');
            }

            if ($isInRange) {
                return true;
            }
        }

        $this->throwErrorAndRemovePackage($errorMessage);
    }

    protected function checkPackageType()
    {
        $manifest = $this->getManifest();

        /** check package type */
        $type = strtolower( $this->getParams('name') );
        $manifestType = isset($manifest['type']) ? strtolower($manifest['type']) : $this->defaultPackageType;

        if (!in_array($manifestType, $this->packageTypes)) {
            $this->throwErrorAndRemovePackage('Unknown package type.');
        }

        if ($type != $manifestType) {
            $this->throwErrorAndRemovePackage('Wrong package type. You cannot install '.$manifestType.' package via '.ucfirst($type).' Manager.');
        }

        return true;
    }

    protected function checkDependencies($dependencyList)
    {
        return true;
    }

    /**
     * Run scripts by type
     * @param  string $type Ex. "before", "after"
     * @return void
     */
    protected function runScript($type)
    {
        $packagePath = $this->getPackagePath();
        $scriptNames = $this->getParams('scriptNames');

        $scriptName = $scriptNames[$type];
        if (!isset($scriptName)) {
            return;
        }

        $beforeInstallScript = Util::concatPath( array($packagePath, self::SCRIPTS, $scriptName) ) . '.php';

        if (file_exists($beforeInstallScript)) {
            require_once($beforeInstallScript);
            $script = new $scriptName();

            try {
                $script->run($this->getContainer());
            } catch (\Exception $e) {
                $this->throwErrorAndRemovePackage($e->getMessage());
            }
        }
    }

    /**
     * Get package path
     *
     * @param  string $processId
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

    /**
     * Get a list of files defined in manifest.json
     *
     * @return array
     */
    protected function getDeleteFileList()
    {
        $manifest = $this->getManifest();

        if (!empty($manifest['delete'])) {
            return $manifest['delete'];
        }

        return array();
    }

    /**
     * Delete files defined in a manifest
     *
     * @return boolen
     */
    protected function deleteFiles($withEmptyDirs = false)
    {
        $deleteFileList = $this->getDeleteFileList();

        //remove directories, leave only files
        foreach ($deleteFileList as $key => $filePath) {
            if (!is_file($filePath)) {
                unset($deleteFileList[$key]);
            }
        }

        if (!empty($deleteFileList)) {
            return $this->getFileManager()->remove($deleteFileList, null, $withEmptyDirs);
        }

        return true;
    }

    protected function getCopyFileList()
    {
        if (!isset($this->data['fileList'])) {
            $packagePath = $this->getPackagePath();
            $filesPath = Util::concatPath($packagePath, self::FILES);

            $this->data['fileList'] = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
        }

        return $this->data['fileList'];
    }

    protected function getRestoreFileList()
    {
        if (!isset($this->data['restoreFileList'])) {
            $backupPath = $this->getPath('backupPath');
            $backupFilePath = Util::concatPath($backupPath, self::FILES);

            $this->data['restoreFileList'] = $this->getFileManager()->getFileList($backupFilePath, true, '', true, true);
        }

        return $this->data['restoreFileList'];
    }

    protected function copy($sourcePath, $destPath, $recursively = false, array $fileList = null, $copyOnlyFiles = false)
    {
        try {
            $res = $this->getFileManager()->copy($sourcePath, $destPath, $recursively, $fileList, $copyOnlyFiles);
        } catch (\Exception $e) {
            $this->throwErrorAndRemovePackage($e->getMessage());
        }

        return $res;
    }

    /**
     * Copy files from upgrade/extension package
     *
     * @param  string $processId
     * @return boolean
     */
    protected function copyFiles()
    {
        $packagePath = $this->getPackagePath();
        $filesPath = Util::concatPath($packagePath, self::FILES);

        return $this->copy($filesPath, '', true);
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

    /**
     * Check if the manifest is correct
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

    /**
     * Unzip a package archieve
     *
     * @return void
     */
    protected function unzipArchive($packagePath = null)
    {
        $packagePath = isset($packagePath) ? $packagePath : $this->getPackagePath();
        $packageArchivePath = $this->getPackagePath(true);

        if (!file_exists($packageArchivePath)) {
            throw new Error('Package Archive doesn\'t exist.');
        }

        $res = $this->getZipUtil()->unzip($packageArchivePath, $packagePath);
        if ($res === false) {
            throw new Error('Unnable to unzip the file - '.$packagePath.'.');
        }
    }

    /**
     * Delete temporary package files
     *
     * @return boolean
     */
    protected function deletePackageFiles()
    {
        $packagePath = $this->getPackagePath();
        $res = $this->getFileManager()->removeInDir($packagePath, true);

        return $res;
    }

    /**
     * Delete temporary package archive
     *
     * @return boolean
     */
    protected function deletePackageArchive()
    {
        $packageArchive = $this->getPackagePath(true);
        $res = $this->getFileManager()->removeFile($packageArchive);

        return $res;
    }

    protected function systemRebuild()
    {
        return $this->getContainer()->get('dataManager')->rebuild();
    }

    /**
     * Execute an action. For ex., execute uninstall action in install
     *
     * @param  string $actionName
     * @param  string $data
     *
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
        $fullFileList = array_merge($this->getDeleteFileList(), $this->getCopyFileList());

        $result = $this->getFileManager()->isWritableList($fullFileList);
        if (!$result) {
            $permissionDeniedList = $this->getFileManager()->getLastPermissionDeniedList();
            throw new Error("Permission denied for <br>". implode(", <br>", $permissionDeniedList));
        }
    }

    protected function backupExistingFiles()
    {
        $fullFileList = array_merge($this->getDeleteFileList(), $this->getCopyFileList());

        $backupPath = $this->getPath('backupPath');
        return $this->copy('', array($backupPath, self::FILES), false, $fullFileList);
    }

    protected function getHelper()
    {
        if (!isset($this->helper)) {
            $this->helper = new Helper();
        }

        $this->helper->setActionObject($this);

        return $this->helper;
    }
}