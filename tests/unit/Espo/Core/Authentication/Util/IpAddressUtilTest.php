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

namespace tests\unit\Espo\Core\Authentication\Util;

use PHPUnit\Framework\TestCase;

class IpAddressUtilTest extends TestCase
{
    public function testWhitelist(): void
    {
        $util = new \Espo\Core\Authentication\Util\IpAddressUtil();

        $this->assertTrue(
            $util->isInWhitelist('192.168.0.1', [
                '192.168.0.1',
            ])
        );

        $this->assertFalse(
            $util->isInWhitelist('192.168.0.1', [
                '192.168.0.0',
            ])
        );

        $this->assertTrue(
            $util->isInWhitelist('192.168.0.1', [
                '192.168.0.1',
                '192.168.0.1',
            ])
        );

        $this->assertTrue(
            $util->isInWhitelist('192.168.0.5', [
                '192.168.0.1',
                '192.168.0.1/24',
            ])
        );

        $this->assertFalse(
            $util->isInWhitelist('0.0.0.5', [
                '192.168.0.1/24',
            ])
        );
    }
}
