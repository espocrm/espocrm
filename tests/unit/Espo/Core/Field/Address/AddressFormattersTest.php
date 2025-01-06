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

namespace tests\unit\Espo\Core\Field\Address;

use Espo\Core\{
    Field\Address,
};

use Espo\Classes\{
    AddressFormatters\Formatter1,
    AddressFormatters\Formatter2,
    AddressFormatters\Formatter3,
    AddressFormatters\Formatter4,
};

class AddressFormattersTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {

    }

    public function testFormat1All()
    {
        $address = Address::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $formatter = new Formatter1();

        $expected =
            "street\n" .
            "city, state postalCode\n" .
            "country";

        $result = $formatter->format($address);

        $this->assertEquals($expected, $result);
    }

    public function testFormat1NoState()
    {
        $address = Address::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState(null)
            ->setPostalCode('postalCode')
            ->build();

        $formatter = new Formatter1();

        $expected =
            "street\n" .
            "city postalCode\n" .
            "country";

        $result = $formatter->format($address);

        $this->assertEquals($expected, $result);
    }

    public function testFormat2All()
    {
        $address = Address::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $formatter = new Formatter2();

        $expected =
            "street\n" .
            "postalCode city\n" .
            "state country";

        $result = $formatter->format($address);

        $this->assertEquals($expected, $result);
    }

    public function testFormat3All()
    {
        $address = Address::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $formatter = new Formatter3();

        $expected =
            "country\n" .
            "state postalCode city\n" .
            "street";

        $result = $formatter->format($address);

        $this->assertEquals($expected, $result);
    }

    public function testFormat4All()
    {
        $address = Address::createBuilder()
            ->setStreet('street')
            ->setCity('city')
            ->setCountry('country')
            ->setState('state')
            ->setPostalCode('postalCode')
            ->build();

        $formatter = new Formatter4();

        $expected =
            "street\n" .
            "city\n" .
            "country - state postalCode";

        $result = $formatter->format($address);

        $this->assertEquals($expected, $result);
    }
}
