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

namespace tests\unit\Espo\Core\Job;

use Espo\Core\Job\QueueName;
use Espo\Core\Job\QueuePortionNumberProvider;
use Espo\Core\Utils\Config;
use PHPUnit\Framework\TestCase;

class QueuePortionNumberProviderTest extends TestCase
{
    private $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
    }

    public function testDefault(): void
    {
        $provider = new QueuePortionNumberProvider($this->config);

        $this->assertEquals(200, $provider->get(QueueName::Q0));
        $this->assertEquals(500, $provider->get(QueueName::Q1));
        $this->assertEquals(100, $provider->get(QueueName::E0));
        $this->assertEquals(200, $provider->get('TestDefault'));
    }

    public function testConfig(): void
    {
        $this->config
            ->method('get')
            ->willReturnMap(
                [
                    ['jobQ0MaxPortion', null, 201],
                    ['jobQ1MaxPortion', null, 501],
                    ['jobE0MaxPortion', null, 101],
                    ['jobTestDefaultMaxPortion', null, 301]
                ]
            );

        $provider = new QueuePortionNumberProvider($this->config);

        $this->assertEquals(201, $provider->get(QueueName::Q0));
        $this->assertEquals(501, $provider->get(QueueName::Q1));
        $this->assertEquals(101, $provider->get(QueueName::E0));
        $this->assertEquals(301, $provider->get('TestDefault'));
    }
}
