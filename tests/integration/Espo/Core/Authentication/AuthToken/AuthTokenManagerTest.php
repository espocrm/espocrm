<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace tests\integration\Espo\Core\Authentication\AuthToken;

use Espo\Core\Authentication\AuthToken\Data;

class AuthTokenManagerTest extends \tests\integration\Core\BaseTestCase
{
    public function testCreateWithSecret()
    {
        $authTokenManager = $this->getContainer()->get('authTokenManager');

        $authTokenData = Data::create([
            'hash' => 'test-hash',
            'ipAddress' => 'ip-address',
            'userId' => 'user-id',
            'portalId' => 'portal-id',
            'createSecret' => true,
        ]);

        $authToken = $authTokenManager->create($authTokenData);

        $this->assertEquals($authTokenData->getHash(), $authToken->getHash());
        $this->assertEquals($authTokenData->getUserId(), $authToken->getUserId());
        $this->assertEquals($authTokenData->getPortalId(), $authToken->getPortalId());

        $this->assertTrue($authToken->isActive());

        $this->assertNotEmpty($authToken->getToken());
        $this->assertNotEmpty($authToken->getSecret());

        $this->assertNotEmpty($authToken->get('lastAccess'));
    }

    public function testCreateWithNoSecretNoPortal()
    {
        $authTokenManager = $this->getContainer()->get('authTokenManager');

        $authTokenData = Data::create([
            'hash' => 'test-hash',
            'ipAddress' => 'ip-address',
            'userId' => 'user-id',
            'portalId' => null,
            'createSecret' => false,
        ]);

        $authToken = $authTokenManager->create($authTokenData);

        $this->assertEquals($authTokenData->getHash(), $authToken->getHash());
        $this->assertEquals($authTokenData->getUserId(), $authToken->getUserId());

        $this->assertEmpty($authToken->getPortalId());

        $this->assertNotEmpty($authToken->getToken());
        $this->assertEmpty($authToken->getSecret());
    }

    public function testRenew()
    {
        $authTokenManager = $this->getContainer()->get('authTokenManager');

        $authTokenData = Data::create([
            'hash' => 'test-hash',
            'userId' => 'user-id',
        ]);

        $authToken = $authTokenManager->create($authTokenData);

        $authToken = $authTokenManager->get($authToken->getToken());

        $authTokenManager->renew($authToken);

        $this->assertNotEmpty($authToken->get('lastAccess'));
    }

    public function testInactivate()
    {
        $authTokenManager = $this->getContainer()->get('authTokenManager');

        $authTokenData = Data::create([
            'hash' => 'test-hash',
            'userId' => 'user-id',
        ]);

        $authToken = $authTokenManager->create($authTokenData);

        $authToken = $authTokenManager->get($authToken->getToken());

        $authTokenManager->inactivate($authToken);

        $this->assertFalse($authToken->isActive());
    }
}
