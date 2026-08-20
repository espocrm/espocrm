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

namespace tests\integration\Espo\Event;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\DateTimeOptional;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use tests\integration\Core\BaseTestCase;

class EventTest extends BaseTestCase
{
    public function testMeetingAutomaticDateEnd(): void
    {
        $em = $this->getEntityManager();

        $meeting = $em->getRDBRepositoryByClass(Meeting::class)->getNew();
        $meeting->setDateStart(DateTimeOptional::fromString('2030-01-01 10:00'));
        $meeting->setDuration(60);
        $em->saveEntity($meeting);
        $em->refreshEntity($meeting);

        $this->assertEquals('2030-01-01 10:01:00', $meeting->getDateEnd()?->toString());

        //

        $meeting = $em->getRDBRepositoryByClass(Meeting::class)->getNew();
        $meeting->setDateStart(DateTimeOptional::fromString('2030-01-01 10:00'));
        $meeting->setDateEnd(DateTimeOptional::fromString('2030-01-01 11:00'));
        $em->saveEntity($meeting);
        $em->refreshEntity($meeting);

        $this->assertEquals('2030-01-01 11:00:00', $meeting->getDateEnd()?->toString());

        //

        $meeting = $em->getRDBRepositoryByClass(Meeting::class)->getNew();
        $meeting->setIsAllDay(true);
        $meeting->setDateStart(DateTimeOptional::fromString('2030-01-01'));
        $meeting->setDuration(3600 * 24 * 2);
        $em->saveEntity($meeting);
        $em->refreshEntity($meeting);

        $this->assertEquals('2030-01-02', $meeting->getDateEnd()?->toString());

        //

        $meeting = $em->getRDBRepositoryByClass(Meeting::class)->getNew();
        $meeting->setIsAllDay(true);
        $meeting->setDateStart(DateTimeOptional::fromString('2030-01-01'));
        $meeting->setDateEnd(DateTimeOptional::fromString('2030-01-02'));
        $em->saveEntity($meeting);
        $em->refreshEntity($meeting);

        $this->assertEquals('2030-01-02', $meeting->getDateEnd()?->toString());
    }

    public function testCallAutomaticDateEnd(): void
    {
        $em = $this->getEntityManager();

        $call = $em->getRDBRepositoryByClass(Call::class)->getNew();

        $call->setDateStart(DateTime::fromString('2030-01-01 10:00'));
        $call->setDuration(60);

        $em->saveEntity($call);

        $em->refreshEntity($call);

        $this->assertEquals('2030-01-01 10:01:00', $call->getDateEnd()?->toString());
    }
}
