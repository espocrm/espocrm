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

use Espo\Core\Exceptions\Error,
    Espo\Core\Utils\Util;

class Uninstall extends \Espo\Core\Upgrades\Actions\Base
{
    public function run($processId)
    {
        $GLOBALS['log']->debug('Uninstallation process ['.$processId.']: start run.');

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

        $GLOBALS['log']->debug('Uninstallation process ['.$processId.']: end run.');
    }

    protected function getDeleteFileList()
    {
        $extensionEntity = $this->getExtensionEntity();
        return $extensionEntity->get('fileList');
    }

    protected function restoreFiles()
    {
        $packagePath = $this->getPath('packagePath');
        $filesPath = Util::concatPath($packagePath, self::FILES);

        if (!file_exists($filesPath)) {
            $this->unzipArchive($packagePath);
        }

        $res = $this->copy($filesPath, '', true);
        $res &= $this->getFileManager()->removeInDir($packagePath, true);

        return $res;
    }

    protected function copyFiles()
    {
        $backupPath = $this->getPath('backupPath');
        $res = $this->copy(array($backupPath, self::FILES), '', true);

        return $res;
    }

    /**
     * Get backup path
     *
     * @param  string $processId
     * @return string
     */
    protected function getPackagePath($isPackage = false)
    {
        if ($isPackage) {
            return $this->getPath('packagePath', $isPackage);
        }

        return $this->getPath('backupPath');
    }

    protected function deletePackageFiles()
    {
        $backupPath = $this->getPath('backupPath');
        $res = $this->getFileManager()->removeInDir($backupPath, true);

        return $res;
    }

    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        $this->restoreFiles();
        throw new Error($errorMessage);
    }

}
