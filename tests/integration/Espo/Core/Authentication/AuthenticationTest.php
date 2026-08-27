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
use Espo\Core\ApplicationState;
use Espo\Core\ApplicationUser;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\AuthToken\Manager;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Core\Authentication\Logins\Hmac;
use Espo\Core\Authentication\Result;
use Espo\Core\Authentication\TwoFactor\Login;
use Espo\Core\Authentication\TwoFactor\LoginFactory as TwoFactorLoginFactory;
use Espo\Core\Authentication\TwoFactor\MethodProvider as TwoFactorMethodProvider;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Utils\Util;
use Espo\Entities\AuthToken;
use Espo\Entities\Portal;
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
        [$username, $password, $user] = $this->prepareTestUser();

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
        $this->assertEquals($user->getId(), $result->getUser()->getId());
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

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLogin2Fa(): void
    {
        $this->setConfigParams([
            'auth2FA' => true,
            'auth2FAMethodList' => ['TestMethod'],
        ]);

        [$username, $password, $user] = $this->prepareTestUser();

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::never())
            ->method('setUser');

        $twoFactorLogin = $this->createMock(Login::class);

        $twoFactorLogin
            ->expects(self::once())
            ->method('login')
            ->with(
                $this->callback(function (Result $result) use ($user) {
                    return $result->getUser()->getId() === $user->getId();
                })
            )
            ->willReturn(
                Result::secondStepRequired(
                    user: $user,
                    data: Result\Data::create(),
                )
            );

        $authentication = $this->createAuthentication(
            applicationUser: $applicationUser,
            twoFactorLogin: $twoFactorLogin,
            twoFactorMethod: 'TestMethod',
            userId: $user->getId(),
        );

        $result = $authentication->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isFail());
        $this->assertTrue($result->isSecondStepRequired());

        $this->assertNull(
            $this->getEntityManager()
                ->getRDBRepositoryByClass(AuthToken::class)
                ->where([AuthToken::ATTR_USER_ID => $user->getId()])
                ->findOne()
        );

        //

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::once())
            ->method('setUser');

        $twoFactorLogin = $this->createMock(Login::class);

        $twoFactorLogin
            ->expects(self::once())
            ->method('login')
            ->with(
                $this->callback(function (Result $result) use ($user) {
                    return $result->getUser()->getId() === $user->getId();
                })
            )
            ->willReturnCallback(function (Result $result) {
                return $result;
            });

        $authentication = $this->createAuthentication(
            applicationUser: $applicationUser,
            twoFactorLogin: $twoFactorLogin,
            twoFactorMethod: 'TestMethod',
            userId: $user->getId(),
        );

        $result = $authentication->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class)
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isSecondStepRequired());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testPortalBasicSuccess(): void
    {
        $em = $this->getEntityManager();

        $portal = $em->getRDBRepositoryByClass(Portal::class)->getNew();
        $portal->setName('Test');
        $em->saveEntity($portal);

        [$username, $password] = $this->prepareTestUser(
            isPortal: true,
            portalIds: [$portal->getId()],
        );

        $applicationUser = $this->createMock(ApplicationUser::class);

        $applicationUser
            ->expects(self::once())
            ->method('setUser')
            ->with(
                $this->callback(function (User $user) use ($username) {
                    return $user->getUserName() === $username;
                })
            );

        $authentication = $this->createAuthentication($applicationUser, portal: $portal);

        $result = $authentication->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class)
        );

        $this->assertTrue($result->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testPortalFailNonPortalUser(): void
    {
        $em = $this->getEntityManager();

        $portal = $em->getRDBRepositoryByClass(Portal::class)->getNew();
        $portal->setName('Test');
        $em->saveEntity($portal);

        [$username, $password] = $this->prepareTestUser();

        $authentication = $this->createAuthentication(portal: $portal);

        $result = $authentication->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testPortalFailWrongPortal(): void
    {
        $em = $this->getEntityManager();

        $portal = $em->getRDBRepositoryByClass(Portal::class)->getNew();
        $portal->setName('Test');
        $em->saveEntity($portal);

        $portalAnother = $em->getRDBRepositoryByClass(Portal::class)->getNew();
        $portalAnother->setName('Test Another');
        $em->saveEntity($portalAnother);

        [$username, $password] = $this->prepareTestUser(
            isPortal: true,
            portalIds: [$portal->getId()],
        );

        $authentication = $this->createAuthentication(portal: $portalAnother);

        $result = $authentication->login(
            data: AuthenticationData::create()
                ->withUsername($username)
                ->withPassword($password),
            request: $this->createSimpleGetRequest(),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testApiKeySuccess(): void
    {
        $username = 'test';

        $em = $this->getEntityManager();

        $apiKey = Util::generateApiKey();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setType(User::TYPE_API)
            ->setUserName($username)
            ->setApiKey($apiKey)
            ->setAuthMethod(ApiKey::NAME);
        $em->saveEntity($user);

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
                ->withMethod(ApiKey::NAME),
            request: $this->createSimpleGetRequest(
                headers: [
                    ApiKey::HEADER_API_KEY => $apiKey,
                ],
            ),
            response: $this->createMock(Response::class)
        );

        $this->assertTrue($result->isSuccess());

        $this->assertNull(
            $em->getRDBRepositoryByClass(AuthToken::class)
                ->where([
                    AuthToken::ATTR_USER_ID => $user->getId(),
                ])
                ->findOne()
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testApiKeyFailWrongApiKey(): void
    {
        $username = 'test';

        $em = $this->getEntityManager();

        $apiKey = Util::generateApiKey();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setType(User::TYPE_API)
            ->setUserName($username)
            ->setApiKey($apiKey)
            ->setAuthMethod(ApiKey::NAME);
        $em->saveEntity($user);

        $result = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withMethod(ApiKey::NAME),
            request: $this->createSimpleGetRequest(
                headers: [
                    ApiKey::HEADER_API_KEY => 'wrong',
                ],
            ),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testApiKeyFailWrongMethod(): void
    {
        $username = 'test';

        $em = $this->getEntityManager();

        $apiKey = Util::generateApiKey();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setType(User::TYPE_API)
            ->setUserName($username)
            ->setApiKey($apiKey)
            ->setSecretKey(Util::generateApiKey())
            ->setAuthMethod(Hmac::NAME);
        $em->saveEntity($user);

        $result = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withMethod(ApiKey::NAME),
            request: $this->createSimpleGetRequest(
                headers: [
                    ApiKey::HEADER_API_KEY => $apiKey,
                ],
            ),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testHmacSuccess(): void
    {
        $username = 'test';

        $em = $this->getEntityManager();

        $apiKey = Util::generateApiKey();
        $secretKey = Util::generateSecretKey();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setType(User::TYPE_API)
            ->setUserName($username)
            ->setApiKey($apiKey)
            ->setSecretKey($secretKey)
            ->setAuthMethod(Hmac::NAME);
        $em->saveEntity($user);

        $resourcePath = '/api/v1/Test';

        $string = Method::GET . ' ' . $resourcePath;
        $authorizationHeader = base64_encode($apiKey . ':' . hash_hmac('sha256', $string, $secretKey));

        $result = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withMethod(Hmac::NAME),
            request: $this->createSimpleGetRequest(
                headers: [
                    Hmac::HEADER_HMAC_AUTHORIZATION => $authorizationHeader,
                ],
                resourcePath: $resourcePath,
            ),
            response: $this->createMock(Response::class)
        );

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($user->getId(), $result->getUser()->getId());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testHmacFailWrongSecret(): void
    {
        $username = 'test';

        $em = $this->getEntityManager();

        $apiKey = Util::generateApiKey();
        $secretKey = Util::generateSecretKey();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setType(User::TYPE_API)
            ->setUserName($username)
            ->setApiKey($apiKey)
            ->setSecretKey($secretKey)
            ->setAuthMethod(Hmac::NAME);
        $em->saveEntity($user);

        $resourcePath = '/api/v1/Test';

        $string = Method::GET . ' ' . $resourcePath;
        $authorizationHeader = base64_encode($apiKey . ':' . hash_hmac('sha256', $string, 'wrong'));

        $result = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withMethod(Hmac::NAME),
            request: $this->createSimpleGetRequest(
                headers: [
                    Hmac::HEADER_HMAC_AUTHORIZATION => $authorizationHeader,
                ],
                resourcePath: $resourcePath,
            ),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testHmacFailWrongResourcePath(): void
    {
        $username = 'test';

        $em = $this->getEntityManager();

        $apiKey = Util::generateApiKey();
        $secretKey = Util::generateSecretKey();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setType(User::TYPE_API)
            ->setUserName($username)
            ->setApiKey($apiKey)
            ->setSecretKey($secretKey)
            ->setAuthMethod(Hmac::NAME);
        $em->saveEntity($user);

        $string = Method::GET . ' ' . '/Wrong';
        $authorizationHeader = base64_encode($apiKey . ':' . hash_hmac('sha256', $string, $secretKey));

        $result = $this->createAuthentication()->login(
            data: AuthenticationData::create()
                ->withMethod(Hmac::NAME),
            request: $this->createSimpleGetRequest(
                headers: [
                    Hmac::HEADER_HMAC_AUTHORIZATION => $authorizationHeader,
                ],
                resourcePath: '/api/v1',
            ),
            response: $this->createMock(Response::class)
        );

        $this->assertFalse($result->isSuccess());
    }

    private function createAuthentication(
        ?ApplicationUser $applicationUser = null,
        ?Login $twoFactorLogin = null,
        ?string $twoFactorMethod = null,
        ?string $userId = null,
        ?Portal $portal = null,
    ): Authentication {

        $applicationUser ??= $this->createMock(ApplicationUser::class);

        $builder = BindingContainerBuilder::create()
            ->bindInstance(ApplicationUser::class, $applicationUser);

        if ($twoFactorLogin) {
            $twoFactorLoginFactory = $this->createMock(TwoFactorLoginFactory::class);

            $twoFactorLoginFactory
                ->expects(self::once())
                ->method('create')
                ->with($twoFactorMethod)
                ->willReturn($twoFactorLogin);

            $builder->bindInstance(TwoFactorLoginFactory::class, $twoFactorLoginFactory);
        }

        if ($userId && $twoFactorMethod) {
            $twoFactorMethodProvider = $this->createMock(TwoFactorMethodProvider::class);

            $twoFactorMethodProvider
                ->expects(self::once())
                ->method('get')
                ->with($userId)
                ->willReturn($twoFactorMethod);

            $builder->bindInstance(TwoFactorMethodProvider::class, $twoFactorMethodProvider);
        }

        if ($portal) {
            $applicationState = $this->createMock(ApplicationState::class);

            $applicationState
                ->expects(self::any())
                ->method('isPortal')
                ->willReturn(true);

            $applicationState
                ->expects(self::any())
                ->method('getPortal')
                ->willReturn($portal);

            $builder->bindInstance(ApplicationState::class, $applicationState);
        }

        return $this->getInjectableFactory()->createWithBinding(Authentication::class, $builder->build());
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function createSimpleGetRequest(
        array $headers = [],
        array $cookieParams = [],
        string $resourcePath = '',
    ): RequestWrapper {

        return $this->createRequest(
            method: Method::GET,
            headers: [
                HeaderKey::CREATE_TOKEN_SECRET => 'true',
                ...$headers,
            ],
            cookieParams: $cookieParams,
            resourcePath: $resourcePath,
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
     * @return array{string, string, User}
     */
    private function prepareTestUser(
        bool $isActive = true,
        bool $isPortal = false,
        array $portalIds = [],
    ): array {

        [$username, $password] = $this->prepareUsernamePassword();

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => $username,
                User::FIELD_PASSWORD => $password,
                User::FIELD_IS_ACTIVE => $isActive,
                User::LINK_PORTALS . 'Ids' => $portalIds,
            ],
            isPortal: $isPortal,
        );

        return [$username, $password, $user];
    }
}
