<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\ORM\Type;

class FieldType
{
    public const string VARCHAR = 'varchar';
    public const string BOOL = 'bool';
    public const string TEXT = 'text';
    public const string INT = 'int';
    public const string FLOAT = 'float';
    public const string DATE = 'date';
    public const string DATETIME = 'datetime';
    public const string DATETIME_OPTIONAL = 'datetimeOptional';
    public const string ENUM = 'enum';
    public const string MULTI_ENUM = 'multiEnum';
    public const string ARRAY = 'array';
    public const string CHECKLIST = 'checklist';
    public const string CURRENCY = 'currency';
    public const string CURRENCY_CONVERTED = 'currencyConverted';
    public const string PERSON_NAME = 'personName';
    public const string ADDRESS = 'address';
    public const string EMAIL = 'email';
    public const string PHONE = 'phone';
    public const string AUTOINCREMENT = 'autoincrement';
    public const string URL = 'url';
    public const string NUMBER = 'number';
    public const string LINK = 'link';
    public const string LINK_ONE = 'linkOne';
    public const string LINK_PARENT = 'linkParent';
    public const string FILE = 'file';
    public const string IMAGE = 'image';
    public const string LINK_MULTIPLE = 'linkMultiple';
    public const string ATTACHMENT_MULTIPLE = 'attachmentMultiple';
    public const string FOREIGN = 'foreign';
    public const string WYSIWYG = 'wysiwyg';
    public const string JSON_ARRAY = 'jsonArray';
    public const string JSON_OBJECT = 'jsonObject';
    public const string PASSWORD = 'password';

    /**
     * @since 9.3.0
     */
    public const string DECIMAL = 'decimal';

    /**
     * @since 9.3.0
     */
    public const string URL_MULTIPLE = 'urlMultiple';

    /**
     * @since 9.3.0
     */
    public const string BARCODE = 'barcode';
}
