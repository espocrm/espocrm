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

use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\League\Entities\AccessTokenEntity;
use Espo\Tools\OAuthServer\Repository\AccessTokenRepository as Repository;
use Espo\Tools\OAuthServer\Utils\Hasher;
use InvalidArgumentException;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use SensitiveParameter;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private Repository $repository,
        private Hasher $hasher,
    ) {}

    /**
     * @inheritDoc
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $entity = $this->entityManager->getRDBRepositoryByClass(AccessToken::class)->getNew();

        $accessToken = new AccessTokenEntity($entity, $this->hasher);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$accessTokenEntity instanceof AccessTokenEntity) {
            throw new InvalidArgumentException("Not supported access token entity.");
        }

        $this->entityManager->saveEntity($accessTokenEntity->getEntity());
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function revokeAccessToken(#[SensitiveParameter] $tokenId)
    {
        $entity = $this->repository->getActiveByIdentifier($tokenId);

        if (!$entity || $entity->isRevoked()) {
            return;
        }

        $entity->setRevoked();

        $this->entityManager->saveEntity($entity);
    }

    /**
     * Not existent or inactive token is treated as revoked intentionally.
     */
    public function isAccessTokenRevoked(#[SensitiveParameter] $tokenId)
    {
        $entity = $this->repository->getActiveByIdentifier($tokenId);

        if (!$entity) {
            return true;
        }

        // Intentionally.
        return !$entity->isActive();
    }
}
