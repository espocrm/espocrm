<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
    QueryParams\Select,
};

interface Mapper
{
    /**
     * Get the first entity from DB.
     */
    public function selectOne(Select $select) : ?Entity;

    /**
     * Select a list of entities according to given parameters.
     */
    public function select(Select $select) : Collection;

    /**
     * Returns count of records according to given parameters.
     *
     * @return Record count.
     */
    public function count(Select $select) : int;

    /**
     * Selects related entity or list of entities.
     *
     * @return List of entities or one entity.
     */
    public function selectRelated(Entity $entity, string $relationName, ?Select $select = null);

    /**
     * Returns count of related records according to given parameters.
     *
     * @return A number of records.
     */
    public function countRelated(Entity $entity, string $relationName, ?Select $select = null) : int;

    /**
     * Insert an entity into DB.
     */
    public function insert(Entity $entity);

    /**
     * Insert an entity collaction.
     */
    public function massInsert(Collection $collection);

    /**
     * Update an entity in DB.
     */
    public function update(Entity $entity);

    /**
     * Delete an entity (mark as deleted).
     */
    public function delete(Entity $entity);
}
