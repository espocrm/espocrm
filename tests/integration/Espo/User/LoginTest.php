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

namespace tests\integration\Espo\User;

class LoginTest extends \tests\integration\Core\BaseTestCase
{
    protected ?string $userName = 'admin';
    protected ?string $password = '1';

    public function testLogin()
    {
        $user = $this->getContainer()->get('user');

        $this->assertEquals('Espo\\Entities\\User', get_class($user));
        $this->assertEquals('1', $user->get('id'));
        $this->assertEquals('admin', $user->get('userName'));
    }

    public function testWrongCredentials()
    {
        $this->auth('admin', 'wrong-password');

        $application = $this->createApplication();

        $this->assertFalse($application->getContainer()->has('user'));
    }
}
