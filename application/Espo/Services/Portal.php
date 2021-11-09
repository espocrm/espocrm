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

namespace Espo\Services;

use Espo\ORM\Entity;

use Espo\Repositories\Portal as Repository;
use Espo\Entities\Portal as PortalEntity;

use Espo\Core\Di;

class Portal extends Record implements

    Di\FileManagerAware,
    Di\DataManagerAware
{
    use Di\FileManagerSetter;
    use Di\DataManagerSetter;

    protected $getEntityBeforeUpdate = true;

    protected $mandatorySelectAttributeList = [
        'customUrl',
        'customId',
    ];

    protected function afterUpdateEntity(Entity $entity, $data)
    {
        /** @var PortalEntity $entity */

        $this->loadUrlField($entity);

        if (property_exists($data, 'portalRolesIds')) {
            $this->clearRolesCache();
        }
    }

    protected function loadUrlField(PortalEntity $entity)
    {
        $this->getPortalRepository()->loadUrlField($entity);
    }

    protected function clearRolesCache()
    {
        $this->fileManager->removeInDir('data/cache/application/aclPortal');
        $this->fileManager->removeInDir('data/cache/application/aclPortalMap');

        $this->dataManager->updateCacheTimestamp();
    }

    private function getPortalRepository(): Repository
    {
        /** @var Repository */
        return $this->getRepository();
    }
}
