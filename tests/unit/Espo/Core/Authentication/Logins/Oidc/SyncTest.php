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

namespace tests\unit\Espo\Core\Authentication\Logins\Oidc;

use Espo\Core\Acl\Cache\Clearer;
use Espo\Core\ApplicationState;
use Espo\Core\Authentication\Oidc\ConfigDataProvider;
use Espo\Core\Authentication\Oidc\UserProvider\Sync;
use Espo\Core\Authentication\Oidc\UserProvider\UsernameValidator;
use Espo\Core\Authentication\Oidc\UserProvider\UserRepository;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\PasswordHash;

use PHPUnit\Framework\TestCase;

class SyncTest extends TestCase
{
    private ?Sync $sync = null;
    private ?Config $config = null;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $configDataProvider = $this->createMock(ConfigDataProvider::class);

        $this->sync = new Sync(
            $this->createMock(UsernameValidator::class),
            $this->config,
            $configDataProvider,
            $this->createMock(UserRepository::class),
            $this->createMock(PasswordHash::class),
            $this->createMock(Clearer::class),
            $this->createMock(ApplicationState::class)
        );
    }

    public function testNormalizeUsername(): void
    {
        $this->config
            ->expects($this->any())
            ->method('get')
            ->with('userNameRegularExpression')
            ->willReturn('[^a-z0-9\-@_\.\s]');

        $this->assertEquals(
            'test_name',
            $this->sync->normalizeUsername('test_name')
        );

        $this->assertEquals(
            'test_name',
            $this->sync->normalizeUsername('test|name')
        );

        $this->assertEquals(
            'test@name',
            $this->sync->normalizeUsername('test@name')
        );

        $this->assertEquals(
            'test_name',
            $this->sync->normalizeUsername('test name')
        );
    }
}
