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

namespace Espo\Core\Upgrades\Actions\Extension;

use Espo\Core\Exceptions\Error,
    Espo\Core\ExtensionManager;

class Install extends \Espo\Core\Upgrades\Actions\Base\Install
{
    protected $extensionEntity = null;

    protected function beforeRunAction()
    {
        $this->findExtension();
        if (!$this->isNew()) {
            $this->compareVersion();
            $this->uninstallExtension();
            $this->deleteExtension();
        }

        $this->copyExistingFiles();
    }

    protected function afterRunAction()
    {
        $this->storeExtension();
    }

    /**
     * Copy Existing files to backup directory
     *
     * @return bool
     */
    protected function copyExistingFiles()
    {
        $fileList = $this->getCopyFileList();
        $backupPath = $this->getPath('backupPath');

        $res = $this->copy('', array($backupPath, self::FILES), false, $fileList);

        /** copy scripts files */
        $packagePath = $this->getPackagePath();
        $res &= $this->copy(array($packagePath, self::SCRIPTS), array($backupPath, self::SCRIPTS), true);

        return $res;
    }

    protected function restoreFiles()
    {
        $res = true;
        if ($this->isCopied) {
            $extensionFileList = $this->getCopyFileList();
            $res &= $this->getFileManager()->remove($extensionFileList);
        }

        $res &= parent::restoreFiles();

        return $res;
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
     * @return \Espo\Entities\Extension
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
     * Create a record of Extension Entity
     *
     * @return bool
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
            'name' => $manifest['name'],
            'isInstalled' => true,
            'version' => $manifest['version'],
            'fileList' => $fileList,
            'description' => $manifest['description'],
        );
        $extensionEntity->set($data);

        return $entityManager->saveEntity($extensionEntity);
    }

    /**
     * Compare version between installed and a new extensions
     *
     * @return void
     */
    protected function compareVersion()
    {
        $manifest = $this->getManifest();
        $extensionEntity = $this->getExtensionEntity();

        if (isset($extensionEntity)) {
            $comparedVersion = version_compare($manifest['version'], $extensionEntity->get('version'));
            if ($comparedVersion <= 0) {
                $this->throwErrorAndRemovePackage('You cannot install an older version of this extension.');
            }
        }
    }

    /**
     * Throw an exception and remove package files.
     * Redeclared to prevent of deleting a package of installed extension.
     *
     * @param  string $errorMessage [description]
     * @return [type]               [description]
     */
    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        if (!$this->isNew()) {
            throw new Error($errorMessage);
        }

        return parent::throwErrorAndRemovePackage($errorMessage);
    }

    /**
     * If extension already installed, uninstall an old version
     *
     * @return void
     */
    protected function uninstallExtension()
    {
        $extensionEntity = $this->getExtensionEntity();

        $this->executeAction(ExtensionManager::UNINSTALL, $extensionEntity->get('id'));
    }

    /**
     * Delete extension package
     *
     * @return void
     */
    protected function deleteExtension()
    {
        $extensionEntity = $this->getExtensionEntity();

        $this->executeAction(ExtensionManager::DELETE, $extensionEntity->get('id'));
    }




}
