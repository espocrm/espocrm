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

namespace tests\integration\Espo\User;

use Espo\Entities\User;
use integration\Core\NoTransaction;
use tests\integration\Core\BaseTestCase;

class LoginTest extends BaseTestCase
{
    protected ?string $password = '1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createUser([
            'type' => User::TYPE_ADMIN,
            'userName' => 'admin',
            'lastName' => 'Admin',
        ]);

        $this->authenticate('admin');
    }

    public function testLogin(): void
    {
        $this->authenticate('admin');

        $user = $this->getContainer()->getByClass(User::class);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('admin', $user->getUserName());
    }

    #[NoTransaction]
    public function testWrongCredentials(): void
    {
        $this->auth('admin', 'wrong-password');

        $application = $this->createApplication();

        $this->assertFalse($application->getContainer()->has('user'));
    }
}
