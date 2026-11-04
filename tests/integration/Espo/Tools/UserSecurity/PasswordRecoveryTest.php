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

namespace tests\integration\Espo\Tools\UserSecurity;

use Espo\Core\Authentication\AuthToken\Data;
use Espo\Core\Authentication\AuthToken\Manager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\PasswordHash;
use Espo\Entities\AuthToken;
use Espo\Entities\PasswordChangeRequest;
use Espo\Entities\User;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\UserSecurity\Api\PostChangePasswordByRequest;
use Espo\Tools\UserSecurity\Password\RecoveryService;
use tests\integration\Core\BaseTestCase;

class PasswordRecoveryTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testChangePasswordByRecoveryRevokesTokens(): void
    {
        $em = $this->getEntityManager();
        $if = $this->getInjectableFactory();

        $oldPassword = 'old-password';
        $newPassword = 'new-password';

        $user = $this->createUser([
            User::FIELD_USER_NAME => 'test-user',
            User::FIELD_PASSWORD => $oldPassword,
        ]);

        // A control user whose tokens must not be affected.
        $otherUser = $this->createUser('test-other');

        $authToken1 = $this->createAuthToken($user->getId());
        $authToken2 = $this->createAuthToken($user->getId());
        $otherAuthToken = $this->createAuthToken($otherUser->getId());

        [$refreshToken, $accessToken] = $this->createOAuthTokens($user);
        [$otherRefreshToken, $otherAccessToken] = $this->createOAuthTokens($otherUser);

        // Preconditions.
        $this->assertTrue($this->fetchAuthToken($authToken1)->isActive());
        $this->assertTrue($this->fetchAuthToken($authToken2)->isActive());
        $this->assertFalse($refreshToken->isRevoked());
        $this->assertFalse($accessToken->isRevoked());

        $passwordChangeRequest = $if->create(RecoveryService::class)->createRequestForNewUser($user);

        $requestId = $passwordChangeRequest->getRequestId();

        $this->assertNotEmpty($requestId);

        // Change the password by the request, as the front-end does on the recovery page.
        $request = $this->createRequest(
            method: 'POST',
            headers: ['Content-Type' => 'application/json'],
            body: Json::encode([
                'requestId' => $requestId,
                'password' => $newPassword,
            ]),
            resourcePath: '/User/changePasswordByRequest',
        );

        $response = $if->create(PostChangePasswordByRequest::class)->process($request);

        $this->assertEquals(200, $response->getStatusCode());

        $passwordHash = $if->create(PasswordHash::class);

        $em->refreshEntity($user);

        $this->assertTrue($passwordHash->verify($newPassword, $user->getPassword()));
        $this->assertFalse($passwordHash->verify($oldPassword, $user->getPassword()));

        // The request is consumed.
        $requestCount = $em->getRDBRepositoryByClass(PasswordChangeRequest::class)
            ->where([PasswordChangeRequest::FIELD_REQUEST_ID => $requestId])
            ->count();

        $this->assertEquals(0, $requestCount);

        // Internal auth tokens of the user are inactivated.
        $this->assertFalse($this->fetchAuthToken($authToken1)->isActive());
        $this->assertFalse($this->fetchAuthToken($authToken2)->isActive());
        $this->assertEquals(0, $this->countActiveAuthTokens($user->getId()));

        // OAuth tokens of the user are revoked.
        $em->refreshEntity($refreshToken);
        $em->refreshEntity($accessToken);

        $this->assertTrue($refreshToken->isRevoked());
        $this->assertTrue($accessToken->isRevoked());

        // Tokens of the other user are not affected.
        $em->refreshEntity($otherRefreshToken);
        $em->refreshEntity($otherAccessToken);

        $this->assertTrue($this->fetchAuthToken($otherAuthToken)->isActive());
        $this->assertFalse($otherRefreshToken->isRevoked());
        $this->assertFalse($otherAccessToken->isRevoked());
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

    private function fetchAuthToken(string $token): AuthToken
    {
        $authToken = $this->getEntityManager()
            ->getRDBRepositoryByClass(AuthToken::class)
            ->where([AuthToken::FIELD_TOKEN => $token])
            ->findOne();

        $this->assertNotNull($authToken);

        return $authToken;
    }

    private function countActiveAuthTokens(string $userId): int
    {
        return $this->getEntityManager()
            ->getRDBRepositoryByClass(AuthToken::class)
            ->where([
                AuthToken::ATTR_USER_ID => $userId,
                AuthToken::FIELD_IS_ACTIVE => true,
            ])
            ->count();
    }

    /**
     * @return array{RefreshToken, AccessToken}
     */
    private function createOAuthTokens(User $user): array
    {
        $em = $this->getEntityManager();

        $refreshToken = $em->getRDBRepositoryByClass(RefreshToken::class)
            ->getNew()
            ->setUser($user);

        $accessToken = $em->getRDBRepositoryByClass(AccessToken::class)
            ->getNew()
            ->setUser($user);

        $em->saveEntity($refreshToken);
        $em->saveEntity($accessToken);

        return [$refreshToken, $accessToken];
    }
}
