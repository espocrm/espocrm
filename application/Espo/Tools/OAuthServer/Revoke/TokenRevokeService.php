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

namespace Espo\Tools\OAuthServer\Revoke;

use Espo\Core\Exceptions\Unauthorized;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\ClientValidator;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\Repository\AccessTokenRepository;
use Espo\Tools\OAuthServer\Repository\ClientRepository;
use Espo\Tools\OAuthServer\Repository\RefreshTokenRepository;
use SensitiveParameter;

class TokenRevokeService
{
    public const string TYPE_ACCESS_TOKEN = 'access_token';
    public const string TYPE_REFRESH_TOKEN = 'refresh_token';

    public function __construct(
        private User $user,
        private AccessTokenRepository $accessTokenRepository,
        private RefreshTokenRepository $refreshTokenRepository,
        private ClientRepository $clientRepository,
        private ClientValidator $clientValidator,
        private EntityManager $entityManager,
    ) {}

    /**
     * @throws Unauthorized
     */
    public function revoke(#[SensitiveParameter] Data $data): void
    {
        $tokenTypeHint = $data->tokenTypeHint;
        $token = $data->token;

        $client = $this->clientRepository->getActiveByIdentifier($data->clientId);

        if (!$client || $this->clientValidator->validate($client, $data->clientSecret)) {
            throw new Unauthorized();
        }

        if (!$tokenTypeHint || $tokenTypeHint === self::TYPE_ACCESS_TOKEN) {
            $this->revokeAccessToken($token, $client);
        }

        if (!$tokenTypeHint || $tokenTypeHint === self::TYPE_REFRESH_TOKEN) {
            $this->revokeRefreshToken($token, $client);
        }
    }

    private function revokeAccessToken(#[SensitiveParameter] string $token, Client $client): void
    {
        $accessToken = $this->accessTokenRepository->getActiveByIdentifier($token);

        if (
            !$accessToken ||
            !$this->tokenBelongsToUser($accessToken) ||
            $accessToken->getClient()->getId() !== $client->getId()
        ) {
            return;
        }

        $accessToken->setRevoked();

        $this->entityManager->saveEntity($accessToken);
    }

    private function revokeRefreshToken(#[SensitiveParameter] string $token, Client $client): void
    {
        $refreshToken = $this->refreshTokenRepository->getActiveByIdentifier($token);

        if (
            !$refreshToken ||
            !$this->tokenBelongsToUser($refreshToken) ||
            $refreshToken->getClient()->getId() !== $client->getId()
        ) {
            return;
        }

        $accessToken = $refreshToken->getAccessToken();

        $refreshToken->setRevoked();

        $this->entityManager->saveEntity($refreshToken);

        if ($accessToken) {
            $accessToken->setRevoked();

            $this->entityManager->saveEntity($accessToken);
        }
    }

    private function tokenBelongsToUser(AccessToken|RefreshToken $entry): bool
    {
        return $entry->getUser()->getId() === $this->user->getId();
    }
}
