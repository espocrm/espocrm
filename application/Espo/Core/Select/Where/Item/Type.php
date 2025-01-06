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

namespace Espo\Core\Select\Where\Item;

class Type
{
    public const AND = 'and';
    public const OR = 'or';
    public const NOT = 'not';
    public const SUBQUERY_NOT_IN = 'subQueryNotIn';
    public const SUBQUERY_IN = 'subQueryIn';
    public const EXPRESSION = 'expression';
    public const IN = 'in';
    public const NOT_IN = 'notIn';
    public const EQUALS = 'equals';
    public const NOT_EQUALS = 'notEquals';
    public const ON = 'on';
    public const NOT_ON = 'notOn';
    public const LIKE = 'like';
    public const NOT_LIKE = 'notLike';
    public const STARTS_WITH = 'startsWith';
    public const ENDS_WITH = 'endsWith';
    public const CONTAINS = 'contains';
    public const NOT_CONTAINS = 'notContains';
    public const GREATER_THAN = 'greaterThan';
    public const LESS_THAN = 'lessThan';
    public const GREATER_THAN_OR_EQUALS = 'greaterThanOrEquals';
    public const LESS_THAN_OR_EQUALS = 'lessThanOrEquals';
    public const AFTER = 'after';
    public const BEFORE = 'before';
    public const BETWEEN = 'between';
    public const EVER = 'ever';
    public const ANY = 'any';
    public const NONE = 'none';
    public const IS_NULL = 'isNull';
    public const IS_NOT_NULL = 'isNotNull';
    public const IS_TRUE = 'isTrue';
    public const IS_FALSE = 'isFalse';
    public const TODAY = 'today';
    public const PAST = 'past';
    public const FUTURE = 'future';
    public const LAST_SEVEN_DAYS = 'lastSevenDays';
    public const LAST_X_DAYS = 'lastXDays';
    public const NEXT_X_DAYS = 'nextXDays';
    public const OLDER_THAN_X_DAYS = 'olderThanXDays';
    public const AFTER_X_DAYS = 'afterXDays';
    public const CURRENT_MONTH = 'currentMonth';
    public const NEXT_MONTH = 'nextMonth';
    public const LAST_MONTH = 'lastMonth';
    public const CURRENT_QUARTER = 'currentQuarter';
    public const LAST_QUARTER = 'lastQuarter';
    public const CURRENT_YEAR = 'currentYear';
    public const LAST_YEAR = 'lastYear';
    public const CURRENT_FISCAL_YEAR = 'currentFiscalYear';
    public const LAST_FISCAL_YEAR = 'lastFiscalYear';
    public const CURRENT_FISCAL_QUARTER = 'currentFiscalQuarter';
    public const LAST_FISCAL_QUARTER = 'lastFiscalQuarter';
    public const ARRAY_ANY_OF = 'arrayAnyOf';
    public const ARRAY_NONE_OF = 'arrayNoneOf';
    public const ARRAY_ALL_OF = 'arrayAllOf';
    public const ARRAY_IS_EMPTY = 'arrayIsEmpty';
    public const ARRAY_IS_NOT_EMPTY = 'arrayIsNotEmpty';
    public const IS_LINKED_WITH = 'linkedWith';
    public const IS_NOT_LINKED_WITH = 'notLinkedWith';
    public const IS_LINKED_WITH_ALL = 'linkedWithAll';
    public const IS_LINKED_WITH_ANY = 'isLinked';
    public const IS_LINKED_WITH_NONE = 'isNotLinked';
}
