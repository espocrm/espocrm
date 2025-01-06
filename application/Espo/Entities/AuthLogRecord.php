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

use Espo\Core\ORM\Entity;

class AuthLogRecord extends Entity
{
    public const ENTITY_TYPE = 'AuthLogRecord';

    public const DENIAL_REASON_CREDENTIALS = 'CREDENTIALS';
    public const DENIAL_REASON_WRONG_CODE = 'WRONG_CODE';
    public const DENIAL_REASON_INACTIVE_USER = 'INACTIVE_USER';
    public const DENIAL_REASON_IS_PORTAL_USER = 'IS_PORTAL_USER';
    public const DENIAL_REASON_IS_NOT_PORTAL_USER = 'IS_NOT_PORTAL_USER';
    public const DENIAL_REASON_USER_IS_NOT_IN_PORTAL = 'USER_IS_NOT_IN_PORTAL';
    public const DENIAL_REASON_IS_SYSTEM_USER = 'IS_SYSTEM_USER';
    public const DENIAL_REASON_FORBIDDEN = 'FORBIDDEN';

    public function setUsername(?string $username): self
    {
        $this->set('username', $username);

        return $this;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->set('ipAddress', $ipAddress);

        return $this;
    }

    public function setRequestMethod(string $requestMethod): self
    {
        $this->set('requestMethod', $requestMethod);

        return $this;
    }

    public function setRequestUrl(string $requestUrl): self
    {
        $this->set('requestUrl', $requestUrl);

        return $this;
    }

    public function setAuthenticationMethod(?string $authenticationMethod): self
    {
        $this->set('authenticationMethod', $authenticationMethod);

        return $this;
    }

    public function setRequestTime(?float $requestTime): self
    {
        $this->set('requestTime', $requestTime);

        return $this;
    }

    public function setUserId(?string $userId): self
    {
        $this->set('userId', $userId);

        return $this;
    }

    public function setPortalId(?string $portalId): self
    {
        $this->set('portalId', $portalId);

        return $this;
    }

    public function setAuthTokenId(?string $authTokenId): self
    {
        $this->set('authTokenId', $authTokenId);

        return $this;
    }

    public function setIsDenied(bool $isDenied = true): self
    {
        $this->set('isDenied', $isDenied);

        return $this;
    }

    public function setDenialReason(?string $denialReason): self
    {
        $this->set('denialReason', $denialReason);

        return $this;
    }
}
