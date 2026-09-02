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

namespace Espo\Tools\OAuthServer\Entities;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Link;
use Espo\Core\ORM\Entity;
use Espo\Entities\User;
use UnexpectedValueException;

class RefreshToken extends Entity
{
    public const string ENTITY_TYPE = 'OAuthRefreshToken';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_CLIENT = 'client';
    public const string FIELD_EXPIRES_AT = 'expiresAt';
    public const string FIELD_USER = 'user';
    public const string FIELD_ACCESS_TOKEN = 'accessToken';
    public const string FIELD_HASH = 'hash';

    public const string STATUS_ACTIVE = 'Active';
    public const string STATUS_REVOKED = 'Revoked';
    public const string STATUS_EXPIRED = 'Expired';

    public function setHash(string $value): self
    {
        return $this->set(self::FIELD_HASH, $value);
    }

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function isRevoked(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_REVOKED;
    }

    public function setStatus(string $status): self
    {
        return $this->set(self::FIELD_STATUS, $status);
    }

    public function getClient(): Client
    {
        $client = $this->relations->getOne(self::FIELD_CLIENT);

        if (!$client instanceof Client) {
            throw new UnexpectedValueException("No client.");
        }

        return $client;
    }

    public function setAccessToken(AccessToken $accessToken): self
    {
        return $this->setRelatedLinkOrEntity(self::FIELD_ACCESS_TOKEN, $accessToken);
    }

    public function setClient(Client $client): self
    {
        return $this->setRelatedLinkOrEntity(self::FIELD_CLIENT, $client);
    }

    public function setRevoked(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_REVOKED);
    }

    public function setExpired(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_EXPIRED);
    }

    public function getExpiresAt(): DateTime
    {
        $value = $this->getValueObject(self::FIELD_EXPIRES_AT);

        if (!$value instanceof DateTime) {
            throw new UnexpectedValueException("No expiresAt.");
        }

        return $value;
    }

    public function setExpiresAt(DateTime $expiresAt): self
    {
        return $this->setValueObject(self::FIELD_EXPIRES_AT, $expiresAt);
    }

    public function getUser(): Link
    {
        $user = $this->getValueObject(self::FIELD_USER);

        if (!$user instanceof Link) {
            throw new UnexpectedValueException("No user.");
        }

        return $user;
    }

    public function setUser(Link|User $user): self
    {
        return $this->setRelatedLinkOrEntity(self::FIELD_USER, $user);
    }
}
