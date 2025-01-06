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
use Espo\Core\Utils\Json;
use Throwable;

class Uninstall extends Base
{
    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function run(mixed $data): mixed
    {
        $processId = $data['id'];

        $this->getLog()->debug('Uninstallation process ['.$processId.']: start run.');

        if (empty($processId)) {
            throw new Error('Uninstallation package ID was not specified.');
        }

        $this->setProcessId($processId);

        if (isset($data['parentProcessId'])) {
            $this->setParentProcessId($data['parentProcessId']);
        }

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
                $this->throwErrorAndRemovePackage(
                    'Error occurred while EspoCRM rebuild. More detail in the log.'
                );
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

        $this->getLog()->debug('Uninstallation process ['.$processId.']: end run.');

        $this->clearCache();

        return null;
    }

    /**
     * @throws Error
     */
    protected function restoreFiles(): bool
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $packagePath = $this->getPath('packagePath');

        $manifestPath = Util::concatPath($packagePath, $this->manifestName);

        if (!file_exists($manifestPath)) {
            $this->unzipArchive($packagePath);
        }

        $fileDirs = $this->getFileDirs($packagePath);

        $res = true;

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

        return (bool) $res;
    }

    /**
     * @param ?string $type
     * @param string $dest
     * @throws Error
     */
    protected function copyFiles($type = null, $dest = ''): bool
    {
        $backupPath = $this->getPath('backupPath');

        $source = Util::concatPath($backupPath, self::FILES);

        return $this->copy($source, $dest, true);
    }

    /**
     * Get backup path.
     *
     * @param bool $isPackage
     * @throws Error
     */
    protected function getPackagePath($isPackage = false): string
    {
        if ($isPackage) {
            return $this->getPath('packagePath', $isPackage);
        }

        return $this->getPath('backupPath');
    }

    /**
     * @throws Error
     */
    protected function deletePackageFiles(): bool
    {
        $backupPath = $this->getPath('backupPath');

        return $this->getFileManager()->removeInDir($backupPath, true);
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

        parent::throwErrorAndRemovePackage($errorMessage, false, $systemRebuild, $exception);
    }

    /**
     * @return string[]
     * @throws Error
     */
    protected function getCopyFileList(): array
    {
        if (!isset($this->data['fileList'])) {
            $backupPath = $this->getPath('backupPath');
            $filesPath = Util::concatPath($backupPath, self::FILES);

            $this->data['fileList'] = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
        }

        return $this->data['fileList'];
    }

    /**
     * @throws Error
     */
    protected function getRestoreFileList(): array
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

    /**
     * @param string $type
     * @throws Error
     */
    protected function getDeleteList($type = 'delete'): array
    {
        if ($type == 'delete') {
            $packageFileList = $this->getRestoreFileList();
            $backupFileList = $this->getCopyFileList();

            return array_diff($packageFileList, $backupFileList);
        }

        return [];
    }
}
