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

namespace tests\unit\Espo\Core\FieldUtils\Currency;

use Espo\Core\{
    FieldUtils\Currency\CurrencyValue,
};

use Espo\{
    ORM\Entity,
};

use RuntimeException;

class CurrencyValueTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    public function testValue()
    {
        $value = new CurrencyValue(2.0, 'USD');

        $this->assertEquals(2.0, $value->getAmount());

        $this->assertEquals('USD', $value->getCode());
    }

    public function testAdd()
    {
        $value = (new CurrencyValue(2.0, 'USD'))->add(
            new CurrencyValue(1.0, 'USD')
        );

        $this->assertEquals(3.0, $value->getAmount());

        $this->assertEquals('USD', $value->getCode());
    }

    public function testSubtract()
    {
        $value = (new CurrencyValue(2.0, 'USD'))->subtract(
            new CurrencyValue(3.0, 'USD')
        );

        $this->assertEquals(-1.0, $value->getAmount());

        $this->assertEquals('USD', $value->getCode());
    }

    public function testMultiply()
    {
        $value = (new CurrencyValue(2.0, 'USD'))->multiply(3.0);

        $this->assertEquals(6.0, $value->getAmount());

        $this->assertEquals('USD', $value->getCode());
    }

    public function testDivide()
    {
        $value = (new CurrencyValue(6.0, 'USD'))->divide(3.0);

        $this->assertEquals(2.0, $value->getAmount());

        $this->assertEquals('USD', $value->getCode());
    }

    public function testRound()
    {
        $value = (new CurrencyValue(2.306, 'USD'))->round(2);

        $this->assertEquals(2.31, $value->getAmount());

        $this->assertEquals('USD', $value->getCode());
    }

    public function testBadAdd()
    {
        $this->expectException(RuntimeException::class);

        (new CurrencyValue(2.0, 'USD'))->add(
            new CurrencyValue(1.0, 'EUR')
        );
    }

    public function testGetBadCode()
    {
        $this->expectException(RuntimeException::class);

        new CurrencyValue(2.0, '');
    }

    public function testGetFromEntity()
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['test', 10.0],
                ['testCurrency', 'USD'],
            ]);

        $value = CurrencyValue::fromEntity($entity, 'test');

        $this->assertEquals(10.0, $value->getAmount());
        $this->assertEquals('USD', $value->getCode());

    }

    public function testIsSetInEntityTrue()
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['test', true],
                ['testCurrency', true],
            ]);

        $this->assertTrue(
            CurrencyValue::isSetInEntity($entity, 'test')
        );
    }

    public function testIsSetInEntityFalse()
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['test', false],
                ['testCurrency', true],
            ]);

        $this->assertFalse(
            CurrencyValue::isSetInEntity($entity, 'test')
        );
    }
}
