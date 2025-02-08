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

namespace Espo\ORM\Mapper;

use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\Query\Select;

interface RDBMapper extends Mapper
{
    /**
     * Relate an entity with another entity.
     *
     * @param Entity $entity An entity.
     * @param string $relationName A relation name.
     * @param Entity $foreignEntity A foreign entity.
     * @param array<string, mixed>|null $columnData Column values.
     * @return bool True if the row was affected.
     */
    public function relate(Entity $entity, string $relationName, Entity $foreignEntity, ?array $columnData): bool;

    /**
     * Unrelate an entity from another entity.
     *
     * @param Entity $entity An entity.
     * @param string $relationName A relation name.
     * @param Entity $foreignEntity A foreign entity.
     */
    public function unrelate(Entity $entity, string $relationName, Entity $foreignEntity): void;

    /**
     * Relate an entity from another entity by a given ID.
     *
     * @param Entity $entity An entity.
     * @param string $relationName A relation name.
     * @param string $id A foreign ID.
     * @param array<string, mixed>|null $columnData Column values.
     */
    public function relateById(Entity $entity, string $relationName, string $id, ?array $columnData = null): bool;

    /**
     * Unrelate an entity from another entity by a given ID.
     *
     * @param Entity $entity An entity.
     * @param string $relationName A relation name.
     * @param string $id A foreign ID.
     */
    public function unrelateById(Entity $entity, string $relationName, string $id): void;

    /**
     * Mass relate.
     */
    public function massRelate(Entity $entity, string $relationName, Select $select): void;

    /**
     * Update relationship columns.
     *
     * @param array<string, mixed> $columnData
     */
    public function updateRelationColumns(
        Entity $entity,
        string $relationName,
        string $id,
        array $columnData
    ): void;

    /**
     * Get a relationship column value.
     *
     * @return string|int|float|bool|null A relationship column value.
     */
    public function getRelationColumn(
        Entity $entity,
        string $relationName,
        string $id,
        string $column
    ): string|int|float|bool|null;

    /**
     * Select related entities from DB.
     *
     * @return Collection<Entity>|Entity|null
     */
    public function selectRelated(Entity $entity, string $relationName, ?Select $select = null): Collection|Entity|null;

    /**
     * Get a number of related entities in DB.
     */
    public function countRelated(Entity $entity, string $relationName, ?Select $select = null): int;
}
