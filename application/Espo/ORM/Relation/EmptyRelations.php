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

namespace Espo\ORM\Relation;

use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use LogicException;
use RuntimeException as RuntimeExceptionAlias;

class EmptyRelations implements Relations
{
    /** @var array<string, Entity|null> */
    private array $setData = [];

    public function __construct() {}

    public function resetAll(): void
    {
        $this->setData = [];
    }

    public function reset(string $relation): void
    {
        unset($this->setData[$relation]);
    }

    /**
     * @param Entity|null $related
     */
    public function set(string $relation, Entity|null $related): void
    {
        $this->setData[$relation] = $related;
    }

    public function isSet(string $relation): bool
    {
        return array_key_exists($relation, $this->setData);
    }

    /**
     * @return Entity|null
     */
    public function getSet(string $relation): Entity|null
    {
        if (!array_key_exists($relation, $this->setData)) {
            throw new RuntimeExceptionAlias("Relation '$relation' is not set.");
        }

        return $this->setData[$relation];
    }

    public function getOne(string $relation): ?Entity
    {
        $entity = $this->setData[$relation] ?? null;

        if ($entity instanceof EntityCollection) {
            throw new LogicException("Not an entity.");
        }

        return $entity;
    }

    /***
     * @return EntityCollection<Entity>
     */
    public function getMany(string $relation): EntityCollection
    {
        $collection = $this->setData[$relation] ?? new EntityCollection();

        if (!$collection instanceof EntityCollection) {
            throw new LogicException("Not a collection.");
        }

        /** @var EntityCollection<Entity> */
        return $collection;
    }
}
