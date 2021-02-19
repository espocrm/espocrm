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

namespace Espo\ORM;

/**
 * An entity. Represents a single record in DB.
 */
interface Entity
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
     * Get an entity ID.
     */
    public function getId() : ?string;

    /**
     * Reset all attributes (empty an entity).
     */
    public function reset() : void;

    /**
     * Set an attribute or multiple attributes.
     *
     * Two usage options:
     * * `set(string $name, mixed $value)`
     * * `set(array|object $valueMap)`
     *
     * @param string|object|array $name
     * @param ?mixed $value
     */
    public function set($name, $value = null) : void;

    /**
     * Get an attribute value.
     *
     * @retrun ?mixed
     */
    public function get(string $name);

    /**
     * Whether an attribute value is set.
     */
    public function has(string $name) : bool;

    /**
     * Clear an attribute value.
     */
    public function clear(string $name) : void;

    /**
     * Get an entity type.
     */
    public function getEntityType() : string;
}
