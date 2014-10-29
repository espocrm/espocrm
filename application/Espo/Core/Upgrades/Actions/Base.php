<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/
namespace Espo\Core\Upgrades\Actions;

use Espo\Core\Container;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Upgrades\ActionManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\File\ZipArchive;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;

abstract class Base
{

    /**
     * Directory name of files in a package
     */
    const FILES = 'files';
    /**
     * Directory name of scripts in a package
     */
    const SCRIPTS = 'scripts';

    protected $data;

    protected $params = null;

    protected $processId = null;

    protected $manifestName = 'manifest.json';

    protected $packagePostfix = 'z';

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

    private $container;

    private $actionManager;

    private $zipUtil;

    private $fileManager;

    private $config;

    private $entityManager;

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

    abstract public function run($data);

    /**
     * @return EntityManager

     */
    protected function getEntityManager()
    {
        if (!isset($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('entityManager');
        }
        return $this->entityManager;
    }

    protected function createProcessId()
    {
        if (isset($this->processId)) {
            throw new Error('Another installation process is currently running.');
        }
        $this->processId = uniqid();
        return $this->processId;
    }

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM
     *
     * @internal param string $version
     *
     * @return boolean
     */
    protected function isAcceptable()
    {
        $res = $this->checkPackageType();
        $res &= $this->checkVersions();
        return (bool)$res;
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
            $this->throwErrorAndRemovePackage('Wrong package type. You cannot install ' . $manifestType . ' package via ' . ucfirst($type) . ' Manager.');
        }
        return true;
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

    protected function getPackagePath($isPackage = false)
    {
        return $this->getPath('packagePath', $isPackage);
    }

    /**
     * Get package path
     *
     * @param string $name
     * @param bool   $isPackage
     *
     * @throws Error
     * @internal param string $processId
     *
     * @return string
     */
    protected function getPath($name = 'packagePath', $isPackage = false)
    {
        $postfix = $isPackage ? $this->packagePostfix : '';
        $processId = $this->getProcessId();
        $path = Util::concatPath($this->getParams($name), $processId);
        return $path . $postfix;
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

    protected function getParams($name = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return $this->params;
    }

    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        $this->deletePackageFiles();
        $this->deletePackageArchive();
        throw new Error($errorMessage);
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
     * @return Manager

     */
    protected function getFileManager()
    {
        if (!isset($this->fileManager)) {
            $this->fileManager = $this->getContainer()->get('fileManager');
        }
        return $this->fileManager;
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

    /**
     * Check if the manifest is correct
     *
     * @param  array $manifest
     *
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

    protected function checkVersions()
    {
        $manifest = $this->getManifest();
        /** check acceptable versions */
        $version = $manifest['acceptableVersions'];
        if (empty($version)) {
            return true;
        }
        $currentVersion = $this->getConfig()->get('version');
        if (is_string($version)) {
            $version = (array)$version;
        }
        foreach ($version as $strVersion) {
            $strVersion = trim($strVersion);
            if ($strVersion == $currentVersion) {
                return true;
            }
            $strVersion = str_replace('\\', '', $strVersion);
            $strVersion = preg_quote($strVersion);
            $strVersion = str_replace('\\*', '+', $strVersion);
            if (preg_match('/^' . $strVersion . '/', $currentVersion)) {
                return true;
            }
        }
        $this->throwErrorAndRemovePackage('Your EspoCRM version doesn\'t match for this installation package.');
    }

    /**
     * @return Config

     */
    protected function getConfig()
    {
        if (!isset($this->config)) {
            $this->config = $this->getContainer()->get('config');
        }
        return $this->config;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Run scripts by type
     *
     * @param  string $type Ex. "before", "after"
     *
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
        $beforeInstallScript = Util::concatPath(array($packagePath, self::SCRIPTS, $scriptName)) . '.php';
        if (file_exists($beforeInstallScript)) {
            /** @noinspection PhpIncludeInspection */
            require_once($beforeInstallScript);
            $script = new $scriptName();
            try{
                /** @noinspection PhpUndefinedMethodInspection */
                $script->run($this->getContainer());
            } catch(\Exception $e){
                $this->throwErrorAndRemovePackage($e->getMessage());
            }
        }
    }

    /**
     * Delete files defined in a manifest
     *
     * @return boolean
     */
    protected function deleteFiles()
    {
        $deleteFileList = $this->getDeleteFileList();
        if (!empty($deleteFileList)) {
            return $this->getFileManager()->remove($deleteFileList);
        }
        return true;
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

    protected function getCopyFileList()
    {
        if (!isset($this->data['fileList'])) {
            $packagePath = $this->getPackagePath();
            $filesPath = Util::concatPath($packagePath, self::FILES);
            $this->data['fileList'] = $this->getFileManager()->getFileList($filesPath, true, '', 'all', true);
        }
        return $this->data['fileList'];
    }

    /**
     * Copy files from upgrade/extension package
     *
     * @internal param string $processId
     *
     * @return boolean
     */
    protected function copyFiles()
    {
        $packagePath = $this->getPackagePath();
        $filesPath = Util::concatPath($packagePath, self::FILES);
        return $this->copy($filesPath, '', true);
    }

    protected function copy(
        $sourcePath,
        $destPath,
        $recursively = false,
        array $fileList = null,
        $copyOnlyFiles = false
    ){
        $res = false;
        try{
            $res = $this->getFileManager()->copy($sourcePath, $destPath, $recursively, $fileList, $copyOnlyFiles);
        } catch(\Exception $e){
            $this->throwErrorAndRemovePackage($e->getMessage());
        }
        return $res;
    }

    /**
     * Unzip a package archieve
     *
     * @param null $packagePath
     *
     * @throws Error
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
            throw new Error('Unnable to unzip the file - ' . $packagePath . '.');
        }
    }

    protected function getZipUtil()
    {
        return $this->zipUtil;
    }

    protected function systemRebuild()
    {
        /**
         * @var DataManager $dataManager
         */
        $dataManager = $this->getContainer()->get('dataManager');
        return $dataManager->rebuild();
    }

    /**
     * Execute an action. For ex., execute uninstall action in install
     *
     * @param   $actionName [description]
     * @param   $data       [description]
     *
     */
    protected function executeAction($actionName, $data)
    {
        $currentAction = $this->getActionManager()->getAction();
        $this->getActionManager()->setAction($actionName);
        $this->getActionManager()->run($data);
        $this->getActionManager()->setAction($currentAction);
    }

    /**
     * @return ActionManager

     */
    protected function getActionManager()
    {
        return $this->actionManager;
    }

    protected function beforeRunAction()
    {
    }

    protected function afterRunAction()
    {
    }
}
