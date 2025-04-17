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

use Espo\Core\ORM\Entity;
use stdClass;
use ValueError;

class OAuthProvider extends Entity
{
    public const ENTITY_TYPE = 'OAuthProvider';

    public function isActive(): bool
    {
        return $this->get('isActive');
    }

    public function getClientId(): string
    {
        $value = $this->get('clientId');

        if (!is_string($value)) {
            throw new ValueError("No client ID.");
        }

        return $value;
    }

    public function getClientSecret(): string
    {
        $value = $this->get('clientSecret');

        if (!is_string($value)) {
            throw new ValueError("No client secret.");
        }

        return $value;
    }

    public function getTokenEndpoint(): string
    {
        $value = $this->get('tokenEndpoint');

        if (!is_string($value)) {
            throw new ValueError("No token endpoint.");
        }

        return $value;
    }

    public function getAuthorizationEndpoint(): string
    {
        $value = $this->get('authorizationEndpoint');

        if (!is_string($value)) {
            throw new ValueError("No authorization endpoint.");
        }

        return $value;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->get('scopes') ?? [];
    }

    public function getScopeSeparator(): ?string
    {
        return $this->get('scopeSeparator');
    }

    public function getAuthorizationPrompt(): string
    {
        return $this->get('authorizationPrompt');
    }

    public function getAuthorizationParams(): ?stdClass
    {
        return $this->get('authorizationParams') ?? null;
    }
}
