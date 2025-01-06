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

namespace Espo\Core\Formula\Functions\ExtGroup\WorkingTimeGroup;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\DateTimeOptional;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Utils\DateTime as DateTimeUtil;

class AddWorkingDaysType extends Base
{
    public function process(ArgumentList $args)
    {
        if (count($args) < 2) {
            $this->throwTooFewArguments(2);
        }

        /** @var mixed[] $evaluatedArgs */
        $evaluatedArgs = $this->evaluate($args);

        $stringValue = $evaluatedArgs[0];
        $days = $evaluatedArgs[1];

        if (!is_string($stringValue)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!is_int($days) && !is_float($days)) {
            $this->throwBadArgumentType(2, 'int');
        }

        if (is_float($days)) {
            $days = (int) $days;
        }

        if ($days <= 0) {
            $this->throwBadArgumentValue(2, 'Days value should be greater than 0.');
        }

        $calendar = $this->createCalendar($evaluatedArgs, 2);

        $dateTime = DateTimeOptional::fromString($stringValue);

        $isAllDay = $dateTime->isAllDay();

        if ($isAllDay) {
            $dateTime = $dateTime->withTimezone($calendar->getTimezone());
        }

        $dateTime = DateTime::fromDateTime($dateTime->toDateTime());

        $result = $this->createCalendarUtility($calendar)->addWorkingDays($dateTime, $days);

        if (!$result) {
            return null;
        }

        if ($isAllDay) {
            return $result->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT);
        }

        return $result->toString();
    }
}
