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

use Espo\Core\Exceptions\Error;

use Throwable;

class Uninstall extends \Espo\Core\Upgrades\Actions\Base\Uninstall
{
    protected $extensionEntity;

    /**
     * Get entity of this extension
     *
     * @return \Espo\Entities\Extension
     */
    protected function getExtensionEntity()
    {
        if (!isset($this->extensionEntity)) {
            $processId = $this->getProcessId();
            $this->extensionEntity = $this->getEntityManager()->getEntity('Extension', $processId);

            if (!isset($this->extensionEntity)) {
                throw new Error('Extension Entity not found.');
            }
        }

        return $this->extensionEntity;
    }

    protected function afterRunAction()
    {
        /** Set extension entity, isInstalled = false */
        $extensionEntity = $this->getExtensionEntity();
        $extensionEntity->set('isInstalled', false);

        try {
            $this->getEntityManager()->saveEntity($extensionEntity);
        }
        catch (Throwable $e) {
            $this->getLog()->error(
                'Error saving Extension entity. The error occurred by existing Hook, more details: ' .
                $e->getMessage() .' at '. $e->getFile() . ':' . $e->getLine()
            );

            $this->throwErrorAndRemovePackage('Error saving Extension entity. Check logs for details.', false);
        }
    }

    protected function getRestoreFileList()
    {
        if (!isset($this->data['restoreFileList'])) {
            $extensionEntity = $this->getExtensionEntity();
            $this->data['restoreFileList'] = $extensionEntity->get('fileList');
        }

        return $this->data['restoreFileList'];
    }
}
