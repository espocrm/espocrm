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

namespace Espo\ORM\Mapper;

use Espo\ORM\{
    Entity,
    Collection,
    Query\Select,
};

interface RDBMapper extends Mapper
{
    /**
     * Relate an entity with another entity.
     */
    public function relate(Entity $entity, string $relationName, Entity $foreignEntity, ?array $columnData): bool;

    /**
     * Unrelate an entity from another entity.
     */
    public function unrelate(Entity $entity, string $relationName, Entity $foreignEntity): void;

    /**
     * Unrelate an entity from another entity by a given ID.
     */
    public function relateById(Entity $entity, string $relationName, string $id, ?array $columnData = null): bool;

    /**
     * Unrelate an entity from another entity by a given ID.
     */
    public function unrelateById(Entity $entity, string $relationName, string $id): void;

    /**
     * Mass relate.
     */
    public function massRelate(Entity $entity, string $relationName, Select $select): void;

    /**
     * Update relationship columns.
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
    );

    /**
     * Select related entities from DB.
     *
     * @return Collection|Entity|null
     */
    public function selectRelated(Entity $entity, string $relationName, ?Select $select = null);

    /**
     * Get a number of related entities in DB.
     */
    public function countRelated(Entity $entity, string $relationName, ?Select $select = null): int;
}
