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

class HasWorkingTimeType extends Base
{
    public function process(ArgumentList $args)
    {
        if (count($args) < 2) {
            $this->throwTooFewArguments(2);
        }

        /** @var mixed[] $evaluatedArgs */
        $evaluatedArgs = $this->evaluate($args);

        $stringValue1 = $evaluatedArgs[0];
        $stringValue2 = $evaluatedArgs[1];

        if (!is_string($stringValue1)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!is_string($stringValue2)) {
            $this->throwBadArgumentType(2, 'string');
        }

        $calendar = $this->createCalendar($evaluatedArgs, 2);

        $dateTime1 = DateTimeOptional::fromString($stringValue1);
        $dateTime2 = DateTimeOptional::fromString($stringValue2);

        if ($dateTime1->isAllDay()) {
            $dateTime1 = $dateTime1->withTimezone($calendar->getTimezone());
        }

        if ($dateTime2->isAllDay()) {
            $dateTime2 = $dateTime2->withTimezone($calendar->getTimezone());
        }

        $dateTime1 = DateTime::fromDateTime($dateTime1->getDateTime());
        $dateTime2 = DateTime::fromDateTime($dateTime2->getDateTime());

        return $this->createCalendarUtility($calendar)->hasWorkingTime($dateTime1, $dateTime2);
    }
}
