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

namespace Espo\ORM\Defs;

use RuntimeException;

/**
 * Definitions.
 */
class Defs
{
    public function __construct(private DefsData $data)
    {}

    /**
     * Get an entity type list.
     *
     * @return string[]
     */
    public function getEntityTypeList(): array
    {
        return $this->data->getEntityTypeList();
    }

    /**
     * Get an entity definitions list.
     *
     * @return EntityDefs[]
     */
    public function getEntityList(): array
    {
        $list = [];

        foreach ($this->getEntityTypeList() as $name) {
            $list[] = $this->getEntity($name);
        }

        return $list;
    }

    /**
     * Has an entity type.
     */
    public function hasEntity(string $entityType): bool
    {
        return $this->data->hasEntity($entityType);
    }

    /**
     * Get entity definitions.
     */
    public function getEntity(string $entityType): EntityDefs
    {
        if (!$this->hasEntity($entityType)) {
            throw new RuntimeException("Entity type '{$entityType}' does not exist.");
        }

        return $this->data->getEntity($entityType);
    }

    /**
     * Try to get entity definitions, if an entity type does not exist, then return null.
     */
    public function tryGetEntity(string $entityType): ?EntityDefs
    {
        if (!$this->hasEntity($entityType)) {
            return null;
        }

        return $this->getEntity($entityType);
    }
}
