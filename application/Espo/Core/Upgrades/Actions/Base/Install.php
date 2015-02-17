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
 ************************************************************************/

namespace Espo\Core\Upgrades\Actions\Base;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Util;

class Install extends \Espo\Core\Upgrades\Actions\Base
{
    /**
     * Is copied extension files to Espo
     *
     * @var [type]
     */
    protected $isCopied = null;

    /**
     * Main installation process
     *
     * @param  string $processId Upgrade/Extension ID, gotten in upload stage
     * @return bool
     */
    public function run($processId)
    {
        $GLOBALS['log']->debug('Installation process ['.$processId.']: start run.');

        if (empty($processId)) {
            throw new Error('Installation package ID was not specified.');
        }

        $this->setProcessId($processId);

        $this->isCopied = false;

        /** check if an archive is unzipped, if no then unzip */
        $packagePath = $this->getPackagePath();
        if (!file_exists($packagePath)) {
            $this->unzipArchive();
            $this->isAcceptable();
        }

        //check permissions copied and deleted files
        $this->checkIsWritable();

        $this->backupExistingFiles();

        /* run before install script */
        $this->runScript('before');

        $this->beforeRunAction();

        /* copy files from directory "Files" to EspoCRM files */
        if (!$this->copyFiles()) {
            $this->throwErrorAndRemovePackage('Cannot copy files.');
        }
        $this->isCopied = true;

        /* remove files defined in a manifest */
        $this->deleteFiles();

        if (!$this->systemRebuild()) {
            $this->throwErrorAndRemovePackage('Error occurred while EspoCRM rebuild.');
        }

        $this->afterRunAction();

        /* run before install script */
        $this->runScript('after');

        $this->clearCache();

        /* delete unziped files */
        $this->deletePackageFiles();

        $GLOBALS['log']->debug('Installation process ['.$processId.']: end run.');
    }

    protected function restoreFiles()
    {
        if (!$this->isCopied) {
            return;
        }

        $GLOBALS['log']->info('Installer: Restore previous files.');

        $backupPath = $this->getPath('backupPath');
        $backupFilePath = Util::concatPath($backupPath, self::FILES);

        $backupFileList = $this->getRestoreFileList();
        $copyFileList = $this->getCopyFileList();
        $deleteFileList = array_diff($copyFileList, $backupFileList);

        $res = $this->copy($backupFilePath, '', true);
        $res &= $this->getFileManager()->remove($deleteFileList, null, true);

        if ($res) {
            $this->getFileManager()->removeInDir($backupPath, true);
        }

        return $res;
    }

    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        $this->restoreFiles();
        parent::throwErrorAndRemovePackage($errorMessage);
    }
}
