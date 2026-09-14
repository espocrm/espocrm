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

namespace Espo\Tools\OAuthServer;

use Espo\Core\ApplicationState;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\User;
use Espo\Tools\OAuthServer\League\AuthorizationRequestStorage;
use Espo\Tools\OAuthServer\League\AuthorizationServerFactory;
use Espo\Tools\OAuthServer\League\Entities\ScopeEntity;
use Espo\Tools\OAuthServer\League\Entities\UserEntity;
use Espo\Tools\OAuthServer\Scope\ScopeSorter;
use Espo\Tools\OAuthServer\Utils\UriUtil;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationService
{
    public function __construct(
        private AuthorizationServerFactory $authorizationServerFactory,
        private ApplicationState $applicationState,
        private AuthorizationRequestStorage $authorizationRequestStorage,
        private ScopeSorter $scopeSorter,
    ) {}

    /**
     * @throws Error
     */
    public function authorizeStart(ServerRequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        $server = $this->authorizationServerFactory->create();

        try {
            $authRequest = $server->validateAuthorizationRequest($request);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($response);
        }

        $clientId = $authRequest->getClient()->getIdentifier();

        $this->authorizationRequestStorage->store($clientId, $authRequest);

        return null;
    }

    /**
     * @param non-empty-string[] $scopes
     * @throws NotFound
     * @throws Error
     * @throws Forbidden
     * @throws BadRequest
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function authorizeComplete(
        string $clientId,
        ResponseInterface $response,
        bool $approved,
        array $scopes,
    ): ResponseInterface {

        $scopes = $this->scopeSorter->sort($scopes);

        $authorizationRequest = $this->getAuthRequestFromSession($clientId);

        $this->validateScopesOnCompletion($authorizationRequest, $scopes);

        $authorizationRequest->setScopes(array_map(fn ($it) => new ScopeEntity($it), $scopes));

        $user = $this->applicationState->getUser();

        try {
            $this->assertUser($user, $authorizationRequest);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($response);
        }

        $authorizationRequest->setUser(new UserEntity($user));
        $authorizationRequest->setAuthorizationApproved($approved);

        $server = $this->authorizationServerFactory->create();

        try {
            return $server->completeAuthorizationRequest($authorizationRequest, $response);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($response);
        }
    }

    /**
     * @throws Error
     */
    public function token(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $server = $this->authorizationServerFactory->create();

        try {
            return $server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($response);
        }
    }

    /**
     * @throws NotFound
     */
    private function getAuthRequestFromSession(string $clientId): AuthorizationRequest
    {
        $authRequest = $this->authorizationRequestStorage->get($clientId, clear: true);

        if (!$authRequest) {
            throw new NotFound("Session not found.");
        }

        return $authRequest;
    }

    /**
     * @throws Error
     * @throws OAuthServerException
     */
    private function assertUser(User $user, AuthorizationRequest $authorizationRequest): void
    {
        if ($user->isRegular() || $user->getType() === User::TYPE_ADMIN) {
            return;
        }

        $redirectUri = $authorizationRequest->getRedirectUri();

        if ($redirectUri && $authorizationRequest->getState()) {
            $redirectUri = UriUtil::makeRedirectUri($redirectUri, $authorizationRequest->getState());
        }

        throw OAuthServerException::accessDenied("User is not allowed.", $redirectUri);
    }

    /**
     * @param string[] $scopes
     * @throws Forbidden
     * @throws BadRequest
     */
    private function validateScopesOnCompletion(AuthorizationRequest $authorizationRequest, array $scopes): void
    {
        if (count(array_unique($scopes)) !== count($scopes)) {
            throw new BadRequest("Duplicate scopes.");
        }

        $storedScopes = array_map(fn ($it) => $it->getIdentifier(), $authorizationRequest->getScopes());

        if (array_diff($scopes, $storedScopes)) {
            throw new Forbidden("Scope mismatch.");
        }
    }
}
