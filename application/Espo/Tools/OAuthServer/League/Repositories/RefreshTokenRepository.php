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

namespace Espo\Tools\OAuthServer\League\Repositories;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Link;
use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\League\Entities\AccessTokenEntity;
use Espo\Tools\OAuthServer\League\Entities\RefreshTokenEntity;
use Espo\Tools\OAuthServer\Repository\ClientRepository as ClientRepositoryInternal;
use Espo\Tools\OAuthServer\Repository\RefreshTokenRepository as RefreshTokenRepositoryInternal;
use Espo\Tools\OAuthServer\Utils\Hasher;
use InvalidArgumentException;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use RuntimeException;
use SensitiveParameter;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private RefreshTokenRepositoryInternal $repository,
        private ClientRepositoryInternal $clientRepository,
        private Hasher $hasher,
    ) {}

    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $entity = $this->entityManager->getRDBRepositoryByClass(RefreshToken::class)->getNew();

        $userId = $refreshTokenEntity->getAccessToken()->getUserIdentifier();

        if (!is_string($userId)) {
            throw new InvalidArgumentException("User ID must be string.");
        }

        $clientId = $refreshTokenEntity->getAccessToken()->getClient()->getIdentifier();

        $client = $this->clientRepository->getActiveByIdentifier($clientId) ??
            throw new RuntimeException("Client not found.");

        $accessToken = $refreshTokenEntity->getAccessToken();

        if (!$accessToken instanceof AccessTokenEntity) {
            throw new InvalidArgumentException("Not supported access token implementation.");
        }

        $hash = $this->hasher->hash($refreshTokenEntity->getIdentifier());

        $entity
            ->setClient($client)
            ->setAccessToken($accessToken->getEntity())
            ->setHash($hash)
            ->setUser(Link::create($userId))
            ->setExpiresAt(DateTime::fromDateTime($refreshTokenEntity->getExpiryDateTime()));

        $this->entityManager->saveEntity($entity);
    }

    /**
     * @todo Delay?
     * @inheritDoc
     * @return void
     */
    public function revokeRefreshToken(#[SensitiveParameter] $tokenId)
    {
        $token = $this->repository->getActiveByIdentifier($tokenId);

        if (!$token || $token->isRevoked()) {
            return;
        }

        $token->setRevoked();

        $this->entityManager->saveEntity($token);
    }

    public function isRefreshTokenRevoked(#[SensitiveParameter] $tokenId)
    {
        $entity = $this->repository->getActiveByIdentifier($tokenId);

        if (!$entity) {
            return true;
        }

        // Intentionally.
        return !$entity->isActive();
    }
}
