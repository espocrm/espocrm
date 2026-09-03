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

use Closure;
use Espo\Core\Api\Method;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Authentication\Oidc\PkceUtil;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;
use Espo\Core\Session\Session;
use Espo\Core\Utils\Json;
use Espo\Entities\User;
use Espo\Tools\OAuthServer\ClientType;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\ClientSecret;
use Espo\Tools\OAuthServer\EntryPoints\Authorize;
use Espo\Tools\OAuthServer\EntryPoints\AuthorizeComplete;
use Espo\Tools\OAuthServer\ScopesProvider;
use Slim\Psr7\Response;
use tests\integration\Core\BaseTestCase;

class AuthorizationServerTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAuthorize(): void
    {
        $redirectUri = 'http://localhost/oauth/callback';

        $em = $this->getEntityManager();

        $client = $em->getRDBRepositoryByClass(Client::class)->getNew();
        $client
            ->setScopes([ScopesProvider::SCOPE_GLOBAL])
            ->setClientType(ClientType::Confidential)
            ->setRedirectUris([$redirectUri]);
        $em->saveEntity($client);

        $secret = $em->getRDBRepositoryByClass(ClientSecret::class)->getNew();
        $secret->setClient($client);
        $em->saveEntity($secret);

        //

        $user = $this->createUser(
            [
                User::FIELD_USER_NAME => 'test',
                User::FIELD_PASSWORD => 'hello',
            ],
        );

        //

        $session = $this->createMock(Session::class);

        $this->setApplication(
            $this->createApplication(
                binding: $this->prepareBinding(function (Binder $binder) use ($session) {
                    $binder->bindInstance(Session::class, $session);
                }),
                reuse: true,
                noUser: true,
            )
        );

        $codeChallenge = PkceUtil::generateCodeVerifier();

        //

        $request = $this->createRequest(
            method: Method::GET,
            queryParams: [
                'client_id' => $client->getIdentifier(),
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => ScopesProvider::SCOPE_GLOBAL,
                'code_challenge' => PkceUtil::hashAndEncodeCodeVerifier($codeChallenge),
                'code_challenge_method' => 'S256',
            ],
            resourcePath: '/oauth/authorize',
        );

        $response = new ResponseWrapper(new Response());

        $authorizeEntryPoint = $this->getInjectableFactory()->create(Authorize::class);

        $authorizationRequest = null;
        $sessionKey = null;

        $session
            ->expects(self::once())
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
            ->expects(self::once())
            ->method('get')
            ->with($sessionKey)
            ->willReturn($authorizationRequest);

        $session
            ->expects(self::once())
            ->method('clear')
            ->with($sessionKey);

        //

        $this->authenticate($user->getUserName());

        $this->setApplication(
            $this->createApplication(
                binding: $this->prepareBinding(function (Binder $binder) use ($session) {
                    $binder->bindInstance(Session::class, $session);
                }),
                reuse: true,
                //noUser: true,
            )
        );

        //

        $request = $this->createRequest(
            method: Method::POST,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            // @todo Use form.
            body: http_build_query([
                'clientId' => $client->getIdentifier(),
                'approved' => 'true',
            ]),
            resourcePath: '?entryPoint=oAuthAuthorizeComplete'
        );

        $response = new ResponseWrapper(new Response());

        $completeEntryPoint = $this->getInjectableFactory()->create(AuthorizeComplete::class);

        $completeEntryPoint->run($request, $response);

        // @todo Assert response.
    }

    /**
     * @todo Move to the super class.
     * @param Closure(Binder): void $callback
     */
    private function prepareBinding(Closure $callback): BindingProcessor
    {
        return new class ($callback) implements BindingProcessor {

            /**
             * @param Closure(Binder): void $callback
             */
            public function __construct(private Closure $callback) {}

            public function process(Binder $binder): void
            {
                ($this->callback)($binder);
            }
        };
    }
}
