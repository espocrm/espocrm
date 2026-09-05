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

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Tools\OAuthServer\ClientType;
use UnexpectedValueException;

class Client extends Entity
{
    public const string ENTITY_TYPE = 'OAuthClient';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_IDENTIFIER = 'identifier';
    public const string FIELD_CLIENT_TYPE = 'clientType';
    public const string FIELD_SCOPES = 'scopes';
    public const string FIELD_REDIRECT_URIS = 'redirectUris';

    public const string LINK_SECRETS = 'secrets';

    public const string STATUS_ACTIVE = 'Active';
    public const string STATUS_INACTIVE = 'Inactive';

    public function getName(): ?string
    {
        return $this->get(Field::NAME);
    }

    public function setName(string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    public function getClientType(): ClientType
    {
        $raw = $this->get(self::FIELD_CLIENT_TYPE) ?? throw new UnexpectedValueException("No client type.");

        return ClientType::tryFrom($raw) ?? throw new UnexpectedValueException("Bad client type.");
    }

    public function setClientType(ClientType $clientType): self
    {
        return $this->set(self::FIELD_CLIENT_TYPE, $clientType->value);
    }

    /**
     * @return ?non-empty-string
     */
    public function getIdentifier(): ?string
    {
        /** @var ?non-empty-string */
        return $this->get(self::FIELD_IDENTIFIER);
    }

    public function setIdentifier(?string $clientId): self
    {
        return $this->set(self::FIELD_IDENTIFIER, $clientId);
    }

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function setActive(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_ACTIVE);
    }

    public function setInactive(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_INACTIVE);
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->get(self::FIELD_SCOPES) ?? [];
    }

    /**
     * @param string[] $scopes
     */
    public function setScopes(array $scopes): self
    {
        return $this->set(self::FIELD_SCOPES, $scopes);
    }

    /**
     * @return string[]
     */
    public function getRedirectUris(): array
    {
        return $this->get(self::FIELD_REDIRECT_URIS) ?? [];
    }

    /**
     * @param string[] $redirectUris
     */
    public function setRedirectUris(array $redirectUris): self
    {
        return $this->set(self::FIELD_REDIRECT_URIS, $redirectUris);
    }
}
