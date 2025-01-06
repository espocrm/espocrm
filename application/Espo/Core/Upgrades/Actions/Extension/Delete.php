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

namespace Espo\Core\Upgrades\Actions\Extension;
use Espo\Core\Exceptions\Error;
use Espo\Entities\Extension;

class Delete extends \Espo\Core\Upgrades\Actions\Base\Delete
{
    protected ?Extension $extensionEntity = null;

    /**
     * Get an entity of this extension.
     *
     * @throws Error
     */
    protected function getExtensionEntity(): Extension
    {
        if (!$this->extensionEntity) {
            $processId = $this->getProcessId();

            $this->extensionEntity = $this->getEntityManager()->getEntityById(Extension::ENTITY_TYPE, $processId);

            if (!$this->extensionEntity) {
                throw new Error('Extension entity not found.');
            }
        }

        return $this->extensionEntity;
    }

    /**
     * @throws Error
     */
    protected function afterRunAction(): void
    {
        /** Delete extension entity */
        $extensionEntity = $this->getExtensionEntity();

        $this->getEntityManager()->removeEntity($extensionEntity);
    }
}
