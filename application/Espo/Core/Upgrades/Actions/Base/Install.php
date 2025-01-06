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

namespace Espo\Core\Upgrades\Actions\Base;

use Espo\Core\Exceptions\Error;
use Espo\Core\Upgrades\Actions\Base;
use Espo\Core\Utils\Util;
use Throwable;

class Install extends Base
{
    /**
     * Main installation process.
     *
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function run(mixed $data): mixed
    {
        $processId = $data['id'];

        $this->getLog()->debug("Installation process [$processId]: start run.");

        $this->stepInit($data);
        $this->stepCopyBefore($data);

        if ($this->getCopyFilesPath('before')) {
            $this->stepRebuild($data);
        }

        $this->stepBeforeInstallScript($data);

        if ($this->getScriptPath('before')) {
            $this->stepRebuild($data);
        }

        $this->stepCopy($data);
        $this->stepRebuild($data);

        $this->stepCopyAfter($data);

        if ($this->getCopyFilesPath('after')) {
            $this->stepRebuild($data);
        }

        $this->stepAfterInstallScript($data);
        if ($this->getScriptPath('after')) {
            $this->stepRebuild($data);
        }

        $this->stepFinalize($data);

        $this->getLog()->debug("Installation process [$processId]: end run.");

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    protected function initPackage(array $data): void
    {
        $processId = $data['id'];

        if (empty($processId)) {
            throw new Error('Installation package ID was not specified.');
        }

        $this->setProcessId($processId);

        if (isset($data['parentProcessId'])) {
            $this->setParentProcessId($data['parentProcessId']);
        }

        /** check if an archive is unzipped, if no then unzip */
        $packagePath = $this->getPackagePath();

        if (!file_exists($packagePath)) {
            $this->unzipArchive();
            $this->isAcceptable();
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepInit(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"init\" step.");

        if (!$this->systemRebuild()) {
            $this->throwErrorAndRemovePackage('Rebuild is failed. Fix all errors before upgrade.');
        }

        $this->initialize();
        $this->checkIsWritable();
        $this->enableMaintenanceMode();
        $this->beforeRunAction();
        $this->backupExistingFiles();

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"init\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepCopyBefore(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"copyBefore\" step.");

        if (!$this->copyFiles('before')) {
            $this->throwErrorAndRemovePackage('Cannot copy beforeInstall files.');
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"copyBefore\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepBeforeInstallScript(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"beforeInstallScript\" step.");

        if (!isset($data['skipBeforeScript']) || !$data['skipBeforeScript']) {
            $this->runScript('before');
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"beforeInstallScript\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepCopy(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"copy\" step.");

        /* remove files defined in a manifest */
        if (!$this->deleteFiles('delete', true)) {
            $this->throwErrorAndRemovePackage('Cannot delete files.');
        }

        /* copy files from directory "Files" to EspoCRM files */
        if (!$this->copyFiles()) {
            $this->throwErrorAndRemovePackage('Cannot copy files.');
        }

        if (!$this->deleteFiles('vendor')) {
            $this->throwErrorAndRemovePackage('Cannot delete vendor files.');
        }

        if (!$this->copyFiles('vendor')) {
            $this->throwErrorAndRemovePackage('Cannot copy vendor files.');
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"copy\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepRebuild(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"rebuild\" step.");

        if (!isset($data['skipSystemRebuild']) || !$data['skipSystemRebuild']) {
            if (!$this->systemRebuild()) {
                $this->throwErrorAndRemovePackage('Error occurred while EspoCRM rebuild. More detail in the log.');
            }
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"rebuild\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepCopyAfter(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"copyAfter\" step.");

        //afterInstallFiles
        if (!$this->copyFiles('after')) {
            $this->throwErrorAndRemovePackage('Cannot copy afterInstall files.');
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"copyAfter\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepAfterInstallScript(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"afterInstallScript\" step.");

        /* run after install script */
        if (!isset($data['skipAfterScript']) || !$data['skipAfterScript']) {
            $this->runScript('after');
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"afterInstallScript\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepFinalize(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"finalize\" step.");

        $this->disableMaintenanceMode();
        $this->afterRunAction();
        $this->finalize();

        /* delete unzipped files */
        $this->deletePackageFiles();

        if ($this->getManifestParam('skipBackup')) {
            $path = Util::concatPath($this->getPath('backupPath'), self::FILES);

            $this->getFileManager()->removeInDir($path);
        }

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"finalize\" step.");
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepRevert(array $data): void
    {
        $this->initPackage($data);

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: Start \"revert\" step.");

        $this->restoreFiles();

        $this->getLog()->info("Installation process [{$this->getProcessId()}]: End \"revert\" step.");
    }

    /**
     * @throws Error
     */
    protected function restoreFiles(): bool
    {
        $this->getLog()->info('Installer: Restore previous files.');

        $backupPath = $this->getPath('backupPath');
        $backupFilePath = Util::concatPath($backupPath, self::FILES);

        if (!file_exists($backupFilePath)) {
            return true;
        }

        $backupFileList = $this->getRestoreFileList();
        $copyFileList = $this->getCopyFileList();
        $deleteFileList = array_diff($copyFileList, $backupFileList);

        $res = $this->copy($backupFilePath, '', true);

        if (!empty($deleteFileList)) {
            $res &= $this->getFileManager()->remove($deleteFileList, null, true);
        }

        if ($res) {
            $this->getFileManager()->removeInDir($backupPath, true);
        }

        return (bool) $res;
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

        $this->restoreFiles();

        parent::throwErrorAndRemovePackage($errorMessage, $deletePackage, $systemRebuild, $exception);
    }
}
