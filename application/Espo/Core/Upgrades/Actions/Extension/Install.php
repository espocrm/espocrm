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

namespace Espo\Core\Upgrades\Actions\Extension;

use Espo\Core\Upgrades\Base;
use Espo\Core\Utils\Util;
use Espo\Core\Exceptions\Error;

use Espo\Entities\Extension;
use Throwable;

class Install extends \Espo\Core\Upgrades\Actions\Base\Install
{
    protected ?Extension $extensionEntity = null;

    /**
     * @throws Error
     */
    protected function beforeRunAction(): void
    {
        $this->findExtension();

        if (!$this->isNew()) {
            $this->scriptParams['isUpgrade'] = true;

            $this->compareVersion();
            $this->uninstallExtension();
        }
    }

    /**
     * @throws Error
     */
    protected function afterRunAction(): void
    {
        if (!$this->isNew()) {
            $this->deleteExtension();
        }

        $this->storeExtension();
    }

    /**
     * Copy Existing files to a backup directory.
     *
     * @throws Error
     */
    protected function backupExistingFiles(): bool
    {
        parent::backupExistingFiles();

        $backupPath = $this->getPath('backupPath');

        /** copy scripts files */
        $packagePath = $this->getPackagePath();

        $source = Util::concatPath($packagePath, self::SCRIPTS);
        $destination = Util::concatPath($backupPath, self::SCRIPTS);

        return $this->copy($source, $destination, true);
    }

    protected function isNew(): bool
    {
        $extensionEntity = $this->getExtensionEntity();

        if (isset($extensionEntity)) {
            $id = $extensionEntity->get('id');
        }

        return !isset($id);
    }

    /**
     * Get extension ID. It's an ID of existing entity (if available) or Installation ID.
     *
     * @throws Error
     */
    protected function getExtensionId(): string
    {
        $extensionEntity = $this->getExtensionEntity();

        if (isset($extensionEntity)) {
            $extensionEntityId = $extensionEntity->get('id');
        }

        if (!isset($extensionEntityId)) {
            return $this->getProcessId();
        }

        return $extensionEntityId;
    }

    /**
     * Get entity of this extension.
     */
    protected function getExtensionEntity(): ?Extension
    {
        return $this->extensionEntity;
    }

    /**
     * Find Extension entity.
     *
     * @throws Error
     */
    protected function findExtension(): ?Extension
    {
        $manifest = $this->getManifest();

        $this->extensionEntity = $this->getEntityManager()
            ->getRDBRepository(Extension::ENTITY_TYPE)
            ->where([
                'name' => $manifest['name'],
                'isInstalled' => true,
            ])
            ->findOne();

        return $this->extensionEntity;
    }

    /**
     * Create a record of Extension Entity.
     *
     * @throws Error
     */
    protected function storeExtension(): void
    {
        $entityManager = $this->getEntityManager();

        $extensionEntity = $entityManager->getEntity(Extension::ENTITY_TYPE, $this->getProcessId());

        if (!$extensionEntity) {
            $extensionEntity = $entityManager->getNewEntity(Extension::ENTITY_TYPE);
        }

        $manifest = $this->getManifest();
        $fileList = $this->getCopyFileList();

        $data = [
            'id' => $this->getProcessId(),
            'name' => trim($manifest['name']),
            'isInstalled' => true,
            'version' => $manifest['version'],
            'fileList' => $fileList,
            'description' => $manifest['description'],
        ];

        if (!empty($manifest['checkVersionUrl'])) {
            $data['checkVersionUrl'] = $manifest['checkVersionUrl'];
        }

        $extensionEntity->set($data);

        try {
            $entityManager->saveEntity($extensionEntity);
        }
        catch (Throwable $e) {
            $this->getLog()
                ->error(
                    'Error saving Extension entity. The error occurred by existing Hook, more details: ' .
                    $e->getMessage() .' at '. $e->getFile() . ':' . $e->getLine()
                );

            $this->throwErrorAndRemovePackage('Error saving Extension entity. Check logs for details.', false);
        }
    }

    /**
     * Compare version between installed and a new extensions.
     *
     * @throws Error
     */
    protected function compareVersion(): void
    {
        $manifest = $this->getManifest();
        $extensionEntity = $this->getExtensionEntity();

        if (isset($extensionEntity)) {
            $comparedVersion = version_compare($manifest['version'], $extensionEntity->get('version'), '>=');
            if ($comparedVersion <= 0) {
                $this->throwErrorAndRemovePackage('You cannot install an older version of this extension.');
            }
        }
    }

    /**
     * If extension already installed, uninstall an old version.
     *
     * @throws Error
     */
    protected function uninstallExtension(): void
    {
        $extensionEntity = $this->getExtensionEntity();

        if (!$extensionEntity) {
            throw new Error("Can't uninstall not existing extension.");
        }

        $this->executeAction(Base::UNINSTALL, [
            'id' => $extensionEntity->get('id'),
            'skipSystemRebuild' => true,
            'skipAfterScript' => true,
            'parentProcessId' => $this->getProcessId(),
        ]);
    }

    /**
     * Delete extension package.
     *
     * @throws Error
     */
    protected function deleteExtension(): void
    {
        $extensionEntity = $this->getExtensionEntity();

        if (!$extensionEntity) {
            throw new Error("Can't delete not existing extension.");
        }

        $this->executeAction(Base::DELETE, [
            'id' => $extensionEntity->get('id'),
            'parentProcessId' => $this->getProcessId(),
        ]);
    }

    /**
     * @param array<string, string[]|string> $dependencyList
     * @throws Error
     */
    protected function checkDependencies($dependencyList): bool
    {
        return $this->getHelper()->checkDependencies($dependencyList);
    }
}
