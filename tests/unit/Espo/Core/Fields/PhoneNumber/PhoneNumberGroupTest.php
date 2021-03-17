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

namespace tests\unit\Espo\Core\Fields\PhoneNumber;

use Espo\Core\{
    Fields\PhoneNumber,
    Fields\PhoneNumberGroup,
};

use RuntimeException;

class PhoneNumberGroupTest extends \PHPUnit\Framework\TestCase
{
    public function testEmpty()
    {
        $group = PhoneNumberGroup::fromNothing();

        $this->assertEquals(0, count($group->getNumberList()));
        $this->assertEquals(0, count($group->getSecondaryList()));

        $this->assertNull($group->getPrimary());

        $this->assertEquals(0, $group->getCount());
    }

    public function testDuplicate()
    {
        $this->expectException(RuntimeException::class);

        PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+100'),
            ]);
    }

    public function testWithPrimary1()
    {
        $primary = PhoneNumber::fromNumber('+000')->invalid();

        $group = PhoneNumberGroup
            ::fromNothing()
            ->withPrimary($primary);

        $this->assertEquals(1, count($group->getList()));
        $this->assertEquals(0, count($group->getSecondaryList()));

        $this->assertNotNull($group->getPrimary());

        $this->assertEquals('+000', $group->getPrimary()->getNumber());

        $primaryAnother = PhoneNumber::fromNumber('+001');

        $groupAnother = $group->withPrimary($primaryAnother);

        $this->assertEquals(2, count($groupAnother->getList()));
        $this->assertEquals(1, count($groupAnother->getSecondaryList()));

        $this->assertEquals('+001', $groupAnother->getPrimary()->getNumber());

        $this->assertEquals('+000', $groupAnother->getList()[1]->getNumber());
        $this->assertTrue($groupAnother->getList()[1]->isInvalid());

        $this->assertTrue($groupAnother->hasNumber('+000'));
        $this->assertTrue($groupAnother->hasNumber('+001'));
    }

    public function testWithPrimary2()
    {
        $number = PhoneNumber::fromNumber('+100')->invalid();

        $group = PhoneNumberGroup
            ::fromList([$number])
            ->withAdded(
                PhoneNumber::fromNumber('+200')
            )
            ->withPrimary(
                PhoneNumber::fromNumber('+200')
            );

        $this->assertEquals('+200', $group->getPrimary()->getNumber());

        $this->assertEquals(2, count($group->getList()));
    }

    public function testWithAdded1()
    {
        $number = PhoneNumber::fromNumber('+100')->invalid();

        $group = PhoneNumberGroup
            ::fromList([$number])
            ->withAdded(
                PhoneNumber::fromNumber('+200')
            );

        $this->assertEquals('+100', $group->getPrimary()->getNumber());

        $this->assertEquals(2, count($group->getList()));

        $this->assertEquals(2, $group->getCount());
    }

    public function testWithAddedList()
    {
        $group = PhoneNumberGroup
            ::fromNothing()
            ->withAddedList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+200'),
            ]);

        $this->assertEquals('+100', $group->getPrimary()->getNumber());

        $this->assertEquals(2, count($group->getList()));
    }

    public function testWithRemoved1()
    {
        $group = PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+200'),
                PhoneNumber::fromNumber('+300'),
            ])
            ->withRemoved(PhoneNumber::fromNumber('+200'));

        $this->assertEquals('+100', $group->getPrimary()->getNumber());

        $this->assertEquals(2, count($group->getList()));
    }

    public function testWithRemoved2()
    {
        $group = PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+200'),
                PhoneNumber::fromNumber('+300'),
            ])
            ->withRemoved(PhoneNumber::fromNumber('+100'));

        $this->assertEquals('+200', $group->getPrimary()->getNumber());

        $this->assertEquals(2, count($group->getList()));
    }

    public function testWithRemoved3()
    {
        $group = PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
            ])
            ->withRemoved(PhoneNumber::fromNumber('+100'));

        $this->assertNull($group->getPrimary());

        $this->assertEquals(0, count($group->getList()));

        $this->assertEquals(0, $group->getCount());
    }

    public function testHasNumber()
    {
        $group = PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+200'),
            ]);

        $this->assertTrue($group->hasNumber('+100'));
        $this->assertTrue($group->hasNumber('+200'));

        $this->assertFalse($group->hasNumber('+400'));
    }

    public function testGetByNumber()
    {
        $group = PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+200'),
                PhoneNumber::fromNumber('+300'),
            ]);

        $this->assertEquals('+100', $group->getByNumber('+100')->getNumber());

        $this->assertNull($group->getByNumber('+400'));
    }

    public function testClone()
    {
        $group = PhoneNumberGroup
            ::fromList([
                PhoneNumber::fromNumber('+100'),
                PhoneNumber::fromNumber('+200'),
                PhoneNumber::fromNumber('+300'),
            ]);

        $cloned = clone $group;

        $this->assertEquals('+100', $cloned->getByNumber('+100')->getNumber());

        $this->assertEquals($cloned->getPrimary()->getNumber(), $group->getPrimary()->getNumber());

        $this->assertNotSame($cloned->getPrimary(), $group->getPrimary());

        $this->assertNotSame($cloned->getList()[1], $group->getList()[1]);
    }
}
