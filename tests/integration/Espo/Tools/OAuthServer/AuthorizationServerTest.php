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

namespace tests\integration\Espo\Tools\OAuthServer;

use DateTimeImmutable;
use Espo\Core\Acl\Scope;
use Espo\Core\Api\Auth;
use Espo\Core\Api\AuthBuilderFactory;
use Espo\Core\Api\Method;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\ApplicationState;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\Oidc\PkceUtil;
use Espo\Core\Binding\Binder;
use Espo\Core\Field\Date;
use Espo\Core\Session\Session;
use Espo\Core\Utils\DateTime\Clock;
use Espo\Core\Utils\Json;
use Espo\Entities\User;
use Espo\Tools\App\SettingsService;
use Espo\Tools\OAuthServer\ClientType;
use Espo\Tools\OAuthServer\ConsentDataService;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\ClientSecret;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\EntryPoints\Authorize;
use Espo\Tools\OAuthServer\EntryPoints\AuthorizeComplete;
use Espo\Tools\OAuthServer\EntryPoints\Token;
use Espo\Tools\OAuthServer\EntryPoints\TokenRevoke;
use Espo\Tools\OAuthServer\Repository\AccessTokenRepository;
use Slim\Psr7\Response;
use tests\integration\Core\BaseTestCase;

