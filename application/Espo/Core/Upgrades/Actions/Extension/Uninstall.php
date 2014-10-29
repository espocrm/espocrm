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

use Espo\Core\Exceptions\Error;
use Espo\Entities\Extension;

class Uninstall extends
    \Espo\Core\Upgrades\Actions\Base\Uninstall
{

    protected $extensionEntity;

    protected function beforeRunAction()
    {
        /**
         * @var Extension $extensionEntity
         */
        $processId = $this->getProcessId();
        /** get extension entity */
        $extensionEntity = $this->getEntityManager()->getEntity('Extension', $processId);
        if (!isset($extensionEntity)) {
            throw new Error('Extension Entity not found.');
        }
        $this->setExtensionEntity($extensionEntity);
    }

    protected function afterRunAction()
    {
        /** Set extension entity, isInstalled = false */
        $extensionEntity = $this->getExtensionEntity();
        $extensionEntity->set('isInstalled', false);
        $this->getEntityManager()->saveEntity($extensionEntity);
    }

    /**
     * Get entity of this extension
     *
     * @return Extension
     */
    protected function getExtensionEntity()
    {
        return $this->extensionEntity;
    }

    /**
     * Set Extension Entity
     *
     * @param Extension $extensionEntity [description]
     */
    protected function setExtensionEntity(Extension $extensionEntity)
    {
        $this->extensionEntity = $extensionEntity;
    }
}