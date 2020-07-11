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

namespace Espo\ORM\DB;

use Espo\ORM\{
    IEntity as Entity,
    ICollection as Collection,
};

interface Mapper
{
    /**
     * Select an entity by ID.
     */
    public function selectById(Entity $entity, $id, ?array $params = null) : ?Entity;

    /**
     * Select a list of entities according to given parameters.
     */
    public function select(Entity $entity, ?array $params = null) : Collection;

    /**
     * Invoke an aggregate function and return a result value.
     *
     * @return mixed Result of an aggregation.
     */
    public function aggregate(Entity $entity, ?array $params, string $aggregation, string $aggregationBy);

    /**
     * Returns count of records according to given parameters.
     *
     * @return Record count.
     */
    public function count(Entity $entity, ?array $params = null) : int;

    /**
     * Returns max value of the attribute in the select according to given parameters.
     *
     * @return mixed Max value.
     */
    public function max(Entity $entity, ?array $params, string $attribute);

    /**
     * Returns a min value of the attribute in the select according to given parameters.
     *
     * @return mixed Min value.
     */
    public function min(Entity $entity, ?array $params, string $attribute);

    /**
     * Returns a sum value of the attribute in the select according to given parameters.
     *
     * @return mixed Sum value.
     */
    function sum(Entity $entity, ?array $params, string $attribute);

    /**
     * Selects related entity or list of entities.
     *
     * @return List of entities or total count if $totalCount was passed as true.
     */
    public function selectRelated(Entity $entity, string $relationName, array $params = [], bool $returnTotalCount = false);

    /**
     * Returns count of related records according to given parameters.
     *
     * @return int Count of records.
     */
    public function countRelated(Entity $entity, string $relationName, array $params) : int;

    /**
     * Links entity with another one.
     *
     * @return bool True if success
     */
    public function addRelation(
        Entity $entity, string $relationName, ?string $id = null, ?Entity $relEntity = null, ?array $data = null
    );

    /**
     * Remove relation of entity with certain record.
     *
     * @return bool True if success.
     */
    public function removeRelation(
        Entity $entity, string $relationName, ?string $id = null, bool $all = false, ?Entity $relEntity = null
    ) : bool;

    /**
     * Remove all relations of entity of specified relation name.
     *
     * @return True if success.
     */
    public function removeAllRelations(Entity $entity, string $relationName);

    /**
     * Insert an entity into DB.
     *
     * @return Record ID if success.
     */
    public function insert(Entity $entity);

    /**
     * Update an entity in DB.
     *
     * @return Recotd ID if success.
     */
    public function update(Entity $entity) : ?string;


    /**
     * Delete an entity (mark as deleted).
     *
     * @return TRUE if success.
     */
    public function delete(Entity $entity) : bool;

    /**
     * Delete a record from DB.
     */
    public function deleteFromDb(string $entityType, string $id, bool $onlyDeleted = false) : bool;
}
