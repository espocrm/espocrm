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

namespace tests\integration\Espo\UserSecurity;

use Espo\Core\Authentication\AuthToken\Data;
use Espo\Core\Authentication\AuthToken\Manager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\AuthToken;
use Espo\Entities\User;
use Espo\Tools\UserSecurity\Api\PostRevokeAllAuthenticationTokens;
use tests\integration\Core\BaseTestCase;

class RevokeAuthenticationTokensTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAdminRevokesForOtherUser(): void
    {
        $admin = $this->createUser([
            User::FIELD_USER_NAME => 'test-admin',
            User::FIELD_TYPE => User::TYPE_ADMIN,
        ]);

        $user1 = $this->createUser('test-1');
        $user2 = $this->createUser('test-2');

        $user1Token1 = $this->createAuthToken($user1->getId());
        $user1Token2 = $this->createAuthToken($user1->getId());
        $user2Token = $this->createAuthToken($user2->getId());

        $this->authenticate('test-admin');

        $adminId = $admin->getId();
        $adminActiveCountBefore = $this->countActiveTokens($adminId);

        $this->revoke($user1->getId());

        $this->assertEquals(0, $this->countActiveTokens($user1->getId()));
        $this->assertFalse($this->fetchTokenEntity($user1Token1)->isActive());
        $this->assertFalse($this->fetchTokenEntity($user1Token2)->isActive());

        // Other users are not affected, including the admin.
        $this->assertTrue($this->fetchTokenEntity($user2Token)->isActive());
        $this->assertEquals($adminActiveCountBefore, $this->countActiveTokens($adminId));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRegularUserRevokesForSelf(): void
    {
        $user1 = $this->createUser('test-1');
        $user2 = $this->createUser('test-2');

        $user1Token1 = $this->createAuthToken($user1->getId());
        $user1Token2 = $this->createAuthToken($user1->getId());
        $user2Token = $this->createAuthToken($user2->getId());

        $this->authenticate('test-1');

        $this->revoke($user1->getId());

        // All the user's tokens are revoked, including the one created on login.
        $this->assertEquals(0, $this->countActiveTokens($user1->getId()));
        $this->assertFalse($this->fetchTokenEntity($user1Token1)->isActive());
        $this->assertFalse($this->fetchTokenEntity($user1Token2)->isActive());

        $this->assertTrue($this->fetchTokenEntity($user2Token)->isActive());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRegularUserCannotRevokeForOtherUser(): void
    {
        $user1 = $this->createUser('test-1');
        $user2 = $this->createUser('test-2');

        $user2Token1 = $this->createAuthToken($user2->getId());
        $user2Token2 = $this->createAuthToken($user2->getId());

        $this->authenticate('test-1');

        $user1ActiveCountBefore = $this->countActiveTokens($user1->getId());

        $isThrown = false;

        try {
            $this->revoke($user2->getId());
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown, 'Forbidden expected.');

        $this->assertTrue($this->fetchTokenEntity($user2Token1)->isActive());
        $this->assertTrue($this->fetchTokenEntity($user2Token2)->isActive());
        $this->assertEquals(2, $this->countActiveTokens($user2->getId()));

        $this->assertEquals($user1ActiveCountBefore, $this->countActiveTokens($user1->getId()));
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     * @throws Conflict
     * @throws Error
     */
    private function revoke(string $userId): void
    {
        $request = $this->createRequest(
            method: 'POST',
            routeParams: ['id' => $userId],
            resourcePath: "/User/$userId/revokeAllAuthenticationTokens",
        );

        $response = $this->getInjectableFactory()
            ->create(PostRevokeAllAuthenticationTokens::class)
            ->process($request);

        $this->assertEquals('true', (string) $response->getBody());
    }

    private function createAuthToken(string $userId): string
    {
        $authToken = $this->getContainer()
            ->getByClass(Manager::class)
            ->create(
                Data::create([
                    'userId' => $userId,
                    'passwordVersion' => 1,
                ])
            );

        return $authToken->getToken();
    }

    private function fetchTokenEntity(string $token): AuthToken
    {
        $authToken = $this->getEntityManager()
            ->getRDBRepositoryByClass(AuthToken::class)
            ->where([AuthToken::FIELD_TOKEN => $token])
            ->findOne();

        $this->assertNotNull($authToken);

        return $authToken;
    }

    private function countActiveTokens(string $userId): int
    {
        return $this->getEntityManager()
            ->getRDBRepositoryByClass(AuthToken::class)
            ->where([
                AuthToken::ATTR_USER_ID => $userId,
                AuthToken::FIELD_IS_ACTIVE => true,
            ])
            ->count();
    }
}
