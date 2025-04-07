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

namespace Espo\Tools\DynamicLogic;

enum Type: string
{
    case And = 'and';
    case Or = 'or';
    case Not = 'not';
    case Equals = 'equals';
    case NotEquals = 'notEquals';
    case IsEmpty = 'isEmpty';
    case IsNotEmpty = 'isNotEmpty';
    case IsTrue = 'isTrue';
    case IsFalse = 'isFalse';
    case Contains = 'contains';
    case Has = 'has';
    case NotContains = 'notContains';
    case NotHas = 'notHas';
    case StartsWith = 'startsWith';
    case EndsWith = 'endsWith';
    case Matches = 'matches';
    case GreaterThan = 'greaterThan';
    case LessThan = 'lessThan';
    case GreaterThanOrEquals = 'greaterThanOrEquals';
    case LessThanOrEquals = 'lessThanOrEquals';
    case In = 'in';
    case NotIn = 'notIn';
    case IsToday = 'isToday';
    case InFuture = 'inFuture';
    case InPast = 'inPast';
}
