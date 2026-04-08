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

namespace tests\unit\Espo\Core\Utils\Security;

use Espo\Core\Utils\Security\HostCheck;
use PHPUnit\Framework\TestCase;

class HostCheckTest extends TestCase
{
    public function testIsHostAndNotInternal(): void
    {
        $hostCheck = new HostCheck();

        $this->assertTrue(
            $hostCheck->isHostAndNotInternal('200.1.1.1')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('172.20.0.1')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('0177.0.0.1')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('127.1')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('127.0.1')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('2130706433')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('0x7f000001')
        );

        $this->assertFalse(
            $hostCheck->isHostAndNotInternal('0x7f.1')
        );
    }

    /**
     * @noinspection SpellCheckingInspection
     */
    public function testIpAddress()
    {
        $hostCheck = new HostCheck();

        $this->assertFalse(
            $hostCheck->ipAddressIsNotInternal('172.20.0.1')
        );

        $this->assertTrue(
            $hostCheck->ipAddressIsNotInternal('2606:2800:220:1:248:1893:25c8:1946')
        );

        $this->assertFalse(
            $hostCheck->ipAddressIsNotInternal('::ffff:127.0.0.1')
        );
    }
}
