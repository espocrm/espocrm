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

interface Mapper
{
    /**
     * Get a first entity from DB.
     */
    public function selectOne(Select $select): ?Entity;

    /**
     * Select entities from DB.
     */
    public function select(Select $select): Collection;

    /**
     * Get a number of records in DB.
     */
    public function count(Select $select): int;

    /**
     * Insert an entity into DB.
     */
    public function insert(Entity $entity): void;

    /**
     * Insert a collection into DB.
     */
    public function massInsert(Collection $collection): void;

    /**
     * Update an entity in DB.
     */
    public function update(Entity $entity): void;

    /**
     * Mark an entity as deleted in DB.
     */
    public function delete(Entity $entity): void;

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

    /**
     * Insert an entity into DB, on duplicate key update specified attributes.
     */
    public function insertOnDuplicateUpdate(Entity $entity, array $onDuplicateUpdateAttributeList): void;
}
