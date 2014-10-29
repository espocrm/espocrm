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
namespace Espo\Core\Upgrades\Actions\Base;

use Espo\Core\Exceptions\Error;
use Espo\Core\Upgrades\Actions\Base;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Util;
use Espo\Entities\Extension;

class Uninstall extends
    Base
{

    public function run($processId)
    {
        /**
         * @var Log $log
         */
        $log = $GLOBALS['log'];
        $log->debug('Uninstallation process [' . $processId . ']: start run.');
        if (empty($processId)) {
            throw new Error('Uninstallation package ID was not specified.');
        }
        $this->setProcessId($processId);
        $this->beforeRunAction();
        /* run before install script */
        $this->runScript('beforeUninstall');
        $backupPath = $this->getPath('backupPath');
        if (file_exists($backupPath)) {
            /* remove extension files, saved in fileList */
            if (!$this->deleteFiles()) {
                throw new Error('Permission denied to delete files.');
            }
            /* copy core files */
            if (!$this->copyFiles()) {
                throw new Error('Cannot copy files.');
            }
        }
        if (!$this->systemRebuild()) {
            throw new Error('Error occurred while EspoCRM rebuild.');
        }
        /* run before install script */
        $this->runScript('afterUninstall');
        $this->afterRunAction();
        /* delete backup files */
        $this->deletePackageFiles();
        $log->debug('Uninstallation process [' . $processId . ']: end run.');
    }

    protected function copyFiles()
    {
        $backupPath = $this->getPath('backupPath');
        $res = $this->copy(array($backupPath, self::FILES), '', true);
        return $res;
    }

    protected function deletePackageFiles()
    {
        /**
         * @var Manager $fileManager
         */
        $backupPath = $this->getPath('backupPath');
        $fileManager = $this->getFileManager();
        $res = $fileManager->removeInDir($backupPath, true);
        return $res;
    }

    protected function getDeleteFileList()
    {
        /**
         * @var Extension $extensionEntity
         */
        /** @noinspection PhpUndefinedMethodInspection */
        $extensionEntity = $this->getExtensionEntity();
        return $extensionEntity->get('fileList');
    }

    /**
     * Get backup path
     *
     * @param bool $isPackage
     *
     * @return string
     */
    protected function getPackagePath($isPackage = false)
    {
        if ($isPackage) {
            return $this->getPath('packagePath', $isPackage);
        }
        return $this->getPath('backupPath');
    }

    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        $this->restoreFiles();
        throw new Error($errorMessage);
    }

    protected function restoreFiles()
    {
        /**
         * @var Manager $fileManager
         */
        $packagePath = $this->getPath('packagePath');
        $filesPath = Util::concatPath($packagePath, self::FILES);
        if (!file_exists($filesPath)) {
            $this->unzipArchive($packagePath);
        }
        $res = $this->copy($filesPath, '', true);
        $fileManager = $this->getFileManager();
        $res &= $fileManager->removeInDir($packagePath, true);
        return $res;
    }
}
