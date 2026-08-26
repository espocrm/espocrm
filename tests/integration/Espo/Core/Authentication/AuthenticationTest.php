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

namespace integration\Espo\Core\Authentication;

use Espo\Core\Api\Method;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\Response;
use Espo\Core\ApplicationUser;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\AuthToken\Manager;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Entities\AuthToken;
use Espo\Entities\User;
use tests\integration\Core\BaseTestCase;

class AuthenticationTest extends BaseTestCase
{
    private const string COOKIE_AUTH_TOKEN_SECRET = 'auth-token-secret';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginBasicSuccess(): void
    {
        [$username, $password] = $this->prepareTestUser();

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::once())
            ->method('setUser')
            ->with(
                $this->callback(function (User $user) use ($username) {
                    return $user->getUserName() === $username;
                })
            );

        $result = $this->createAuthentication($applicationUser)->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class)
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFail());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginBasicFailPassword(): void
    {
        [$username] = $this->prepareTestUser();

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::never())
            ->method('setUser');

        $result = $this->createAuthentication($applicationUser)->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword('wrong'),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFail());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginBasicFailUsername(): void
    {
        [, $password] = $this->prepareTestUser();

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::never())
            ->method('setUser');

        $result = $this->createAuthentication($applicationUser)->login(
            data: AuthenticationData::create()
                ->withUsername('wrong')
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFail());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginBasicFailNoPassword(): void
    {
        [$username,] = $this->prepareTestUser();

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::never())
            ->method('setUser');

        $result = $this->createAuthentication($applicationUser)->login(
            data: AuthenticationData::create()
                ->withUsername($username),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($result->isFail());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginBasicFailInactiveUser(): void
    {
        [$username, $password] = $this->prepareTestUser(isActive: false);

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::never())
            ->method('setUser');

        $result = $this->createAuthentication($applicationUser)->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFail());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenSuccess(): void
    {
        [$username, $password] = $this->prepareTestUser();

        [$token, $secret] = $this->processLogIn($username, $password);

        $secondResult = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($secondResult->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenFailWrongSecret(): void
    {
        [$username, $password] = $this->prepareTestUser();

        [$token] = $this->processLogIn($username, $password);

        $secondResult = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => 'wrong',
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($secondResult->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenFailWrongToken(): void
    {
        [$username, $password] = $this->prepareTestUser();

        [, $secret] = $this->processLogIn($username, $password);

        $secondResult = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword('wrong'),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($secondResult->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenFailInactiveToken(): void
    {
        [$username, $password] = $this->prepareTestUser();

        [$token, $secret] = $this->processLogIn($username, $password);

        $authToken = $this->getAuthTokenManager()->get($token);

        $this->assertNotNull($authToken);

        $this->getAuthTokenManager()->inactivate($authToken);

        $secondResult = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($secondResult->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenSuccessConcurrent(): void
    {
        [$username, $password] = $this->prepareTestUser();

        [$token1, $secret1] = $this->processLogIn($username, $password);
        [$token2, $secret2] = $this->processLogIn($username, $password);

        //

        $result1 = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token1),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret1,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($result1->isSuccess());

        //

        $result2 = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token2),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret2,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($result2->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenFailConcurrent(): void
    {
        $this->setConfigParams([
            'authTokenPreventConcurrent' => true,
        ]);

        [$username, $password] = $this->prepareTestUser();

        [$token1, $secret1] = $this->processLogIn($username, $password);
        [$token2, $secret2] = $this->processLogIn($username, $password);

        //

        $result2 = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token2),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret2,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($result2->isSuccess());

        //

        $result1 = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($token1),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret1,
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertFalse($result1->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLoginAuthTokenOnlySuccess(): void
    {
        [$username, $password] = $this->prepareTestUser();

        [$token, $secret] = $this->processLogIn($username, $password);

        $this->assertNotNull($secret);

        $secondResult = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withByTokenOnly(true)
                ->withPassword($token),
            request: $this->createSimpleGetRequest(
                cookieParams: [
                    self::COOKIE_AUTH_TOKEN_SECRET => $secret,
                ]
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($secondResult->isSuccess());
    }

    private function createAuthentication(?ApplicationUser $applicationUser = null): Authentication
    {
        $applicationUser ??= $this->createMock(ApplicationUser::class);

        return $this->getInjectableFactory()->createWithBinding(
            Authentication::class,
            BindingContainerBuilder::create()
                ->bindInstance(ApplicationUser::class, $applicationUser)
                ->build(),
        );
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function createSimpleGetRequest(
        array $headers = [],
        array $cookieParams = [],
    ): RequestWrapper {

        return $this->createRequest(
            method: Method::GET,
            headers: [
                HeaderKey::CREATE_TOKEN_SECRET => 'true',
                ...$headers,
            ],
            cookieParams: $cookieParams,
        );
    }

    private function getTokenSecret(string $token): ?string
    {
        $authToken = $this->getEntityManager()
            ->getRDBRepositoryByClass(AuthToken::class)
            ->where([AuthToken::FIELD_TOKEN => $token])
            ->findOne();

        return $authToken?->getSecret();
    }

    /**
     * @return array{string, string}
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processLogIn(string $username, string $password): array
    {
        $result = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(
                headers: [
                    HeaderKey::AUTHORIZATION => 'any',
                ],
            ),
            response: $this->createMock(Response::class),
        );

        $this->assertTrue($result->isSuccess());

        $token = $result->getUser()->get(User::FIELD_TOKEN);

        $this->assertNotNull($token);

        $secret = $this->getTokenSecret($token);

        $this->assertNotNull($secret);

        return [$token, $secret];
    }

    /**
     * @return array{string, string}
     */
    private function prepareUsernamePassword(): array
    {
        $username = 'test';
        $password = 'hello';

        return [$username, $password];
    }


    private function getAuthTokenManager(): Manager
    {
        return $this->getContainer()->getByClass(Manager::class);
    }

    /**
     * @return array{string, string}
     */
    private function prepareTestUser(bool $isActive = true): array
    {
        [$username, $password] = $this->prepareUsernamePassword();

        $this->createUser([
            User::FIELD_USER_NAME => $username,
            User::FIELD_PASSWORD => $password,
            User::FIELD_IS_ACTIVE => $isActive,
        ]);

        return [$username, $password];
    }
}
