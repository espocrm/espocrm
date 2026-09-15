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

namespace tests\integration\Espo\Settings;

use Espo\Entities\User;
use Espo\Tools\App\SettingsService;
use tests\integration\Core\BaseTestCase;

class AccessTest extends BaseTestCase
{
    public function testGlobalAccess(): void
    {
        $service = $this->getInjectableFactory()->create(SettingsService::class);

        $data = $service->getConfigData();

        $hiddenProps = [
            'googleMapsApiKey',
            'outboundEmailFromAddress',
            'jobPeriod',
            'cryptKey',
        ];

        foreach ($hiddenProps as $prop) {
            $this->assertObjectNotHasProperty($prop, $data);
        }

        $this->assertObjectHasProperty('cacheTimestamp', $data);
    }

    public function testUserAccess1(): void
    {
        $this->setConfigParams([
            'internalSmtpServer' => 'test',
            'internalSmtpPassword' => 'test',
            'oAuthServer' => [
                'test' => 'test',
            ],
        ]);

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

        $this->authenticate('tester');

        $service = $this->getInjectableFactory()->create(SettingsService::class);

        $data = $service->getConfigData();

        $hiddenProps = [
            'googleMapsApiKey',
            'outboundEmailFromAddress',
            'jobPeriod',
            'cryptKey',
            'oAuthServer',
            'apiExposeExceptions',
            'database',
            'internalSmtpServer',
            'internalSmtpPassword',
        ];

        foreach ($hiddenProps as $prop) {
            $this->assertObjectNotHasProperty($prop, $data);
        }

        $this->assertObjectHasProperty('version', $data);
    }

    public function testUserAccess2()
    {
        $this->createUser('tester', [
            'data' => [
                'Email' => false
            ]
        ]);

        $this->authenticate('tester');

        $data = $this->getInjectableFactory()
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

        $this->authenticate('admin-tester');

        $data = $this->getInjectableFactory()
            ->create(SettingsService::class)
            ->getConfigData();

        $this->assertTrue(property_exists($data, 'version'));
        $this->assertTrue(property_exists($data, 'outboundEmailFromAddress'));
        $this->assertTrue(property_exists($data, 'jobPeriod'));
        $this->assertFalse(property_exists($data, 'cryptKey'));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testReadOnly(): void
    {
        $this->createUser([
            'userName' => 'admin-tester',
            'type' => User::TYPE_ADMIN,
        ]);

        $this->authenticate('admin-tester');

        $service = $this->getInjectableFactory()->create(SettingsService::class);

        $service->setConfigData((object) [
            'systemUserId' => 'test',
            'cryptKey' => 'test',
            'oAuthServerCryptKey' => 'test',
        ]);

        $this->reCreateApplication(reuse: true);

        $this->assertNull($this->getConfig()->get('systemUserId'));
        $this->assertNotEquals('test', $this->getConfig()->get('cryptKey'));
        $this->assertNotEquals('test', $this->getConfig()->get('oAuthServerCryptKey'));
    }
}
