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

namespace Espo\Tools\OAuthServer\League\Entities;

use DateTimeImmutable;
use Espo\Core\Field\DateTime;
use Espo\Core\Field\Link;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Utils\Hasher;
use InvalidArgumentException;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    public function __construct(
        private AccessToken $entity,
        private Hasher $hasher,
    ) {}

    public function toString(): string
    {
        return $this->entity->getIdentifier();
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {}

    public function __toString()
    {
        return $this->toString();
    }

    public function getIdentifier(): string
    {
        return $this->entity->getIdentifier();
    }

    public function setIdentifier(string $identifier): void
    {
        $this->entity->setIdentifier($identifier);

        $hash = $this->hasher->hash($identifier);

        $this->entity->setHash($hash);
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->entity->getExpiresAt()->toDateTime();
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->entity->setExpiresAt(DateTime::fromDateTime($dateTime));
    }

    public function setUserIdentifier(string $identifier): void
    {
        $link = Link::create($identifier);

        $this->entity->setUser($link);
    }

    public function getUserIdentifier(): ?string
    {
        /** @var non-empty-string */
        return $this->entity->getUser()->getId();
    }

    public function getClient(): ClientEntityInterface
    {
        return ClientEntity::fromEntity($this->entity->getClient());
    }


    public function setClient(ClientEntityInterface $client): void
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$client instanceof ClientEntity) {
            throw new InvalidArgumentException("Not supported client entity.");
        }

        $this->entity->setClient(Link::create($client->entityId));
    }


    public function addScope(ScopeEntityInterface $scope): void
    {
        $scopes = $this->entity->getScopes();

        if (!in_array($scope->getIdentifier(), $scopes)) {
            $scopes[] = $scope->getIdentifier();
        }

        $this->entity->setScopes($scopes);
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array
    {
        $scopes = $this->entity->getScopes();

        return array_map(fn ($scope) => new ScopeEntity($scope), $scopes);
    }

    public function getEntity(): AccessToken
    {
        return $this->entity;
    }
}
