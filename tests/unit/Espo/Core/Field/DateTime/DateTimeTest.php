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

namespace tests\unit\Espo\Core\Field\DateTime;

use Espo\Core\{
    Field\DateTime,
};

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use DateInterval;

class DateTimeTest extends \PHPUnit\Framework\TestCase
{
    public function testFromString()
    {
        $value = DateTime::fromString('2021-05-01 10:20:30');

        $this->assertEquals('2021-05-01 10:20:30', $value->getString());
    }

    public function testFromDateTime1()
    {
        $dt = new DateTimeImmutable('2021-05-01 10:20:30', new DateTimeZone('UTC'));

        $value = DateTime::fromDateTime($dt);

        $this->assertEquals('2021-05-01 10:20:30', $value->getString());
    }

    public function testFromDateTime2()
    {
        $dt = new DateTimeImmutable('2021-05-01 10:20:30', new DateTimeZone('Europe/Kiev'));

        $value = DateTime::fromDateTime($dt);

        $this->assertEquals('2021-05-01 07:20:30', $value->getString());
    }

    public function testBad1()
    {
        $this->expectException(RuntimeException::class);

        DateTime::fromString('2021-05-A 10:20:30');
    }

    public function testBad2()
    {
        $this->expectException(RuntimeException::class);

        DateTime::fromString('2021-05-1 10:20:30');
    }

    public function testEmpty()
    {
        $this->expectException(RuntimeException::class);

        DateTime::fromString('');
    }

    public function testGetDateTime()
    {
        $value = DateTime::fromString('2021-05-01 10:20:30');

        $this->assertEquals('2021-05-01', $value->getDateTime()->format('Y-m-d'));
    }

    public function testGetMethods()
    {
        $value = DateTime::fromString('2021-05-01 10:20:30');

        $dt = new DateTimeImmutable('2021-05-01 10:20:30', new DateTimeZone('UTC'));

        $this->assertEquals(1, $value->getDay());
        $this->assertEquals(5, $value->getMonth());
        $this->assertEquals(2021, $value->getYear());
        $this->assertEquals(6, $value->getDayOfWeek());
        $this->assertEquals(10, $value->getHour());
        $this->assertEquals(20, $value->getMinute());
        $this->assertEquals(30, $value->getSecond());

        $this->assertEquals($dt->getTimestamp(), $value->getTimestamp());
    }

    public function testAdd()
    {
        $value = DateTime::fromString('2021-05-01 10:20:30');

        $modifiedValue = $value->add(DateInterval::createFromDateString('1 day'));

        $this->assertEquals('2021-05-02 10:20:30', $modifiedValue->getString());

        $this->assertNotSame($modifiedValue, $value);
    }

    public function testSubtract()
    {
        $value = DateTime::fromString('2021-05-01 10:20:30');

        $modifiedValue = $value->subtract(DateInterval::createFromDateString('1 day'));

        $this->assertEquals('2021-04-30 10:20:30', $modifiedValue->getString());

        $this->assertNotSame($modifiedValue, $value);
    }

    public function testModify()
    {
        $value = DateTime::fromString('2021-05-01 10:20:30');

        $modifiedValue = $value->modify('+1 month');

        $this->assertEquals('2021-06-01 10:20:30', $modifiedValue->getString());

        $this->assertNotSame($modifiedValue, $value);
    }

    public function testWithTimezone()
    {
        $value = DateTime
            ::fromString('2021-05-01 10:20:30')
            ->withTimezone(new DateTimeZone('Europe/Kiev'));

        $this->assertEquals('2021-05-01 10:20:30', $value->getString());

        $this->assertEquals(13, $value->getHour());
    }

    public function getGetTimezone()
    {
        $value = DateTime
            ::fromString('2021-05-01 10:20:30')
            ->withTimezone(new DateTimeZone('Europe/Kiev'));

        $this->assertEquals(new DateTimeZone('Europe/Kiev'), $value->getTimezone());
    }

    public function testDiff(): void
    {
        $value1 = DateTime::fromString('2021-05-01 10:10:30');
        $value2 = DateTime::fromString('2021-05-01 10:20:30');

        $this->assertEquals(10, $value1->diff($value2)->i);
        $this->assertEquals(0, $value1->diff($value2)->invert);
    }

    public function testNow(): void
    {
        $value = DateTime::createNow();

        $this->assertNotNull($value);
    }
}
