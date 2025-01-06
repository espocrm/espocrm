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

namespace Espo\Core\Mail\Event;

use ICal\Event as ICalEvent;
use ICal\ICal as U01jmg3ICal;

use RuntimeException;

class EventFactory
{
    public static function createFromU01jmg3Ical(U01jmg3ICal $ical): Event
    {
        /* @var ?ICalEvent $event */
        $event = $ical->events()[0] ?? null;

        if (!$event) {
            throw new RuntimeException();
        }

        $dateStart = $event->dtstart_tz ?? null;
        $dateEnd = $event->dtend_tz ?? null;

        $isAllDay = strlen($event->dtstart) === 8;

        if ($isAllDay) {
            $dateStart = $event->dtstart ?? null;
            $dateEnd = $event->dtend ?? null;
        }

        return Event::create()
            ->withUid($event->uid ?? null)
            ->withIsAllDay($isAllDay)
            ->withDateStart($dateStart)
            ->withDateEnd($dateEnd)
            ->withName($event->summary ?? null)
            ->withLocation($event->location ?? null)
            ->withDescription($event->description ?? null)
            ->withTimezone($ical->calendarTimeZone() ?? null) /** @phpstan-ignore-line */
            ->withOrganizer($event->organizer ?? null)
            ->withAttendees($event->attendee ?? null);
    }
}
