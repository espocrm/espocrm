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

namespace tests\integration\Espo\Tools\WorkingTime;

use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Entities\WorkingTimeCalendar;
use Espo\Tools\WorkingTime\CalendarUtilityFactory;
use tests\integration\Core\BaseTestCase;

class UtilityTest extends BaseTestCase
{
    public function testUtilityUser(): void
    {
        $em = $this->getEntityManager();

        $calendar = $em->createEntity(WorkingTimeCalendar::ENTITY_TYPE);

        $user = $this->createUser('test');
        $user->set('workingTimeCalendarId', $calendar->getId());
        $em->saveEntity($user);

        $utility = $this->getInjectableFactory()
            ->create(CalendarUtilityFactory::class)
            ->createForUser($user);

        $this->assertTrue($utility->isWorkingDay(DateTime::fromString('2024-02-23 00:00')));
        $this->assertFalse($utility->isWorkingDay(DateTime::fromString('2024-02-24 00:00')));
        $this->assertFalse($utility->isWorkingDay(DateTime::fromString('2024-02-25 00:00')));
        $this->assertTrue($utility->isWorkingDay(DateTime::fromString('2024-02-26 00:00')));
    }

    public function testUtilityGlobal(): void
    {
        $em = $this->getEntityManager();

        $calendar = $em->createEntity(WorkingTimeCalendar::ENTITY_TYPE);

        $configWriter = $this->getInjectableFactory()->create(ConfigWriter::class);
        $configWriter->set('workingTimeCalendarId', $calendar->getId());
        $configWriter->save();

        $utility = $this->getInjectableFactory()
            ->create(CalendarUtilityFactory::class)
            ->createGlobal();

        $this->assertTrue($utility->isWorkingDay(DateTime::fromString('2024-02-23 00:00')));
        $this->assertFalse($utility->isWorkingDay(DateTime::fromString('2024-02-24 00:00')));
        $this->assertFalse($utility->isWorkingDay(DateTime::fromString('2024-02-25 00:00')));
        $this->assertTrue($utility->isWorkingDay(DateTime::fromString('2024-02-26 00:00')));
    }
}
