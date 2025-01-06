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

use Espo\ORM\Entity;
use Espo\ORM\Repository\Util as RepositoryUtil;

/**
 * Container for record services. Lazy loading is used.
 * Usually there's no need to have multiple record service instances of the same entity type.
 * Use this container instead of serviceFactory to get record services.
 *
 * Important. Returns record services for the current user.
 * Use the service-factory to create services for a specific user.
 */
class ServiceContainer
{
    /** @var array<string, Service<Entity>> */
    private $data = [];

    public function __construct(private ServiceFactory $serviceFactory)
    {}

    /**
     * Get a record service by an entity class name.
     *
     * @template T of Entity
     * @param class-string<T> $className An entity class name.
     * @return Service<T>
     */
    public function getByClass(string $className): Service
    {
        $entityType = RepositoryUtil::getEntityTypeByClass($className);

        /** @var Service<T> */
        return $this->get($entityType);
    }

    /**
     * Get a record service by an entity type.
     *
     * @return Service<Entity>
     */
    public function get(string $entityType): Service
    {
        if (!array_key_exists($entityType, $this->data)) {
            $this->load($entityType);
        }

        return $this->data[$entityType];
    }

    private function load(string $entityType): void
    {
        $this->data[$entityType] = $this->serviceFactory->create($entityType);
    }
}
