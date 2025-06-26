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

namespace tests\integration\Espo\Settings;

use Espo\Entities\User;
use Espo\Tools\App\SettingsService;

class AccessTest extends \tests\integration\Core\BaseTestCase
{
    public function testGlobalAccess()
    {
        $app = $this->createApplication();

        $data = $app->getContainer()->get('injectableFactory')
            ->create(SettingsService::class)
            ->getConfigData();

        $this->assertTrue(property_exists($data, 'cacheTimestamp'));
        $this->assertFalse(property_exists($data, 'googleMapsApiKey'));
        $this->assertFalse(property_exists($data, 'outboundEmailFromAddress'));
        $this->assertFalse(property_exists($data, 'jobPeriod'));
        $this->assertFalse(property_exists($data, 'cryptKey'));
    }

    public function testUserAccess1()
    {
        $this->createUser('tester', [
            'data' => [
                'Email' => [
                    'create' => 'yes',
                    'read' => 'team',
                    'edit' => 'team',
                    'delete' => 'no'
                ]
            ]
        ]);

        $this->auth('tester');

        $app = $this->createApplication();

        $data = $app->getContainer()->get('injectableFactory')
            ->create(SettingsService::class)
            ->getConfigData();

        $this->assertTrue(property_exists($data, 'version'));
        $this->assertFalse(property_exists($data, 'outboundEmailFromAddress'));
        $this->assertFalse(property_exists($data, 'jobPeriod'));
        $this->assertFalse(property_exists($data, 'cryptKey'));
    }

    public function testUserAccess2()
    {
        $this->createUser('tester', [
            'data' => [
                'Email' => false
            ]
        ]);

        $this->auth('tester');

        $app = $this->createApplication();

        $data = $app->getContainer()->get('injectableFactory')
            ->create(SettingsService::class)
            ->getConfigData();

        $this->assertFalse(property_exists($data, 'outboundEmailFromAddress'));
    }

    public function testAdminAccess()
    {
        $this->createUser([
            'userName' => 'admin-tester',
            'type' => 'admin',
        ]);

        $this->auth('admin-tester');

        $app = $this->createApplication();

        $data = $app->getContainer()
            ->get('injectableFactory')
            ->create(SettingsService::class)
            ->getConfigData();

        $this->assertTrue(property_exists($data, 'version'));
        $this->assertTrue(property_exists($data, 'outboundEmailFromAddress'));
        $this->assertTrue(property_exists($data, 'jobPeriod'));
        $this->assertFalse(property_exists($data, 'cryptKey'));
    }

    public function testReadOnly(): void
    {
        $this->createUser([
            'userName' => 'admin-tester',
            'type' => User::TYPE_ADMIN,
        ]);

        $this->auth('admin-tester');
        $this->setApplication($this->createApplication());

        $this->getInjectableFactory()
            ->create(SettingsService::class)
            ->setConfigData((object) [
                'systemUserId' => 'test'
            ]);

        $this->assertNull($this->getConfig()->get('systemUserId'));
    }
}
