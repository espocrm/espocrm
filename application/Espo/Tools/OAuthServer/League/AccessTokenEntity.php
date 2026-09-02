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

use DateTimeImmutable;
use Espo\Core\Field\DateTime;
use Espo\Core\Field\Link;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use InvalidArgumentException;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    public function __construct(
        private AccessToken $entity,
    ) {}

    /**
     * @inheritDoc
     * @return void
     */
    public function setPrivateKey(CryptKey $privateKey)
    {}

    public function __toString()
    {
        return $this->entity->getIdentifier();
    }

    public function getIdentifier()
    {
        return $this->entity->getIdentifier();
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->entity->setIdentifier($identifier);
    }

    public function getExpiryDateTime()
    {
        return $this->entity->getExpiresAt()->toDateTime();
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function setExpiryDateTime(DateTimeImmutable $dateTime)
    {
        $this->entity->setExpiresAt(DateTime::fromDateTime($dateTime));
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function setUserIdentifier($identifier)
    {
        if ($identifier === null) {
            throw new InvalidArgumentException("User ID must be specified.");
        }

        if (is_int($identifier)) {
            throw new InvalidArgumentException("Integer user ID is not supported.");
        }

        $link = Link::create($identifier);

        $this->entity->setUser($link);
    }

    public function getUserIdentifier()
    {
        return $this->entity->getUser()->getId();
    }

    public function getClient()
    {
        return new ClientEntity($this->entity->getClient());
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function setClient(ClientEntityInterface $client)
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$client instanceof ClientEntity) {
            throw new InvalidArgumentException("Not supported client entity.");
        }

        $this->entity->setClient($client->getEntity());
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function addScope(ScopeEntityInterface $scope)
    {
        $scopes = $this->entity->getScopes();

        if (!in_array($scope->getIdentifier(), $scopes)) {
            $scopes[] = $scope->getIdentifier();
        }

        $this->entity->setScopes($scopes);
    }

    public function getScopes()
    {
        $scopes = $this->entity->getScopes();

        return array_map(fn ($scope) => new ScopeEntity($scope), $scopes);
    }

    public function getEntity(): AccessToken
    {
        return $this->entity;
    }
}
