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

namespace Espo\ORM\Defs\Params;

/**
 * A relation parameter.
 */
class RelationParam
{
    /**
     * A type.
     */
    public const TYPE = 'type';

    /**
     * Indexes.
     */
    public const INDEXES = 'indexes';

    /**
     * A relation name.
     */
    public const RELATION_NAME = 'relationName';

    /**
     * A foreign entity type.
     */
    public const ENTITY = 'entity';

    /**
     * A foreign relation name.
     */
    public const FOREIGN = 'foreign';

    /**
     * Conditions.
     */
    public const CONDITIONS = 'conditions';

    /**
     * Additional columns.
     */
    public const ADDITIONAL_COLUMNS = 'additionalColumns';

    /**
     * A key.
     */
    public const KEY = 'key';

    /**
     * A foreign key.
     */
    public const FOREIGN_KEY = 'foreignKey';

    /**
     * Middle keys.
     */
    public const MID_KEYS = 'midKeys';

    /**
     * No join.
     */
    public const NO_JOIN = 'noJoin';

    /**
     * Deferred load.
     */
    public const DEFERRED_LOAD = 'deferredLoad';
}
