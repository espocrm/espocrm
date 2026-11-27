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

namespace tests\unit\Espo\Classes\FieldValidators\WorkingTimeCalendar\TimeRanges;

use Espo\Classes\FieldValidators\WorkingTimeCalendar\TimeRanges\Valid;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\ORM\Entity;
use PHPUnit\Framework\TestCase;

class ValidTest extends TestCase
{
    public function testValid(): void
    {
        $values = [
            null,
            [],
            [['09:00', '17:00']],
            [['09:00', '13:00'], ['13:00', '00:00']],
            [['00:00', '00:00']],
            [['00:00', '08:30'], ['22:00', '23:30']],
        ];

        foreach ($values as $value) {
            $entity = $this->createMock(Entity::class);
            $entity->method('get')->with('timeRanges')->willReturn($value);

            $failure = new Valid()->validate($entity, 'timeRanges', new Data((object) []));

            $this->assertNull($failure, var_export($value, true));
        }
    }

    public function testInvalid(): void
    {
        $values = [
            '09:00',
            [['09:00']],
            [['09:00', null]],
            [['9:00', '17:00']],
            [['09:00', '24:00']],
            [['17:00', '09:00']],
            [['09:00', '09:00']],
            [['09:00', '13:00'], ['12:00', '17:00']],
            [['14:00', '17:00'], ['09:00', '13:00']],
            [['09:00', '00:00'], ['10:00', '11:00']],
        ];

        foreach ($values as $value) {
            $entity = $this->createMock(Entity::class);
            $entity->method('get')->with('timeRanges')->willReturn($value);

            $failure = new Valid()->validate($entity, 'timeRanges', new Data((object) []));

            $this->assertNotNull($failure, var_export($value, true));
        }
    }
}
