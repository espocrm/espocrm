<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Select\Where\Item;

class Type
{
    public const string AND = 'and';
    public const string OR = 'or';
    public const string NOT = 'not';
    public const string SUBQUERY_NOT_IN = 'subQueryNotIn';
    public const string SUBQUERY_IN = 'subQueryIn';
    public const string EXPRESSION = 'expression';
    public const string IN = 'in';
    public const string NOT_IN = 'notIn';
    public const string EQUALS = 'equals';
    public const string NOT_EQUALS = 'notEquals';
    public const string ON = 'on';
    public const string NOT_ON = 'notOn';
    public const string LIKE = 'like';
    public const string NOT_LIKE = 'notLike';
    public const string STARTS_WITH = 'startsWith';
    public const string ENDS_WITH = 'endsWith';
    public const string CONTAINS = 'contains';
    public const string NOT_CONTAINS = 'notContains';
    public const string GREATER_THAN = 'greaterThan';
    public const string LESS_THAN = 'lessThan';
    public const string GREATER_THAN_OR_EQUALS = 'greaterThanOrEquals';
    public const string LESS_THAN_OR_EQUALS = 'lessThanOrEquals';
    public const string AFTER = 'after';
    public const string BEFORE = 'before';
    public const string BETWEEN = 'between';
    public const string EVER = 'ever';
    public const string ANY = 'any';
    public const string NONE = 'none';
    public const string IS_NULL = 'isNull';
    public const string IS_NOT_NULL = 'isNotNull';
    public const string IS_TRUE = 'isTrue';
    public const string IS_FALSE = 'isFalse';
    public const string TODAY = 'today';
    public const string PAST = 'past';
    public const string FUTURE = 'future';
    public const string LAST_SEVEN_DAYS = 'lastSevenDays';
    public const string LAST_X_DAYS = 'lastXDays';
    public const string NEXT_X_DAYS = 'nextXDays';
    public const string OLDER_THAN_X_DAYS = 'olderThanXDays';
    public const string AFTER_X_DAYS = 'afterXDays';
    public const string CURRENT_MONTH = 'currentMonth';
    public const string NEXT_MONTH = 'nextMonth';
    public const string LAST_MONTH = 'lastMonth';
    public const string CURRENT_QUARTER = 'currentQuarter';
    public const string LAST_QUARTER = 'lastQuarter';
    public const string CURRENT_YEAR = 'currentYear';
    public const string LAST_YEAR = 'lastYear';
    public const string CURRENT_FISCAL_YEAR = 'currentFiscalYear';
    public const string LAST_FISCAL_YEAR = 'lastFiscalYear';
    public const string CURRENT_FISCAL_QUARTER = 'currentFiscalQuarter';
    public const string LAST_FISCAL_QUARTER = 'lastFiscalQuarter';
    public const string ARRAY_ANY_OF = 'arrayAnyOf';
    public const string ARRAY_NONE_OF = 'arrayNoneOf';
    public const string ARRAY_ALL_OF = 'arrayAllOf';
    public const string ARRAY_IS_EMPTY = 'arrayIsEmpty';
    public const string ARRAY_IS_NOT_EMPTY = 'arrayIsNotEmpty';

    /**
     * Is linked with at least one of provided values (Any Of).
     */
    public const string IS_LINKED_WITH = 'linkedWith';

    /**
     * Is not linked with any of provided values (None Of).
     */
    public const string IS_NOT_LINKED_WITH = 'notLinkedWith';

    /**
     * Is linked with all of provided values (All Of).
     */
    public const string IS_LINKED_WITH_ALL = 'linkedWithAll';

    /**
     * Is linked with anything (Is Not Empty).
     */
    public const string IS_LINKED_WITH_ANY = 'isLinked';

    /**
     * Is not linked with anything (Is Empty).
     */
    public const string IS_LINKED_WITH_NONE = 'isNotLinked';
}
