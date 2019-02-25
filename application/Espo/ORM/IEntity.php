<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\ORM;
/**
 * Model interface.
 */
interface IEntity
{
    const ID = 'id';
    const VARCHAR = 'varchar';
    const INT = 'int';
    const FLOAT = 'float';
    const TEXT = 'text';
    const BOOL = 'bool';
    const FOREIGN_ID = 'foreignId';
    const FOREIGN = 'foreign';
    const FOREIGN_TYPE = 'foreignType';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const JSON_ARRAY = 'jsonArray';
    const JSON_OBJECT = 'jsonObject';
    const PASSWORD = 'password';

    const MANY_MANY = 'manyMany';
    const HAS_MANY = 'hasMany';
    const BELONGS_TO = 'belongsTo';
    const HAS_ONE = 'hasOne';
    const BELONGS_TO_PARENT = 'belongsToParent';
    const HAS_CHILDREN = 'hasChildren';

    /**
     * Push values from the array.
     * E.g. insert values into the bean from a request data.
     * @param array $arr Array of field - value pairs
     */
    function populateFromArray(array $arr);

    /**
     * Resets all fields in the current model.
     */
    function reset();

    /**
     * Set field.
     */
    function set($name, $value);

    /**
     * Get field.
     */
    function get($name);

    /**
     * Check field is set.
     */
    function has($name);

    /**
     * Clear field.
     */
    function clear($name);

}


