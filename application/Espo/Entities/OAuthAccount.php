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

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Espo\Entities;

use Espo\Core\Field\DateTime;
use Espo\Core\ORM\Entity;
use SensitiveParameter;
use ValueError;

class OAuthAccount extends Entity
{
    public const ENTITY_TYPE = 'OAuthAccount';

    public function getProvider(): OAuthProvider
    {
        $provider = $this->relations->getOne('provider');

        if (!$provider instanceof OAuthProvider) {
            throw new ValueError("No provider.");
        }

        return $provider;
    }

    public function getAccessToken(): ?string
    {
        return $this->get('accessToken');
    }

    public function getRefreshToken(): ?string
    {
        return $this->get('refreshToken');
    }

    public function getExpiresAt(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('expiresAt');
    }

    public function setAccessToken(#[SensitiveParameter] ?string $accessToken): self
    {
        return $this->set('accessToken', $accessToken);
    }

    public function setRefreshToken(#[SensitiveParameter] ?string $refreshToken): self
    {
        return $this->set('refreshToken', $refreshToken);
    }

    public function setExpiresAt(?DateTime $expiresAt): self
    {
        return $this->setValueObject('expiresAt', $expiresAt);
    }
}