class AuthorizationServerTest extends BaseTestCase
{
    private const string REDIRECT_URI = 'http://localhost/oauth/callback';
    private const string USER_USERNAME = 'test';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthorizeConfidentialSuccess(): void
    {
        $redirectUri = self::REDIRECT_URI;

        $client = $this->createClient();
        $secret = $this->createSecret($client);

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => self::USER_USERNAME,
            ],
        );

        //

        $codeChallenge = PkceUtil::generateCodeVerifier();

        $code = $this->processObtainCode(
            client: $client,
            redirectUri: $redirectUri,
            codeChallenge: $codeChallenge,
            user: $user,
        );

        //

        [$accessToken, $refreshToken] = $this->tokenWithAuthorizationCode(
            client: $client,
            secret: $secret,
            redirectUri: $redirectUri,
            code: $code,
            codeChallenge: $codeChallenge,
        );

        //

        $this->expiredSecret(
            client: $client,
            secret: $secret,
            redirectUri: $redirectUri,
            refreshToken: $refreshToken,
        );

        //

        [$accessToken, $refreshToken] = $this->refreshTokenGrant(
            refreshToken: $refreshToken,
            client: $client,
            secret: $secret,
            redirectUri: $redirectUri,
            accessToken: $accessToken,
        );

        //

        $this->resourceSuccess($accessToken, $user);
        $this->resourceAccessTokenWrong();
        $this->resourceAccessTokenInactive($client, $accessToken);
        $this->resourceAccessTokenExpired($accessToken);

        //

        $this->refreshTokenGrantWrongSecret($client, $redirectUri);

        //

        $this->reCreateApplication(reuse: true, noUser: true);

        //

        $this->revokeAccessTokenWithAuthorizationHeader(
            client: $client,
            secret: $secret,
            accessToken: $accessToken,
        );

        $this->revokeAccessTokenWithPostParameters(
            accessToken: $accessToken,
            client: $client,
            secret: $secret,
        );

        $this->revokeRefreshToken(
            refreshToken: $refreshToken,
            client: $client,
            secret: $secret,
            accessToken: $accessToken,
        );

        $this->revokeRefreshTokenWithoutHint(
            refreshToken: $refreshToken,
            client: $client,
            secret: $secret,
            accessToken: $accessToken,
        );

        $this->revokeFailDueWrongSecret(
            accessToken: $accessToken,
            client: $client,
        );

        $this->revokeFailDueWrongClientId(
            accessToken: $accessToken,
            secret: $secret,
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthorizeWrongSecret(): void
    {
        $redirectUri = self::REDIRECT_URI;

        $client = $this->createClient();
        $this->createSecret($client);

        //

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => self::USER_USERNAME,
            ],
        );

        //

        $codeChallenge = PkceUtil::generateCodeVerifier();

        $code = $this->processObtainCode(
            client: $client,
            redirectUri: $redirectUri,
            codeChallenge: $codeChallenge,
            user: $user,
        );

        //

        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $client->getIdentifier(),
                'client_secret' => 'wrong',
                'redirect_uri' => $redirectUri,
                'code' => $code,
                'code_verifier' => $codeChallenge,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertEquals('invalid_client', $body->error);
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthorizePublicSuccess(): void
    {
        $em = $this->getEntityManager();

        //

        $redirectUri = self::REDIRECT_URI;

        $client = $this->createClient(ClientType::Public);

        //

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => self::USER_USERNAME,
            ],
        );

        //

        $codeChallenge = PkceUtil::generateCodeVerifier();

        $code = $this->processObtainCode(
            client: $client,
            redirectUri: $redirectUri,
            codeChallenge: $codeChallenge,
            user: $user,
        );

        //

        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $client->getIdentifier(),
                'redirect_uri' => $redirectUri,
                'code' => $code,
                'code_verifier' => $codeChallenge,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertEquals('Bearer', $body->token_type);
        $this->assertObjectHasProperty('access_token', $body);
        $this->assertObjectHasProperty('refresh_token', $body);
        $this->assertObjectHasProperty('expires_in', $body);

        $refreshToken = $body->refresh_token;
        $accessToken = $body->access_token;

        //

        // Refresh token grant.

        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $client->getIdentifier(),
                'redirect_uri' => $redirectUri,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertObjectHasProperty('access_token', $body);
        $this->assertObjectHasProperty('refresh_token', $body);
        $this->assertObjectHasProperty('expires_in', $body);

        $this->assertNotEquals($accessToken, $body->access_token);
        $this->assertNotEquals($refreshToken, $body->refresh_token);

        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);

        $oldAccessToken = $accessTokenRepo->getByIdentifier($accessToken);

        $this->assertNotNull($oldAccessToken);
        $this->assertTrue($oldAccessToken->isRevoked());

        $oldRefreshToken = $em->getRDBRepositoryByClass(RefreshToken::class)
            ->where([RefreshToken::FIELD_ACCESS_TOKEN . 'Id' => $oldAccessToken->getId()])
            ->findOne();

        $this->assertNotNull($oldRefreshToken);
        $this->assertTrue($oldRefreshToken->isRevoked());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthorizeWrongCodeChallenge(): void
    {
        $redirectUri = self::REDIRECT_URI;

        $client = $this->createClient();
        $secret = $this->createSecret($client);

        //

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => self::USER_USERNAME,
            ],
        );

        //

        $codeChallenge = PkceUtil::generateCodeVerifier();

        $code = $this->processObtainCode(
            client: $client,
            redirectUri: $redirectUri,
            codeChallenge: $codeChallenge,
            user: $user,
        );

        //

        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
                'redirect_uri' => $redirectUri,
                'code' => $code,
                'code_verifier' => 'wrong',
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertEquals('invalid_request', $body->error);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthorizeWrongRequestUri(): void
    {
        $redirectUri = self::REDIRECT_URI;

        $client = $this->createClient();
        $secret = $this->createSecret($client);

        //

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => self::USER_USERNAME,
            ],
        );

        //

        $codeChallenge = PkceUtil::generateCodeVerifier();

        $code = $this->processObtainCode(
            client: $client,
            redirectUri: $redirectUri,
            codeChallenge: $codeChallenge,
            user: $user,
        );

        //

        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
                'redirect_uri' => 'wrong',
                'code' => $code,
                'code_verifier' => $codeChallenge,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $result = Json::decode((string) $response->getBody());

        $this->assertEquals('invalid_request', $result->error);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWrongClientId(): void
    {
        $this->createClient();

        $clientId = 'wrong';
        $redirectUri = self::REDIRECT_URI;
        $scope = Scope::GLOBAL;

        //

        $response = $this->processCodeRequestError($clientId, $redirectUri, $scope);

        $result = Json::decode((string) $response->getBody());
        $this->assertEquals('invalid_client', $result->error);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testWrongRedirectUri(): void
    {
        $client = $this->createClient();

        $clientId = $client->getIdentifier();
        $redirectUri = 'wrong';
        $scope = Scope::GLOBAL;

        //

        $response = $this->processCodeRequestError($clientId, $redirectUri, $scope);

        $result = Json::decode((string) $response->getBody());
        $this->assertEquals('invalid_client', $result->error);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testWrongScope(): void
    {
        $client = $this->createClient();

        $clientId = $client->getIdentifier();
        $redirectUri = self::REDIRECT_URI;
        $scope = 'wrong';

        //

        $response =$this->processCodeRequestError($clientId, $redirectUri, $scope);

        $location = $response->getHeader('Location');
        $this->assertIsString($location);
        $this->assertStringContainsString('error=invalid_scope', $location);
    }

    public function testSettings(): void
    {
        $this->createUser(
            [
                User::FIELD_USER_NAME => 'admin',
                User::FIELD_TYPE => User::TYPE_ADMIN,
            ],
        );

        $this->authenticate('admin');

        $settingsService = $this->getInjectableFactory()->create(SettingsService::class);

        $this->assertObjectNotHasProperty('oAuthServerCryptKey', $settingsService->getConfigData());
    }

    private function createResponseWrapper(): ResponseWrapper
    {
        return new ResponseWrapper(new Response());
    }

    private function createClient(ClientType $type = ClientType::Confidential): Client
    {
        $em = $this->getEntityManager();

        $client = $em->getRDBRepositoryByClass(Client::class)->getNew();
        $client
            ->setScopes([Scope::GLOBAL])
            ->setClientType($type)
            ->setRedirectUris([self::REDIRECT_URI]);
        $em->saveEntity($client);

        return $client;
    }

    private function createSecret(Client $client): ClientSecret
    {
        $em = $this->getEntityManager();

        $secret = $em->getRDBRepositoryByClass(ClientSecret::class)->getNew();

        $secret
            ->setClient($client)
            ->setExpirationDate(Date::createToday()->addYears(1));

        $em->saveEntity($secret);

        return $secret;
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processCodeRequestError(
        string $clientId,
        string $redirectUri,
        string $scope,
    ): ResponseWrapper {

        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $codeChallenge = PkceUtil::generateCodeVerifier();

        //

        $request = $this->createRequest(
            method: Method::GET,
            queryParams: [
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
                'code_challenge' => PkceUtil::hashAndEncodeCodeVerifier($codeChallenge),
                'code_challenge_method' => 'S256',
            ],
            resourcePath: '/oauth/authorize',
        );

        $response = $this->createResponseWrapper();

        $authorizeEntryPoint = $this->getInjectableFactory()->create(Authorize::class);

        $authorizeEntryPoint->run($request, $response);

        return $response;
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processObtainCode(
        Client $client,
        string $redirectUri,
        string $codeChallenge,
        User $user,
    ): string {

        $session = $this->createMock(Session::class);

        $this->reCreateApplication(
            reuse: true,
            noUser: true,
            binding: $this->prepareBinding(function (Binder $binder) use ($session) {
                $binder->bindInstance(Session::class, $session);
            }),
        );

        //

        $request = $this->createRequest(
            method: Method::GET,
            queryParams: [
                'client_id' => $client->getIdentifier(),
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => Scope::GLOBAL,
                'code_challenge' => PkceUtil::hashAndEncodeCodeVerifier($codeChallenge),
                'code_challenge_method' => 'S256',
            ],
            resourcePath: '/oauth/authorize',
        );

        $response = $this->createResponseWrapper();

        $authorizeEntryPoint = $this->getInjectableFactory()->create(Authorize::class);

        $authorizationRequest = null;
        $sessionKey = null;

        $session
            ->expects(self::any())
            ->method('set')
            ->with(
                $this->callback(function ($key) use (&$sessionKey) {
                    $sessionKey = $key;

                    return true;
                }),
                $this->callback(function ($value) use (&$authorizationRequest) {
                    $authorizationRequest = $value;

                    return true;
                })
            );

        $authorizeEntryPoint->run($request, $response);

        //

        $session
            ->expects(self::any())
            ->method('get')
            ->with($sessionKey)
            ->willReturn($authorizationRequest);

        $session
            ->expects(self::once())
            ->method('clear')
            ->with($sessionKey);

        //

        $this->auth($user->getUserName());

        $this->reCreateApplication(
            reuse: true,
            binding: $this->prepareBinding(function (Binder $binder) use ($session) {
                $binder->bindInstance(Session::class, $session);
            }),
        );

        //

        $consentDataService = $this->getInjectableFactory()->create(ConsentDataService::class);

        $data = $consentDataService->getData($client->getIdentifier());

        $this->assertIsArray($data->scopeDataList);

        //

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'clientId' => $client->getIdentifier(),
                'approved' => 'true',
            ]),
            resourcePath: '?entryPoint=oAuthAuthorizeComplete',
        );

        $response = $this->createResponseWrapper();

        $completeEntryPoint = $this->getInjectableFactory()->create(AuthorizeComplete::class);

        $completeEntryPoint->run($request, $response);

        $location = $response->getHeader('Location');

        $this->assertIsString($location);
        $this->assertStringStartsWith($redirectUri, $location);

        $urlData = parse_url($location);
        parse_str($urlData['query'], $queryParams);
        $this->assertIsString($queryParams['code']);

        return $queryParams['code'];
    }

    private function prepareAuth(): Auth
    {
        $authentication = $this->getInjectableFactory()->create(Authentication::class);

        $authBuilderFactory = $this->getInjectableFactory()->create(AuthBuilderFactory::class);

        return $authBuilderFactory->create()
            ->setAuthentication($authentication)
            ->setAuthRequired(true)
            ->build();
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function revokeFailDueWrongClientId(
        string $accessToken,
        ClientSecret $secret,
    ): void  {

        $tokenRevokeEntryPoint = $this->getInjectableFactory()->create(TokenRevoke::class);
        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);
        $em = $this->getEntityManager();

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'token' => $accessToken,
                'token_type_hint' => 'access_token',
                'client_id' => 'wrong',
                'client_secret' => $secret->getValue(),
            ]),
            resourcePath: '/oauth/token/revoke',
        );

        $response = $this->createResponseWrapper();

        $em->getTransactionManager()->start();

        $tokenRevokeEntryPoint->run($request, $response);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());

        $em->getTransactionManager()->rollback();
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function revokeAccessTokenWithAuthorizationHeader(
        Client $client,
        ClientSecret $secret,
        string $accessToken,
    ): void {

        $tokenRevokeEntryPoint = $this->getInjectableFactory()->create(TokenRevoke::class);
        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);
        $em = $this->getEntityManager();

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($client->getIdentifier() . ':' . $secret->getValue()),
            ],
            body: http_build_query([
                'token' => $accessToken,
                'token_type_hint' => 'access_token',
            ]),
            resourcePath: '/oauth/token/revoke',
        );

        $response = $this->createResponseWrapper();

        $em->getTransactionManager()->start();

        $tokenRevokeEntryPoint->run($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());

        $em->getTransactionManager()->rollback();

        $this->assertFalse($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function revokeAccessTokenWithPostParameters(
        string $accessToken,
        Client $client,
        ClientSecret $secret,
    ): void {

        $tokenRevokeEntryPoint = $this->getInjectableFactory()->create(TokenRevoke::class);
        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);
        $em = $this->getEntityManager();

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'token' => $accessToken,
                'token_type_hint' => 'access_token',
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
            ]),
            resourcePath: '/oauth/token/revoke',
        );

        $response = $this->createResponseWrapper();

        $em->getTransactionManager()->start();

        $tokenRevokeEntryPoint->run($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());

        $em->getTransactionManager()->rollback();

        //

        $this->assertFalse($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());
    }


    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function revokeRefreshToken(
        string $refreshToken,
        Client $client,
        ClientSecret $secret,
        string $accessToken,
    ): void {

        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);
        $em = $this->getEntityManager();

        $tokenRevokeEntryPoint = $this->getInjectableFactory()->create(TokenRevoke::class);

        $this->assertEquals(
            1,
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->where([RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE])
                ->count()
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'token' => $refreshToken,
                'token_type_hint' => 'refresh_token',
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
            ]),
            resourcePath: '/oauth/token/revoke',
        );

        $response = $this->createResponseWrapper();

        $em->getTransactionManager()->start();

        $tokenRevokeEntryPoint->run($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($accessTokenRepo->getByIdentifier($accessToken)?->isRevoked());
        $this->assertEquals(
            0,
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->where([RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE])
                ->count()
        );

        $em->getTransactionManager()->rollback();

        //

        $this->assertFalse($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());
        $this->assertEquals(
            1,
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->where([RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE])
                ->count()
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function revokeRefreshTokenWithoutHint(
        string $refreshToken,
        Client $client,
        ClientSecret $secret,
        string $accessToken,
    ) {

        $tokenRevokeEntryPoint = $this->getInjectableFactory()->create(TokenRevoke::class);
        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);
        $em = $this->getEntityManager();

        $this->assertEquals(
            1,
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->where([RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE])
                ->count()
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'token' => $refreshToken,
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
            ]),
            resourcePath: '/oauth/token/revoke',
        );

        $response = $this->createResponseWrapper();

        $em->getTransactionManager()->start();

        $tokenRevokeEntryPoint->run($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($accessTokenRepo->getByIdentifier($accessToken)?->isRevoked());
        $this->assertEquals(
            0,
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->where([RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE])
                ->count()
        );

        $em->getTransactionManager()->rollback();

        //

        $this->assertFalse($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());
        $this->assertEquals(
            1,
            $em->getRDBRepositoryByClass(RefreshToken::class)
                ->where([RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE])
                ->count()
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function revokeFailDueWrongSecret(
        string $accessToken,
        Client $client,
    ): void {

        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);
        $em = $this->getEntityManager();

        $tokenRevokeEntryPoint = $this->getInjectableFactory()->create(TokenRevoke::class);

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'token' => $accessToken,
                'token_type_hint' => 'access_token',
                'client_id' => $client->getIdentifier(),
                'client_secret' => 'wrong',
            ]),
            resourcePath: '/oauth/token/revoke',
        );

        $response = $this->createResponseWrapper();

        $em->getTransactionManager()->start();

        $tokenRevokeEntryPoint->run($request, $response);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($accessTokenRepo->getByIdentifier($accessToken)->isRevoked());

        $em->getTransactionManager()->rollback();
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function refreshTokenGrantWrongSecret(Client $client, string $redirectUri): void
    {
        $this->setApplication(
            $this->createApplication(
                reuse: true,
                noUser: true,
            )
        );

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => 'wrong',
                'client_id' => $client->getIdentifier(),
                'client_secret' => 'wrong',
                'redirect_uri' => $redirectUri,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $this->assertEquals(401, $response->getStatusCode());

        $body = Json::decode((string) $response->getBody());
        $this->assertEquals('invalid_client', $body->error);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function resourceAccessTokenExpired($accessToken): void
    {
        $clock = $this->createMock(Clock::class);
        $clock->expects(self::any())
            ->method('now')
            ->willReturn((new DateTimeImmutable())->modify('+1 day'));

        $this->reCreateApplication(
            reuse: true,
            noUser: true,
            binding: $this->prepareBinding(function ($binder) use ($clock) {
                $binder->bindInstance(Clock::class, $clock);
            }),
        );

        $request = $this->createRequest(
            method::GET,
            headers: [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        );

        $auth = $this->prepareAuth();

        $response = $this->createResponse();

        $result = $auth->process($request, $response);

        $this->assertFalse($result->isResolved());
        $this->assertFalse($this->getContainer()->getByClass(ApplicationState::class)->hasUser());

        $body = Json::decode((string) $response->getBody());
        $this->assertEquals('access_denied', $body->error);
        $this->assertStringContainsString('expired', $body->error_description);
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function resourceAccessTokenInactive(Client $client, string $accessToken): void
    {
        $em = $this->getEntityManager();

        $client->setInactive();
        $em->saveEntity($client);

        $this->reCreateApplication(reuse: true, noUser: true);

        $request = $this->createRequest(
            method::GET,
            headers: [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        );

        $auth = $this->prepareAuth();

        $response = $this->createResponse();

        $result = $auth->process($request, $response);

        $this->assertFalse($result->isResolved());
        $this->assertFalse($this->getContainer()->getByClass(ApplicationState::class)->hasUser());

        $body = Json::decode((string) $response->getBody());
        $this->assertEquals('access_denied', $body->error);
        $this->assertEquals(401, $response->getStatusCode());

        //

        $client->setActive();
        $em->saveEntity($client);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function resourceAccessTokenWrong(): void
    {
        $this->reCreateApplication(reuse: true, noUser: true);

        $request = $this->createRequest(
            method::GET,
            headers: [
                'Authorization' => 'Bearer wrong',
            ],
        );

        $auth = $this->prepareAuth();

        $response = $this->createResponse();

        $result = $auth->process($request, $response);

        $this->assertFalse($result->isResolved());
        $this->assertFalse($this->getContainer()->getByClass(ApplicationState::class)->hasUser());

        $this->assertNotNull($response->getHeader('WWW-Authenticate'));

        $body = Json::decode((string) $response->getBody());
        $this->assertObjectHasProperty('error', $body);
        $this->assertEquals('access_denied', $body->error);
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function resourceSuccess($accessToken, User $user): void
    {
        $this->reCreateApplication(reuse: true, noUser: true);

        $request = $this->createRequest(
            method::GET,
            headers: [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        );

        $auth = $this->prepareAuth();

        $response = $this->createResponse();

        $result = $auth->process($request, $response);

        $this->assertTrue($result->isResolved());
        $this->assertEquals($user->getId(), $this->getContainer()->getByClass(ApplicationState::class)->getUserId());

        $loggedUser = $this->getContainer()->getByClass(ApplicationState::class)->getUser();

        $this->assertEquals([Scope::GLOBAL], $loggedUser->getScopes());
    }

    /**
     * @return array{string, string}
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function refreshTokenGrant(
        string $refreshToken,
        Client $client,
        ClientSecret $secret,
        string $redirectUri,
        string $accessToken,
    ): array {

        $this->reCreateApplication(reuse: true, noUser: true);

        $em = $this->getEntityManager();

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
                'redirect_uri' => $redirectUri,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertObjectHasProperty('access_token', $body);
        $this->assertObjectHasProperty('refresh_token', $body);
        $this->assertObjectHasProperty('expires_in', $body);

        $this->assertNotEquals($accessToken, $body->access_token);
        $this->assertNotEquals($refreshToken, $body->refresh_token);

        $accessTokenRepo = $this->getInjectableFactory()->create(AccessTokenRepository::class);

        $oldAccessToken = $accessTokenRepo->getByIdentifier($accessToken);

        $this->assertNotNull($oldAccessToken);
        $this->assertTrue($oldAccessToken->isRevoked());

        $oldRefreshToken = $em->getRDBRepositoryByClass(RefreshToken::class)
            ->where([RefreshToken::FIELD_ACCESS_TOKEN . 'Id' => $oldAccessToken->getId()])
            ->findOne();

        $this->assertNotNull($oldRefreshToken);
        $this->assertTrue($oldRefreshToken->isRevoked());

        $accessToken = $body->access_token;
        $refreshToken = $body->refresh_token;

        return [$accessToken, $refreshToken];
    }

    /**
     * @return array{string, string}
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function tokenWithAuthorizationCode(
        Client $client,
        ClientSecret $secret,
        string $redirectUri,
        string $code,
        string $codeChallenge,
    ): array {

        $this->reCreateApplication(reuse: true, noUser: true);

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
                'redirect_uri' => $redirectUri,
                'code' => $code,
                'code_verifier' => $codeChallenge,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertEquals('Bearer', $body->token_type);
        $this->assertObjectHasProperty('access_token', $body);
        $this->assertObjectHasProperty('refresh_token', $body);
        $this->assertObjectHasProperty('expires_in', $body);

        $refreshToken = $body->refresh_token;
        $accessToken = $body->access_token;

        return [$accessToken, $refreshToken];
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function expiredSecret(
        Client $client,
        ClientSecret $secret,
        string $redirectUri,
        string $refreshToken,
    ): void {

        $clock = $this->createMock(Clock::class);
        $clock->expects(self::any())
            ->method('now')
            ->willReturn((new DateTimeImmutable())->modify('+1 year'));

        $this->reCreateApplication(
            reuse: true,
            noUser: true,
            binding: $this->prepareBinding(function ($binder) use ($clock) {
                $binder->bindInstance(Clock::class, $clock);
            }),
        );

        $em = $this->getEntityManager();

        $em->getTransactionManager()->start();

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            body: http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $client->getIdentifier(),
                'client_secret' => $secret->getValue(),
                'redirect_uri' => $redirectUri,
            ]),
            resourcePath: '/oauth/token',
        );

        $response = $this->createResponseWrapper();

        $tokenEntryPoint = $this->getInjectableFactory()->create(Token::class);

        $tokenEntryPoint->run($request, $response);

        $body = Json::decode((string) $response->getBody());

        $this->assertEquals('invalid_client', $body->error);

        $em->getTransactionManager()->rollback();
    }
}
