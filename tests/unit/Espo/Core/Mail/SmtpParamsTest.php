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

namespace tests\unit\Espo\Core\Mail;

use Espo\Core\Mail\SmtpParams;
use PHPUnit\Framework\TestCase;

class SmtpParamsTest extends TestCase
{
    public function testFromArray1(): void
    {
        $array = [
            'server' => 'localhost',
            'port' => 587,
            'fromAddress' => 'test@test',
            'fromName' => 'name',
            'auth' => false,
        ];

        $this->assertEquals($array, SmtpParams::fromArray($array)->toArray());
    }

    public function testFromArray2(): void
    {
        $array = [
            'server' => 'localhost',
            'port' => 587,
            'fromAddress' => 'test@test',
            'fromName' => 'name',
            'auth' => true,
            'connectionOptions' => ['test' => 'test'],
            'authMechanism' => 'login',
            'username' => 'tester',
            'password' => 'password',
            'security' => 'ssl',
        ];

        $this->assertEquals($array, SmtpParams::fromArray($array)->toArray());
    }

    public function testBuilding(): void
    {
        $params = SmtpParams::create('localhost', 587)
            ->withFromAddress('test@test')
            ->withFromName('name')
            ->withAuth()
            ->withUsername('tester')
            ->withPassword('test')
            ->withAuthMechanism('login')
            ->withConnectionOptions(['test' => 'test']);

        $this->assertEquals('localhost', $params->getServer());
        $this->assertEquals(587, $params->getPort());

        $this->assertEquals('test@test', $params->getFromAddress());
        $this->assertEquals('name', $params->getFromName());

        $this->assertEquals(true, $params->useAuth());

        $this->assertEquals('tester', $params->getUsername());
        $this->assertEquals('test', $params->getPassword());
        $this->assertEquals(['test' => 'test'], $params->getConnectionOptions());
    }
}
