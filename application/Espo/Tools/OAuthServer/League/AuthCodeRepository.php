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

namespace Espo\Tools\OAuthServer\League;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Link;
use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\Repository\AuthorizationCodeRepository;
use InvalidArgumentException;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private AuthorizationCodeRepository $repository,
    ) {}

    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }

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

        $entity
            ->setClient(Link::create($clientId))
            ->setIdentifier($authCodeEntity->getIdentifier())
            ->setUser(Link::create($userId))
            ->setScopes($scopes)
            ->setRedirectUri($redirectUri)
            ->setExpiresAt(DateTime::fromDateTime($authCodeEntity->getExpiryDateTime()));

        $this->entityManager->saveEntity($entity);
    }

    public function revokeAuthCode($codeId)
    {
        $code = $this->repository->getActiveByIdentifier($codeId);

        if (!$code || $code->isRevoked()) {
            return;
        }

        $code->setRevoked();

        $this->entityManager->saveEntity($code);
    }

    public function isAuthCodeRevoked($codeId)
    {
        $entity = $this->repository->getActiveByIdentifier($codeId);

        if (!$entity) {
            return true;
        }

        // Intentionally.
        return !$entity->isActive();
    }
}
