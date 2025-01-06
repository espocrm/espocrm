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

namespace tests\unit\Espo\Tools\Meeting;

use Espo\Modules\Crm\Business\Event\Ics;
use PHPUnit\Framework\TestCase;

class IcsTest extends TestCase
{
    public function testIcs1(): void
    {
        $ics = new Ics('//EspoCRM//EspoCRM Calendar//EN', [
            'method' => Ics::METHOD_REQUEST,
            'status' => Ics::STATUS_CONFIRMED,
            'startDate' => strtotime('2025-01-01 10:00:00'),
            'endDate' => strtotime('2025-01-01 11:00:00'),
            'uid' => 'test-id',
            'summary' => 'Test',
            'organizer' => ['hello@test.com', 'Hello Test'],
            'description' => 'Test.',
            'stamp' => strtotime('2025-01-01 09:00:00'),
            'attendees' => [
                ['att1@test.com', 'Att 1'],
                ['att2@test.com', 'Att 2'],
            ],
        ]);

        $expected =
            "BEGIN:VCALENDAR\r\n".
            "VERSION:2.0\r\n".
            "PRODID:-//EspoCRM//EspoCRM Calendar//EN\r\n".
            "METHOD:REQUEST\r\n".
            "BEGIN:VEVENT\r\n".
            "DTSTART:20250101T100000Z\r\n".
            "DTEND:20250101T110000Z\r\n".
            "SUMMARY:Test\r\n".
            "LOCATION:\r\n".
            "ORGANIZER;CN=Hello Test:MAILTO:hello@test.com\r\n".
            "DESCRIPTION:Test.\r\n".
            "UID:test-id\r\n".
            "SEQUENCE:0\r\n".
            "DTSTAMP:20250101T090000Z\r\n".
            "STATUS:CONFIRMED\r\n".
            "ATTENDEE;CN=Att 1:MAILTO:att1@test.com\r\n".
            "ATTENDEE;CN=Att 2:MAILTO:att2@test.com\r\n".
            "END:VEVENT\r\n".
            "END:VCALENDAR";

        $this->assertEquals($expected, $ics->get());
    }
}
