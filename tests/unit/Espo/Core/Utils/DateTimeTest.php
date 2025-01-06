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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\DateTime;

class DateTimeTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        date_default_timezone_set('UTC');
    }

    public function testConvertFormat(): void
    {
        $map = [
            'YYYY-MM-DD' => 'Y-m-d',
            'DD-MM-YYYY' => 'd-m-Y',
            'MM-DD-YYYY' => 'm-d-Y',
            'MM/DD/YYYY' => 'm/d/Y',
            'DD/MM/YYYY' => 'd/m/Y',
            'DD.MM.YYYY' => 'd.m.Y',
            'DD. MM. YYYY' => 'd. m. Y',
            'MM.DD.YYYY' => 'm.d.Y',
            'YYYY.MM.DD' => 'Y.m.d',
            'HH:mm' => 'H:i',
            'HH:mm:ss' => 'H:i:s',
            'hh:mm a' => 'h:i a',
            'hh:mma' => 'h:ia',
            'hh:mm A' => 'h:i A',
            'hh:mmA' => 'h:iA',
            'DD. MM. YYYY HH:mm' => 'd. m. Y H:i',
        ];

        foreach ($map as $from => $to) {
            $this->assertEquals($to, DateTime::convertFormatToSystem($from));
        }
    }

    public function testConvertGetFormat(): void
    {
        $util = new DateTime('YYYY-MM-DD', 'HH:mm', 'Europe/Kiev');

        $this->assertEquals('YYYY-MM-DD HH:mm', $util->getDateTimeFormat());

        $this->assertEquals('YYYY-MM-DD', $util->getDateFormat());
    }

    public function testConvertSystemDateTime1(): void
    {
        $util = new DateTime('DD-MM-YYYY', 'HH:mm', 'Europe/Kiev');

        $this->assertEquals(
            '20-05-2021 13:00',
            $util->convertSystemDateTime('2021-05-20 10:00')
        );
    }

    public function testConvertSystemDateTime2(): void
    {
        $util = new DateTime('DD-MM-YYYY', 'HH:mm', 'Europe/Kiev');

        $this->assertEquals(
            '2021-05-20 10:00am',
            $util->convertSystemDateTime('2021-05-20 10:00', 'UTC', 'YYYY-MM-DD hh:mma')
        );
    }

    public function testConvertSystemDate1(): void
    {
        $util = new DateTime('DD-MM-YYYY', 'HH:mm', 'Europe/Kiev');

        $this->assertEquals(
            '20-05-2021',
            $util->convertSystemDate('2021-05-20')
        );
    }
}
