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

namespace Espo\ORM\QueryComposer;

class Functions
{
    public const FUNCTION_LIST = [
        'COUNT',
        'SUM',
        'AVG',
        'MAX',
        'MIN',
        'DATE',
        'MONTH',
        'DAY',
        'YEAR',
        'WEEK',
        'WEEK_0',
        'WEEK_1',
        'QUARTER',
        'DAYOFMONTH',
        'DAYOFWEEK',
        'DAYOFWEEK_NUMBER',
        'MONTH_NUMBER',
        'DATE_NUMBER',
        'YEAR_NUMBER',
        'HOUR_NUMBER',
        'HOUR',
        'MINUTE_NUMBER',
        'MINUTE',
        'QUARTER_NUMBER',
        'WEEK_NUMBER',
        'WEEK_NUMBER_0',
        'WEEK_NUMBER_1',
        'LOWER',
        'UPPER',
        'TRIM',
        'REPLACE',
        'LENGTH',
        'CHAR_LENGTH',
        'YEAR_0',
        'YEAR_1',
        'YEAR_2',
        'YEAR_3',
        'YEAR_4',
        'YEAR_5',
        'YEAR_6',
        'YEAR_7',
        'YEAR_8',
        'YEAR_9',
        'YEAR_10',
        'YEAR_11',
        'QUARTER_0',
        'QUARTER_1',
        'QUARTER_2',
        'QUARTER_3',
        'QUARTER_4',
        'QUARTER_5',
        'QUARTER_6',
        'QUARTER_7',
        'QUARTER_8',
        'QUARTER_9',
        'QUARTER_10',
        'QUARTER_11',
        'CONCAT',
        'LEFT',
        'TZ',
        'NOW',
        'ADD',
        'SUB',
        'MUL',
        'DIV',
        'MOD',
        'FLOOR',
        'CEIL',
        'ROUND',
        'COALESCE',
        'IF',
        'LIKE',
        'NOT_LIKE',
        'EQUAL',
        'NOT_EQUAL',
        'GREATER_THAN',
        'LESS_THAN',
        'GREATER_THAN_OR_EQUAL',
        'LESS_THAN_OR_EQUAL',
        'IS_NULL',
        'IS_NOT_NULL',
        'OR',
        'AND',
        'NOT',
        'IN',
        'NOT_IN',
        'IFNULL',
        'NULLIF',
        'BINARY',
        'UNIX_TIMESTAMP',
        'TIMESTAMPDIFF_DAY',
        'TIMESTAMPDIFF_MONTH',
        'TIMESTAMPDIFF_YEAR',
        'TIMESTAMPDIFF_WEEK',
        'TIMESTAMPDIFF_HOUR',
        'TIMESTAMPDIFF_MINUTE',
        'TIMESTAMPDIFF_SECOND',
        'POSITION_IN_LIST',
    ];

    public const COMPARISON_FUNCTION_LIST = [
        'LIKE',
        'NOT_LIKE',
        'EQUAL',
        'NOT_EQUAL',
        'GREATER_THAN',
        'LESS_THAN',
        'GREATER_THAN_OR_EQUAL',
        'LESS_THAN_OR_EQUAL',
    ];

    public const MATH_OPERATION_FUNCTION_LIST = [
        'ADD',
        'SUB',
        'MUL',
        'DIV',
        'MOD',
    ];

    public const MATCH_FUNCTION_LIST = [
        'MATCH_BOOLEAN',
        'MATCH_NATURAL_LANGUAGE',
        'MATCH_QUERY_EXPANSION',
    ];
}
