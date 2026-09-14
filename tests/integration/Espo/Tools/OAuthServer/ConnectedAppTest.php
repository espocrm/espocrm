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

namespace integration\Espo\Tools\OAuthServer;

use Espo\Core\Acl\Scope;
use Espo\Core\Field\DateTime;
use Espo\Tools\OAuthServer\ClientType;
use Espo\Tools\OAuthServer\ConnectedApp\ConnectedAppService;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\Repository\AccessTokenRepository;
use tests\integration\Core\BaseTestCase;

class ConnectedAppTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testListAndDisconnect(): void
    {
        $em = $this->getEntityManager();

        $user1 = $this->createUser('test-1');
        $user2 = $this->createUser('test-2');

        $client1 = $this->createClient('Test 1');
        $client2 = $this->createClient('Test 2');

        //

        $em->saveEntity(
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->getNew()
                ->setClient($client1)
                ->setExpiresAt(DateTime::createNow()->addHours(1))
                ->setUser($user1)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->getNew()
                ->setClient($client2)
                ->setExpiresAt(DateTime::createNow()->addHours(1))
                ->setUser($user1)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->getNew()
                ->setClient($client1)
                ->setExpiresAt(DateTime::createNow()->addHours(1))
                ->setUser($user1)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(AccessToken::class)
                ->getNew()
                ->setClient($client1)
                ->setExpiresAt(DateTime::createNow()->addHours(1))
                ->setUser($user1)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(AccessToken::class)
                ->getNew()
                ->setClient($client2)
                ->setExpiresAt(DateTime::createNow()->addHours(1))
                ->setUser($user1)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->getNew()
                ->setClient($client1)
                ->setExpiresAt(DateTime::createNow()->addHours(1))
                ->setUser($user2)
        );

        //

        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);

        $service = $this->getInjectableFactory()->create(ConnectedAppService::class);

        $apps = $service->getList($user1);

        $this->assertCount(2, $apps);
        $this->assertEquals($client1->getIdentifier(), $apps[0]->id);

        $this->assertEquals(
            1,
            $accessTokenRepo
                ->findActiveForClientIdAndUser($client1->getId(), $user1->getId())
                ->count(),
        );

        //

        $service->disconnect($user1, $client1->getIdentifier());

        $apps = $service->getList($user1);

        $this->assertCount(1, $apps);

        $this->assertEquals(
            0,
            $accessTokenRepo
                ->findActiveForClientIdAndUser($client1->getId(), $user1->getId())
                ->count(),
        );

        //

        $apps = $service->getList($user2);

        $this->assertCount(1, $apps);

        //

        $apps = $service->getList($user2);

        $this->assertCount(1, $apps);
    }

    private function createClient(string $name): Client
    {
        $em = $this->getEntityManager();

        $client = $em->getRDBRepositoryByClass(Client::class)->getNew();
        $client
            ->setName($name)
            ->setScopes([Scope::GLOBAL])
            ->setClientType(ClientType::Confidential)
            ->setRedirectUris(['http://localhost/oauth/callback']);
        $em->saveEntity($client);

        return $client;
    }
}
