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

namespace Espo\Services;

use Espo\Core\Acl\Cache\Clearer as AclCacheClearer;
use Espo\ORM\Entity;
use Espo\Repositories\Portal as Repository;
use Espo\Entities\Portal as PortalEntity;
use Espo\Core\Di;
use stdClass;

/**
 * @extends Record<PortalEntity>
 */
class Portal extends Record implements

    Di\DataManagerAware
{
    use Di\DataManagerSetter;

    protected $getEntityBeforeUpdate = true;

    protected $mandatorySelectAttributeList = [
        'customUrl',
        'customId',
    ];

    public function filterCreateInput(stdClass $data): void
    {
        parent::filterCreateInput($data);

        $this->filterRestrictedFields($data);
    }

    public function filterUpdateInput(stdClass $data): void
    {
        parent::filterUpdateInput($data);

        $this->filterRestrictedFields($data);
    }

    private function filterRestrictedFields(stdClass $data): void
    {
        if (!$this->config->get('restrictedMode')) {
            return;
        }

        if ($this->user->isSuperAdmin()) {
            return;
        }

        unset($data->customUrl);
    }

    protected function afterUpdateEntity(Entity $entity, $data)
    {
        /** @var PortalEntity $entity */

        $this->loadUrlField($entity);

        if (property_exists($data, 'portalRolesIds')) {
            $this->clearRolesCache();
        }
    }

    protected function loadUrlField(PortalEntity $entity): void
    {
        $this->getPortalRepository()->loadUrlField($entity);
    }

    protected function clearRolesCache(): void
    {
        $this->createAclCacheClearer()->clearForAllPortalUsers();

        $this->dataManager->updateCacheTimestamp();
    }

    private function getPortalRepository(): Repository
    {
        /** @var Repository */
        return $this->getRepository();
    }

    private function createAclCacheClearer(): AclCacheClearer
    {
        return $this->injectableFactory->create(AclCacheClearer::class);
    }
}
