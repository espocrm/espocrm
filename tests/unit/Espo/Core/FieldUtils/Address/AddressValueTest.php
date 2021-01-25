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

namespace tests\unit\Espo\Core\FieldUtils\Address;

use Espo\Core\{
    FieldUtils\Address\AddressValue,
};

use Espo\ORM\Entity;

class AddressValueTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {

    }

    public function testAddress1()
    {
        $address = AddressValue::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $this->assertEquals('street', $address->getStreet());
        $this->assertEquals('city', $address->getCity());
        $this->assertEquals('country', $address->getCountry());
        $this->assertEquals('state', $address->getState());
        $this->assertEquals('postalCode', $address->getPostalCode());
    }

    public function testBuilderClone()
    {
        $addressOriginal = AddressValue::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $address = AddressValue::createBuilder()
            ->clone($addressOriginal)
            ->build();

        $this->assertEquals('street', $address->getStreet());
        $this->assertEquals('city', $address->getCity());
        $this->assertEquals('country', $address->getCountry());
        $this->assertEquals('state', $address->getState());
        $this->assertEquals('postalCode', $address->getPostalCode());
    }

    public function testAddressWith()
    {
        $addressOriginal = AddressValue::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $address = $addressOriginal->withStreet('new street');

        $this->assertEquals('new street', $address->getStreet());
        $this->assertEquals('city', $address->getCity());
    }

    public function testFromEntity()
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['addressStreet', 'street'],
                ['addressCity', 'city'],
                ['addressCountry', 'country'],
                ['addressState', null],
                ['addressPostalCode', null],
            ]);

        $address = AddressValue::fromEntity($entity, 'address');

        $this->assertEquals('street', $address->getStreet());
        $this->assertEquals('city', $address->getCity());
        $this->assertEquals('country', $address->getCountry());
        $this->assertEquals(null, $address->getState());
        $this->assertEquals(null, $address->getPostalCode());
    }
}
