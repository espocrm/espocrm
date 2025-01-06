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

namespace tests\unit\Espo\Tools\UserSecurity\Password;

use Espo\Tools\UserSecurity\Password\Checker;
use Espo\Tools\UserSecurity\Password\ConfigProvider;
use PHPUnit\Framework\TestCase;

class CheckerTest extends TestCase
{
    public function testCheck(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);

        $configProvider
            ->expects($this->any())
            ->method('getStrengthLength')
            ->willReturn(6);

        $configProvider
            ->expects($this->any())
            ->method('getStrengthLetterCount')
            ->willReturn(2);

        $configProvider
            ->expects($this->any())
            ->method('getStrengthNumberCount')
            ->willReturn(1);

        $configProvider
            ->expects($this->any())
            ->method('getStrengthBothCases')
            ->willReturn(true);

        $configProvider
            ->expects($this->any())
            ->method('getStrengthSpecialCharacterCount')
            ->willReturn(1);

        $checker = new Checker($configProvider);

        $this->assertTrue($checker->checkStrength("Aa1:aaaaaaaaa"));
        $this->assertFalse($checker->checkStrength("Aa1aaaaaaaaa"));
        $this->assertFalse($checker->checkStrength("aa1:aaaaaaaaa"));
        $this->assertFalse($checker->checkStrength("aaa:aaaaaaaaa"));
        $this->assertFalse($checker->checkStrength("11:11111111"));
        $this->assertFalse($checker->checkStrength("Aa:1"));
    }
}
