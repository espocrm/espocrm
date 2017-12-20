<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
use Espo\ORM\IEntity;
use Espo\ORM\Classes\EntityFactory;

interface IMapper
{
    /**
     * Selects bean by id.
     *
     * @param IEntity $entity
     * @param string $id Id of the needed bean
     * @return IEntity $entity
     */
    function selectById(IEntity $entity, $id);

    /**
     * Selects list of beans according to given parameters.
     *
     * @param IEntity $entity
     * @param array $params Parameters (whereClause, offset, limit, orderBy, order, customWhere, joins, distinct)
     * @return array Array of beans
     */
    function select(IEntity $entity, $params);

    /**
     * Invokes aggregate function and returns a value.
     *
     * @param IEntity $entity
     * @param array $params Parameters (whereClause, joins, distinct, customWhere, customJoin)
     * @param string $aggregation Aggregate function (COUNT, MAX, MIN, SUM, AVG)
     * @param string $aggregationBy Field to aggregate
     * @param bool $deleted True to consider records marked as deleted either.
     * @return mixed Result of the aggregation
     */
    function aggregate(IEntity $entity, $params, $aggregation, $aggregationBy, $deleted);

    /**
     * Returns count of records according to given parameters.
     *
     * @param IEntity $entity
     * @param array $params Parameters (ordering, and limitig are not used)
     * @return int Count of record
     */
    function count(IEntity $entity, $params);

    /**
     * Returns max value of the field in the select according to given parameters.
     *
     * @param IEntity $entity
     * @param array $params Parameters
     * @param string $field Needed field.
     * @param bool $deleted True to consider records marked as deleted either.
     * @return mixed Max value
     */
    function max(IEntity $entity, $params, $field, $deleted);

    /**
     * Returns min value of the field in the select according to given parameters.
     *
     * @param IEntity $entity
     * @param array $params Parameters
     * @param string $field Needed field.
     * @param bool $deleted True to consider records marked as deleted either.
     * @return mixed Min value
     */
    function min(IEntity $entity, $params, $field, $deleted);

    /**
     * Returns sum value of the field in the select according to given parameters.
     *
     * @param IEntity $entity
     * @param array $params Parameters
     * @param string $field Needed field.
     * @param bool $deleted True to consider records marked as deleted either.
     * @return mixed Sum value
     */
    function sum(IEntity $entity, $params);

    /**
     * Selects related bean or list of beans.
     *
     * @param IEntity $entity
     * @param string $relName Relation name
     * @param array $params (whereClause, offset, limit, orderBy, order, customWhere)
     * @param bool $totalCount used by DB::countRelated to make this method return total count
     * @return array List of beans or total count if $totalCount was passed as true
     */
    function selectRelated(IEntity $entity, $relName, $params, $totalCount);

    /**
     * Returns count of related records according to given parameters.
     *
     * @param IEntity $entity
     * @param string $relName Relation name
     * @param array $params (whereClause, customWhere)
     * @return int Count of records
     */
    function countRelated(IEntity $entity, $relName, $params);

    /**
     * Links the bean with another one.
     *
     * @param IEntity $entity
     * @param string $relName Relation name
     * @param string $id Id of the foreign record.
     * @return bool True if success
     */
    function addRelation(IEntity $entity, $relName, $id);

    /**
     * Removes relation of bean with certain record.
     *
     * @param IEntity $entity
     * @param string $relName Relation name
     * @param string $id Id of the foreign record.
     * @return bool True if success
     */
    function removeRelation(IEntity $entity, $relName, $id);

    /**
     * Removes all relations of bean of specified relation name.
     *
     * @param IEntity $entity
     * @param string $relName Relation name
     * @return bool True if success
     */
    function removeAllRelations(IEntity $entity, $relName);

    /**
     * Insert the bean into db.
     *
     * @param IEntity $entity
     * @return bool True if success
     */
    function insert(IEntity $entity);

    /**
     * Updates the bean in db.
     *
     * @param IEntity $entity
     * @return bool True if success
     */
    function update(IEntity $entity);


    /**
     * Deletes the bean.
     * (Marks as deleted)
     *
     * @param IEntity $entity
     * @return bool True if success
     */
    function delete(IEntity $entity);

    /**
     * Sets class name of a model collection that will be returned by operations such as select.
     *
     * @param string $collectionClass Class name of a model collection.
     */
    function setCollectionClass($collectionClass);
}


