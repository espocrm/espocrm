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

namespace tests\unit\Espo\Core\ORM;

use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\Utils\Config;

class DatabaseParamsFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $factory = new DatabaseParamsFactory($this->createConfig());

        $params = $factory->create();

        $this->assertEquals('test:host', $params->getHost());
        $this->assertEquals(10, $params->getPort());
        $this->assertEquals('name-db', $params->getName());
        $this->assertEquals('test-user', $params->getUsername());
        $this->assertEquals('test-password', $params->getPassword());
        $this->assertEquals('test-platform', $params->getPlatform());
    }

    public function testCreateWithMergedAssoc(): void
    {
        $factory = new DatabaseParamsFactory($this->createConfig());

        $params = $factory->createWithMergedAssoc([
            'host' => 'test:host2',
            'port' => 11,
            'dbname' => 'name2-db',
            'user' => 'test-user2',
            'password' => 'test-password2',
        ]);

        $this->assertEquals('test:host2', $params->getHost());
        $this->assertEquals(11, $params->getPort());
        $this->assertEquals('name2-db', $params->getName());
        $this->assertEquals('test-user2', $params->getUsername());
        $this->assertEquals('test-password2', $params->getPassword());
        $this->assertEquals('test-platform', $params->getPlatform());
    }

    private function createConfig(): Config
    {
        $config = $this->createMock(Config::class);

        $config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['database', null, ['d' => 'd']],
                ['database.host', null, 'test:host'],
                ['database.port', null, 10],
                ['database.dbname', null, 'name-db'],
                ['database.user', null, 'test-user'],
                ['database.password', null, 'test-password'],
                ['database.platform', null, 'test-platform'],
            ]);

        return $config;
    }
}
