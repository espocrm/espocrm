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
 * A field parameter.
 */
class FieldParam
{
    /**
     * A type.
     */
    public const TYPE = 'type';

    /**
     * Not stored in database.
     */
    public const NOT_STORABLE = 'notStorable';

    /**
     * A database type.
     */
    public const DB_TYPE = 'dbType';

    /**
     * Autoincrement.
     */
    public const AUTOINCREMENT = 'autoincrement';

    /**
     * A max length.
     */
    public const MAX_LENGTH = 'maxLength';

    /**
     * Not null.
     */
    public const NOT_NULL = 'notNull';

    /**
     * A default value.
     */
    public const DEFAULT = 'default';

    /**
     * Read-only.
     */
    public const READ_ONLY = 'readOnly';

    /**
     * Decimal.
     */
    public const DECIMAL = 'decimal';

    /**
     * Precision.
     */
    public const PRECISION = 'precision';

    /**
     * Scale.
     */
    public const SCALE = 'scale';

    /**
     * Dependee attributes.
     */
    public const DEPENDEE_ATTRIBUTE_LIST = 'dependeeAttributeList';
}
