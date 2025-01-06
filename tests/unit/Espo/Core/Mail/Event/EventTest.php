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

namespace tests\unit\Espo\Core\Mail\Event;

use ICal\ICal;
use ICal\Event;

use Espo\Core\Mail\Event\EventFactory;
use Espo\Core\Mail\Event\Event as MailEvent;

class EventTest extends \PHPUnit\Framework\TestCase
{
    private $icsContents1 =
"BEGIN:VCALENDAR
METHOD:REQUEST
PRODID:Microsoft Exchange Server 2010
VERSION:2.0
BEGIN:VTIMEZONE
TZID:Jordan Standard Time
BEGIN:STANDARD
DTSTART:16010101T010000
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1FR;BYMONTH=10
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:16010101T235959
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1TH;BYMONTH=3
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VEVENT
ORGANIZER;CN=Test Org:MAILTO:test-org@test.com
ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=Test-1:MAIL
 TO:test-1@test.com
ATTENDEE;ROLE=REQ-PARTICIPANT;CN=Test-2:MAILTO:test-2@test.com
UID:040000008200E00074C5B7101A82E008000000004679AD350342D501000000000000000
 010000000BB4B99DDF6B4934B8DC8B8BA92CF3645
SUMMARY;LANGUAGE=en-US:test2
DTSTART;TZID=Jordan Standard Time:20190729T180000
DTEND;TZID=Jordan Standard Time:20190729T183000
CLASS:PUBLIC
PRIORITY:5
DTSTAMP:20190724T093603Z
TRANSP:OPAQUE
STATUS:CONFIRMED
SEQUENCE:0
LOCATION;LANGUAGE=en-US:
X-MICROSOFT-CDO-APPT-SEQUENCE:0
X-MICROSOFT-CDO-OWNERAPPTID:2117584454
X-MICROSOFT-CDO-BUSYSTATUS:TENTATIVE
X-MICROSOFT-CDO-INTENDEDSTATUS:BUSY
X-MICROSOFT-CDO-ALLDAYEVENT:FALSE
X-MICROSOFT-CDO-IMPORTANCE:1
X-MICROSOFT-CDO-INSTTYPE:0
X-MICROSOFT-DONOTFORWARDMEETING:FALSE
X-MICROSOFT-DISALLOW-COUNTER:FALSE
X-MICROSOFT-LOCATIONS:[]
BEGIN:VALARM
DESCRIPTION:REMINDER
TRIGGER;RELATED=START:-PT15M
ACTION:DISPLAY
END:VALARM
END:VEVENT
END:VCALENDAR
";

    private $icsContents2 =
"BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
BEGIN:VEVENT
SUMMARY:Test-2a
DTSTART;TZID=America/New_York:20130802T103400
DTEND;TZID=America/New_York:20130802T110400
LOCATION:1 Broadway Ave.\, Brooklyn
DESCRIPTION: Description Test 1
STATUS:CONFIRMED
SEQUENCE:3
BEGIN:VALARM
TRIGGER:-PT10M
DESCRIPTION:Pickup Reminder
ACTION:DISPLAY
END:VALARM
END:VEVENT
END:VCALENDAR
";

    private $icsContents3 =
"BEGIN:VCALENDAR
PRODID:-//Google Inc//Google Calendar 70.9054//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
DTSTART;VALUE=DATE:20210810
DTEND;VALUE=DATE:20210811
DTSTAMP:20210810T091857Z
ORGANIZER;CN=test:mailto:test@group.calendar.google.c
 om
UID:4r15namb5v2h4dou58gkfajjbe@google.com
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=
 TRUE;CN=test.com;X-NUM-GUESTS=0:mailto:test@test.com
X-MICROSOFT-CDO-OWNERAPPTID:1443094082
CREATED:20210810T091748Z
LAST-MODIFIED:20210810T091856Z
LOCATION:
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:test ics 4
TRANSP:TRANSPARENT
END:VEVENT
END:VCALENDAR";

    public function testEvent1(): void
    {
        $ical = new ICal();

        $ical->initString($this->icsContents1);

        /* @var $event Event */
        $event = $ical->events()[0];

        $espoEvent = MailEvent::create()
            ->withUid($event->uid ?? null)
            ->withDateStart($event->dtstart_tz ?? null)
            ->withDateEnd($event->dtend_tz ?? null)
            ->withName($event->summary ?? null)
            ->withLocation($event->location ?? null)
            ->withDescription($event->description ?? null)
            ->withTimezone($ical->calendarTimeZone() ?? null)
            ->withOrganizer($event->organizer)
            ->withAttendees($event->attendee);

        $this->assertEquals(
            '040000008200E00074C5B7101A82E008000000004679AD350342D501000000000000000' .
            '010000000BB4B99DDF6B4934B8DC8B8BA92CF3645',
            $espoEvent->getUid()
        );

        $this->assertEquals('2019-07-29 15:00:00', $espoEvent->getDateStart());

        $this->assertEquals('test-org@test.com', $espoEvent->getOrganizerEmailAddress());

        $this->assertEquals(['test-1@test.com', 'test-2@test.com'], $espoEvent->getAttendeeEmailAddressList());
    }

    public function testEvent2(): void
    {
        $ical = new ICal();

        $ical->initString($this->icsContents2);

        /* @var $event Event */
        $event = $ical->events()[0];

        $espoEvent = MailEvent::create()
            ->withUid($event->uid ?? null)
            ->withDateStart($event->dtstart_tz ?? null)
            ->withDateEnd($event->dtend_tz ?? null)
            ->withName($event->summary ?? null)
            ->withLocation($event->location ?? null)
            ->withDescription($event->description ?? null)
            ->withTimezone($ical->calendarTimeZone() ?? null)
            ->withOrganizer($event->organizer)
            ->withAttendees($event->attendee);

        $this->assertEquals(
            null,
            $espoEvent->getUid()
        );

        $this->assertEquals('2013-08-02 14:34:00', $espoEvent->getDateStart());

        $this->assertEquals(null, $espoEvent->getOrganizerEmailAddress());

        $this->assertEquals([], $espoEvent->getAttendeeEmailAddressList());

        $this->assertEquals(
            "1 Broadway Ave., Brooklyn",
            $espoEvent->getLocation()
        );

        $this->assertFalse($espoEvent->isAllDay());
    }

    public function testEvent3(): void
    {
        $ical = new ICal();

        $ical->initString($this->icsContents3);

        $event = EventFactory::createFromU01jmg3Ical($ical);

        $this->assertTrue($event->isAllDay());
        $this->assertEquals('2021-08-10', $event->getDateStart());
        $this->assertEquals('2021-08-10', $event->getDateEnd());
    }
}
