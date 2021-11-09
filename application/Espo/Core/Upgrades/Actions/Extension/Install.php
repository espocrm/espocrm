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

namespace Espo\Core\Upgrades\Actions\Extension;

use Espo\Core\Upgrades\ExtensionManager;

use Espo\Core\Utils\Util;

use Throwable;

class Install extends \Espo\Core\Upgrades\Actions\Base\Install
{
    protected $extensionEntity = null;

    protected function beforeRunAction()
    {
        $this->findExtension();

        if (!$this->isNew()) {
            $this->scriptParams['isUpgrade'] = true;

            $this->compareVersion();
            $this->uninstallExtension();
        }
    }

    protected function afterRunAction()
    {
        if (!$this->isNew()) {
            $this->deleteExtension();
        }

        $this->storeExtension();
    }

    /**
     * Copy Existing files to backup directory
     *
     * @return bool
     */
    protected function backupExistingFiles()
    {
        parent::backupExistingFiles();

        $backupPath = $this->getPath('backupPath');

        /** copy scripts files */
        $packagePath = $this->getPackagePath();

        $source = Util::concatPath($packagePath, self::SCRIPTS);
        $destination = Util::concatPath($backupPath, self::SCRIPTS);

        return $this->copy($source, $destination, true);
    }

    protected function isNew()
    {
        $extensionEntity = $this->getExtensionEntity();

        if (isset($extensionEntity)) {
            $id = $this->getExtensionEntity()->get('id');
        }

        return isset($id) ? false : true;
    }

    /**
     * Get extension ID. It's an ID of existing entity (if available) or Installation ID
     *
     * @return string
     */
    protected function getExtensionId()
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
     * Get entity of this extension
     *
     * @return \Espo\Entities\Extension|null
     */
    protected function getExtensionEntity()
    {
        return $this->extensionEntity;
    }

    /**
     * Find Extension entity
     *
     * @return \Espo\Entities\Extension
     */
    protected function findExtension()
    {
        $manifest = $this->getManifest();

        $this->extensionEntity = $this->getEntityManager()->getRepository('Extension')->where(array(
            'name' => $manifest['name'],
            'isInstalled' => true,
        ))->findOne();

        return $this->extensionEntity;
    }

    /**
     * Create a record of Extension Entity.
     */
    protected function storeExtension()
    {
        $entityManager = $this->getEntityManager();

        $extensionEntity = $entityManager->getEntity('Extension', $this->getProcessId());
        if (!isset($extensionEntity)) {
            $extensionEntity = $entityManager->getEntity('Extension');
        }

        $manifest = $this->getManifest();
        $fileList = $this->getCopyFileList();

        $data = array(
            'id' => $this->getProcessId(),
            'name' => trim($manifest['name']),
            'isInstalled' => true,
            'version' => $manifest['version'],
            'fileList' => $fileList,
            'description' => $manifest['description'],
        );

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
     * @return void
     */
    protected function compareVersion()
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
     * @return void
     */
    protected function uninstallExtension()
    {
        $extensionEntity = $this->getExtensionEntity();

        $this->executeAction(ExtensionManager::UNINSTALL, [
            'id' => $extensionEntity->get('id'),
            'skipSystemRebuild' => true,
            'skipAfterScript' => true,
            'parentProcessId' => $this->getProcessId(),
        ]);
    }

    /**
     * Delete extension package.
     *
     * @return void
     */
    protected function deleteExtension()
    {
        $extensionEntity = $this->getExtensionEntity();

        $this->executeAction(ExtensionManager::DELETE, [
            'id' => $extensionEntity->get('id'),
            'parentProcessId' => $this->getProcessId(),
        ]);
    }

    protected function checkDependencies($dependencyList)
    {
        return $this->getHelper()->checkDependencies($dependencyList);
    }
}
