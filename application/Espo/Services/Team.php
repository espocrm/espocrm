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
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;

use Espo\Core\Select\SearchParams;

use Espo\Core\Di;

/**
 * @extends Record<\Espo\Entities\Team>
 */
class Team extends Record implements

    Di\DataManagerAware
{
    use Di\DataManagerSetter;

    public function afterUpdateEntity(Entity $entity, $data)
    {
        parent::afterUpdateEntity($entity, $data);

        if (property_exists($data, 'rolesIds')) {
            $this->clearRolesCache();
        }
    }

    protected function clearRolesCache(): void
    {
        $this->createAclCacheClearer()->clearForAllInternalUsers();

        $this->dataManager->updateCacheTimestamp();
    }

    public function link(string $id, string $link, string $foreignId): void
    {
        parent::link($id, $link, $foreignId);

        if ($link === 'users') {
            /** @var ?UserEntity $user */
            $user = $this->entityManager->getEntityById(UserEntity::ENTITY_TYPE, $foreignId);

            if ($user) {
                $this->createAclCacheClearer()->clearForUser($user);
            }

            $this->dataManager->updateCacheTimestamp();
        }
    }

    public function unlink(string $id, string $link, string $foreignId): void
    {
        parent::unlink($id, $link, $foreignId);

        if ($link === 'users') {
            /** @var ?UserEntity $user */
            $user = $this->entityManager->getEntityById(UserEntity::ENTITY_TYPE, $foreignId);

            if ($user) {
                $this->createAclCacheClearer()->clearForUser($user);
            }

            $this->dataManager->updateCacheTimestamp();
        }
    }

    public function massLink(string $id, string $link, SearchParams $searchParams): bool
    {
        $result = parent::massLink($id, $link, $searchParams);

        if ($link === 'users') {
            $this->clearRolesCache();
        }

        return $result;
    }

    private function createAclCacheClearer(): AclCacheClearer
    {
        return $this->injectableFactory->create(AclCacheClearer::class);
    }
}
