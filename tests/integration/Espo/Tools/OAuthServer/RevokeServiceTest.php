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

use Espo\Entities\User;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\Record\RevokeService;
use tests\integration\Core\BaseTestCase;

class RevokeServiceTest extends BaseTestCase
{
    public function testRevokeByUsername(): void
    {
        $data = $this->prepareData();

        $service = $this->getInjectableFactory()->create(RevokeService::class);

        $service->revoke(
            new RevokeService\Params(
                userName: 'user-2',
            ),
        );

        $em = $this->getEntityManager();

        $em->refreshEntity($data['refreshToken2']);
        $em->refreshEntity($data['accessToken2']);
        $em->refreshEntity($data['refreshToken1']);
        $em->refreshEntity($data['accessToken1']);

        $this->assertTrue($data['refreshToken2']->isRevoked());
        $this->assertTrue($data['accessToken2']->isRevoked());

        $this->assertFalse($data['refreshToken1']->isRevoked());
        $this->assertFalse($data['accessToken1']->isRevoked());
    }

    public function testRevokeAdmin(): void
    {
        $data = $this->prepareData();

        $service = $this->getInjectableFactory()->create(RevokeService::class);

        $service->revoke(
            new RevokeService\Params(
                adminOnly: true,
            ),
        );

        $em = $this->getEntityManager();

        $em->refreshEntity($data['refreshTokenAdmin']);
        $em->refreshEntity($data['accessTokenAdmin']);
        $em->refreshEntity($data['refreshToken1']);
        $em->refreshEntity($data['accessToken1']);

        $this->assertTrue($data['refreshTokenAdmin']->isRevoked());
        $this->assertTrue($data['accessTokenAdmin']->isRevoked());

        $this->assertFalse($data['refreshToken1']->isRevoked());
        $this->assertFalse($data['accessToken1']->isRevoked());
    }

    public function testRevokeAll(): void
    {
        $data = $this->prepareData();

        $service = $this->getInjectableFactory()->create(RevokeService::class);

        $service->revoke(
            new RevokeService\Params(),
        );

        $em = $this->getEntityManager();

        $em->refreshEntity($data['refreshTokenAdmin']);
        $em->refreshEntity($data['accessTokenAdmin']);
        $em->refreshEntity($data['refreshToken1']);
        $em->refreshEntity($data['accessToken1']);
        $em->refreshEntity($data['refreshToken2']);
        $em->refreshEntity($data['accessToken2']);

        $this->assertTrue($data['refreshTokenAdmin']->isRevoked());
        $this->assertTrue($data['accessTokenAdmin']->isRevoked());

        $this->assertTrue($data['refreshToken1']->isRevoked());
        $this->assertTrue($data['accessToken1']->isRevoked());

        $this->assertTrue($data['refreshToken2']->isRevoked());
        $this->assertTrue($data['accessToken2']->isRevoked());
    }

    /**
     * @return array{
     *     user1: User,
     *     user2: User,
     *     userAdmin: User,
     *     refreshToken1: RefreshToken,
     *     refreshToken2: RefreshToken,
     *     refreshTokenAdmin: RefreshToken,
     *     accessToken1: AccessToken,
     *     accessToken2: AccessToken,
     *     accessTokenAdmin: AccessToken,
     * }
     */
    private function prepareData(): array
    {
        $em = $this->getEntityManager();

        $user1 = $em->getRDBRepositoryByClass(User::class)
            ->getNew()
            ->setUserName('user-1');

        $user2 = $em->getRDBRepositoryByClass(User::class)
            ->getNew()
            ->setUserName('user-2');

        $userAdmin = $em->getRDBRepositoryByClass(User::class)
            ->getNew()
            ->setUserName('user-admin')
            ->setLastName('Admin')
            ->setType(User::TYPE_ADMIN);

        $em->saveEntity($user1);
        $em->saveEntity($user2);
        $em->saveEntity($userAdmin);

        $refreshToken1 = $em->getRDBRepositoryByClass(RefreshToken::class)
            ->getNew()
            ->setUser($user1);

        $refreshToken2 = $em->getRDBRepositoryByClass(RefreshToken::class)
            ->getNew()
            ->setUser($user2);

        $refreshTokenAdmin = $em->getRDBRepositoryByClass(RefreshToken::class)
            ->getNew()
            ->setUser($userAdmin);

        $em->saveEntity($refreshToken1);
        $em->saveEntity($refreshToken2);
        $em->saveEntity($refreshTokenAdmin);

        $accessToken1 = $em->getRDBRepositoryByClass(AccessToken::class)
            ->getNew()
            ->setUser($user1);

        $accessToken2 = $em->getRDBRepositoryByClass(AccessToken::class)
            ->getNew()
            ->setUser($user2);

        $accessTokenAdmin = $em->getRDBRepositoryByClass(AccessToken::class)
            ->getNew()
            ->setUser($userAdmin);

        $em->saveEntity($accessToken1);
        $em->saveEntity($accessToken2);
        $em->saveEntity($accessTokenAdmin);

        return [
            'user1' => $user1,
            'user2' => $user2,
            'userAdmin' => $userAdmin,
            'refreshToken1' => $refreshToken1,
            'refreshToken2' => $refreshToken2,
            'refreshTokenAdmin' => $refreshTokenAdmin,
            'accessToken1' => $accessToken1,
            'accessToken2' => $accessToken2,
            'accessTokenAdmin' => $accessTokenAdmin,
        ];
    }
}
