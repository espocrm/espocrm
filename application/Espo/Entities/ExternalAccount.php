<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Entities;

use Espo\Core\Field\DateTime;
use RuntimeException;

class ExternalAccount extends Integration
{
    public const ENTITY_TYPE = 'ExternalAccount';

    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled');
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->set('enabled', $isEnabled);

        return $this;
    }

    public function unsetData(): self
    {
        $this->set(['data' => null]);

        return $this;
    }

    public function setIsLocked(bool $isLocked): self
    {
        $this->set('isLocked', $isLocked);

        return $this;
    }

    public function isLocked(): bool
    {
        return (bool) $this->get('isLocked');
    }

    public function getRefreshTokenAttempts(): int
    {
        return (int) ($this->get('refreshTokenAttempts') ?? 0);
    }

    public function getAccessToken(): ?string
    {
        return $this->get('accessToken');
    }

    public function getRefreshToken(): ?string
    {
        return $this->get('refreshToken');
    }

    public function getTokenType(): ?string
    {
        return $this->get('tokenType');
    }

    public function getExpiresAt(): ?DateTime
    {
        $raw = $this->get('expiresAt');

        if (!$raw) {
            return null;
        }

        try {
            return DateTime::fromString($raw);
        } catch (RuntimeException) {
            return null;
        }
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->set('accessToken', $accessToken);

        return $this;
    }

    public function setTokenType(?string $tokenType): self
    {
        $this->set('tokenType', $tokenType);

        return $this;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->set('refreshToken', $refreshToken);

        return $this;
    }


    public function setExpiresAt(?string $expiresAt): self
    {
        $this->set('expiresAt', $expiresAt);

        return $this;
    }

    public function setRefreshTokenAttempts(?int $refreshTokenAttempts): self
    {
        $this->set('refreshTokenAttempts', $refreshTokenAttempts);

        return $this;
    }
}
