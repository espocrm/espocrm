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

namespace tests\unit\Espo\Core\Acl\Map;

use Espo\Core\Acl\Map\DefaultCacheKeyProvider;
use Espo\Entities\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DefaultCacheKeyProviderTest extends TestCase
{
    #[DataProvider('provider')]
    public function testKey(string $expected, string $userId, ?array $scopes = null): void
    {
        $user = $this->createMock(User::class);

        $user->expects(self::any())
            ->method('getId')
            ->willReturn($userId);

        $user->expects(self::any())
            ->method('getScopes')
            ->willReturn($scopes);

        $provider = new DefaultCacheKeyProvider($user);

        $this->assertEquals($expected, $provider->get());
    }

    public static function provider(): array
    {
        return [
            ['aclMap/01', '01', null],
            ['aclMap/01/37f6e5e382faedc68e0769892e78d8d3', '01', ['Admin', 'Global']],
            ['aclMap/01/99aa06d3014798d86001c324468d497f', '01', []],
        ];
    }
}
