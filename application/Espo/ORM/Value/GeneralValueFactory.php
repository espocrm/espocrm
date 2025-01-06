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

namespace Espo\ORM\Value;

use Espo\ORM\Entity;

use RuntimeException;

class GeneralValueFactory
{
    /** @var array<string,?ValueFactory> */
    private array $factoryCache = [];

    public function __construct(private ValueFactoryFactory $valueFactoryFactory)
    {}

    /**
     * Whether a field value object can be created from an entity.
     */
    public function isCreatableFromEntity(Entity $entity, string $field): bool
    {
        $factory = $this->getValueFactory($entity->getEntityType(), $field);

        if (!$factory) {
            return false;
        }

        return $factory->isCreatableFromEntity($entity, $field);
    }

    /**
     * Create a field value object from an entity.
     */
    public function createFromEntity(Entity $entity, string $field): object
    {
        $factory = $this->getValueFactory($entity->getEntityType(), $field);

        if (!$factory) {
            $entityType = $entity->getEntityType();

            throw new RuntimeException("No value-object factory for '{$entityType}.{$field}'.");
        }

        /** @var ValueFactory */
        return $factory->createFromEntity($entity, $field);
    }

    private function getValueFactory(string $entityType, string $field): ?ValueFactory
    {
        $key = $entityType . '_' . $field;

        if (!array_key_exists($key, $this->factoryCache)) {
            $this->factoryCache[$key] = $this->getValueFactoryNoCache($entityType, $field);
        }

        return $this->factoryCache[$key];
    }

    private function getValueFactoryNoCache(string $entityType, string $field): ?ValueFactory
    {
        if (!$this->valueFactoryFactory->isCreatable($entityType, $field)) {
            return null;
        }

        return $this->valueFactoryFactory->create($entityType, $field);
    }
}
