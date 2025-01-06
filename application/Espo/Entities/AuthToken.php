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

use Espo\Core\Authentication\AuthToken\AuthToken as AuthTokenInterface;
use Espo\Core\Field\DateTime;
use Espo\Core\ORM\Entity as BaseEntity;

class AuthToken extends BaseEntity implements AuthTokenInterface
{
    public const ENTITY_TYPE = 'AuthToken';

    public function getToken(): string
    {
        return $this->get('token');
    }

    public function getUserId(): string
    {
        return $this->get('userId');
    }

    public function getPortalId(): ?string
    {
        return $this->get('portalId');
    }

    public function getSecret(): ?string
    {
        return $this->get('secret');
    }

    public function isActive(): bool
    {
        return $this->get('isActive');
    }

    public function getHash(): ?string
    {
        return $this->get('hash');
    }

    public function setIsActive(bool $isActive): self
    {
        $this->set('isActive', $isActive);

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->set('userId', $userId);

        return $this;
    }

    public function setPortalId(?string $portalId): self
    {
        $this->set('portalId', $portalId);

        return $this;
    }

    public function setHash(?string $hash): self
    {
        $this->set('hash', $hash);

        return $this;
    }

    public function setToken(string $token): self
    {
        $this->set('token', $token);

        return $this;
    }

    public function setSecret(string $secret): self
    {
        $this->set('secret', $secret);

        return $this;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->set('ipAddress', $ipAddress);

        return $this;
    }

    public function setLastAccessNow(): self
    {
        $this->set('lastAccess', DateTime::createNow()->toString());

        return $this;
    }
}
