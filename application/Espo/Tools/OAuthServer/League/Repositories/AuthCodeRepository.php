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
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\League\Entities\AuthCodeEntity;
use Espo\Tools\OAuthServer\Repository\AuthorizationCodeRepository;
use Espo\Tools\OAuthServer\Repository\ClientRepository as ClientRepository;
use Espo\Tools\OAuthServer\Utils\Hasher;
use InvalidArgumentException;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use RuntimeException;
use SensitiveParameter;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private AuthorizationCodeRepository $repository,
        private ClientRepository $clientRepository,
        private Hasher $hasher,
    ) {}

    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $entity = $this->entityManager->getRDBRepositoryByClass(AuthorizationCode::class)->getNew();

        $userId = $authCodeEntity->getUserIdentifier();

        if (!is_string($userId)) {
            throw new InvalidArgumentException("User ID must be string.");
        }

        $scopes = array_map(fn ($scope) => $scope->getIdentifier(), $authCodeEntity->getScopes());

        $redirectUri = $authCodeEntity->getRedirectUri() ?? throw new InvalidArgumentException("No redirect URI");

        $clientId = $authCodeEntity->getClient()->getIdentifier();

        $client = $this->clientRepository->getActiveByIdentifier($clientId);

        if (!$client) {
            throw new RuntimeException("Client not found.");
        }

        $hash = $this->hasher->hash($authCodeEntity->getIdentifier());

        $entity
            ->setClient($client)
            ->setHash($hash)
            ->setUser(Link::create($userId))
            ->setScopes($scopes)
            ->setRedirectUri($redirectUri)
            ->setExpiresAt(DateTime::fromDateTime($authCodeEntity->getExpiryDateTime()));

        $this->entityManager->saveEntity($entity);
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function revokeAuthCode(#[SensitiveParameter] $codeId)
    {
        $code = $this->repository->getActiveByIdentifier($codeId);

        if (!$code || $code->isRevoked()) {
            return;
        }

        $code->setRevoked();

        $this->entityManager->saveEntity($code);
    }

    public function isAuthCodeRevoked(#[SensitiveParameter] $codeId)
    {
        $entity = $this->repository->getActiveByIdentifier($codeId);

        if (!$entity) {
            return true;
        }

        // Intentionally.
        return !$entity->isActive();
    }
}
