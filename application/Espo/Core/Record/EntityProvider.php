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

namespace Espo\Core\Record;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * Fetches entities.
 *
 * @since 8.1.0
 */
class EntityProvider
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl
    ) {}

    /**
     * Fetch an entity.
     *
     * @template T of Entity
     * @param class-string<T> $className An entity class name.
     * @return T
     * @throws NotFound A record not found.
     * @throws Forbidden Read is forbidden for a current user.
     * @since 8.3.0
     * @noinspection PhpDocSignatureInspection
     */
    public function getByClass(string $className, string $id): Entity
    {
        $entity = $this->entityManager
            ->getRDBRepositoryByClass($className)
            ->getById($id);

        return $this->processGet($entity);
    }

    /**
     * Fetch an entity by an entity type.
     *
     * @return Entity
     * @throws NotFound
     * @throws Forbidden
     * @since 9.0.0
     */
    public function get(string $entityType, string $id): Entity
    {
        $entity = $this->entityManager->getEntityById($entityType, $id);

        return $this->processGet($entity);
    }

    /**
     * @template T of Entity
     * @param ?T $entity
     * @return T
     * @throws Forbidden
     * @throws NotFound
     * @noinspection PhpDocSignatureInspection
     */
    private function processGet(?Entity $entity): Entity
    {
        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityRead($entity)) {
            throw new Forbidden();
        }

        return $entity;
    }
}
