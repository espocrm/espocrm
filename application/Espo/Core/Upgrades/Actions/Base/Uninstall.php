<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Upgrades\Actions\Base;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;

class Uninstall extends \Espo\Core\Upgrades\Actions\Base
{
    public function run($data)
    {
        $processId = $data['id'];

        $GLOBALS['log']->debug('Uninstallation process ['.$processId.']: start run.');

        if (empty($processId)) {
            throw new Error('Uninstallation package ID was not specified.');
        }

        $this->setProcessId($processId);

        $this->initialize();

        $this->checkIsWritable();

        $this->enableMaintenanceMode();

        $this->beforeRunAction();

        /* run before uninstall script */
        if (!isset($data['skipBeforeScript']) || !$data['skipBeforeScript']) {
            $this->runScript('beforeUninstall');
        }

        $backupPath = $this->getPath('backupPath');
        if (file_exists($backupPath)) {
            /* copy core files */
            if (!$this->copyFiles()) {
                $this->throwErrorAndRemovePackage('Cannot copy files.');
            }
        }

        /* remove extension files, saved in fileList */
        if (!$this->deleteFiles('delete', true)) {
            $this->throwErrorAndRemovePackage('Permission denied to delete files.');
        }

        $this->disableMaintenanceMode();

        if (!isset($data['skipSystemRebuild']) || !$data['skipSystemRebuild']) {
            if (!$this->systemRebuild()) {
                $this->throwErrorAndRemovePackage('Error occurred while EspoCRM rebuild.');
            }
        }

        /* run after uninstall script */
        if (!isset($data['skipAfterScript']) || !$data['skipAfterScript']) {
            $this->runScript('afterUninstall');
        }

        $this->afterRunAction();

        /* delete backup files */
        $this->deletePackageFiles();

        $this->finalize();

        $GLOBALS['log']->debug('Uninstallation process ['.$processId.']: end run.');

        $this->clearCache();
    }

    protected function restoreFiles()
    {
        $packagePath = $this->getPath('packagePath');

        $manifestPath = Util::concatPath($packagePath, $this->manifestName);
        if (!file_exists($manifestPath)) {
            $this->unzipArchive($packagePath);
        }

        $fileDirs = $this->getFileDirs($packagePath);
        foreach ($fileDirs as $filesPath) {
            if (file_exists($filesPath)) {
                $res = $this->copy($filesPath, '', true);
            }
        }

        $manifestJson = $this->getFileManager()->getContents($manifestPath);
        $manifest = Json::decode($manifestJson, true);
        if (!empty($manifest['delete'])) {
            $res &= $this->getFileManager()->remove($manifest['delete'], null, true);
        }

        $res &= $this->getFileManager()->removeInDir($packagePath, true);

        return $res;
    }

    protected function copyFiles($type = null, $dest = '')
    {
        $backupPath = $this->getPath('backupPath');
        $res = $this->copy(array($backupPath, self::FILES), $dest, true);

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

    protected function getCopyFileList()
    {
        if (!isset($this->data['fileList'])) {
            $backupPath = $this->getPath('backupPath');
            $filesPath = Util::concatPath($backupPath, self::FILES);

            $this->data['fileList'] = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
        }

        return $this->data['fileList'];
    }

    protected function getRestoreFileList()
    {
        if (!isset($this->data['restoreFileList'])) {
            $packagePath = $this->getPackagePath();
            $filesPath = Util::concatPath($packagePath, self::FILES);

            if (!file_exists($filesPath)) {
                $this->unzipArchive($packagePath);
            }

            $this->data['restoreFileList'] = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
        }

        return $this->data['restoreFileList'];
    }

    protected function getDeleteList($type = 'delete')
    {
        if ($type == 'delete') {
            $packageFileList = $this->getRestoreFileList();
            $backupFileList = $this->getCopyFileList();

            return array_diff($packageFileList, $backupFileList);
        }

        return array();
    }
}
