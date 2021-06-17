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

namespace tests\unit\Espo\Core\Field\PhoneNumber;

use Espo\Core\{
    Field\PhoneNumber,
    Field\PhoneNumberGroup,
    Field\PhoneNumber\PhoneNumberGroupAttributeExtractor,
};

class PhoneNumberGroupAttributeExtractorTest extends \PHPUnit\Framework\TestCase
{
    public function testExtract()
    {
        $group = PhoneNumberGroup
            ::create([
                PhoneNumber::create('+1')->withType('Test-1'),
                PhoneNumber::create('+2')->withType('Test-2')->optedOut(),
            ]);

        $valueMap = (new PhoneNumberGroupAttributeExtractor())->extract($group, 'phoneNumber');

        $this->assertEquals(
            (object) [
                'phoneNumber' => '+1',
                'phoneNumberData' => [
                    (object) [
                        'phoneNumber' => '+1',
                        'type' => 'Test-1',
                        'primary' => true,
                        'optOut' => false,
                        'invalid' => false,
                    ],
                    (object) [
                        'phoneNumber' => '+2',
                        'type' => 'Test-2',
                        'primary' => false,
                        'optOut' => true,
                        'invalid' => false,
                    ],
                ]
            ],
            $valueMap
        );
    }

    public function testEmpty()
    {
        $group = PhoneNumberGroup
            ::create([]);

        $valueMap = (new PhoneNumberGroupAttributeExtractor())->extract($group, 'phoneNumber');

        $this->assertEquals(
            (object) [
                'phoneNumber' => null,
                'phoneNumberData' => [],
            ],
            $valueMap
        );
    }
}
