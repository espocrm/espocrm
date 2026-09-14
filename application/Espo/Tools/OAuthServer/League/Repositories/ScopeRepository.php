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

namespace Espo\Tools\OAuthServer\League\Repositories;

use Espo\Tools\OAuthServer\League\Entities\ScopeEntity;
use Espo\Tools\OAuthServer\Scope\UserAvailableScopesFilter;
use Espo\Tools\OAuthServer\Scope\ScopeValidator;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    public function __construct(
        private ScopeValidator $scopeValidator,
        private UserAvailableScopesFilter $userAvailableScopesProvider,
    ) {}

    /**
     * @param non-empty-string $identifier
     */
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntity
    {
        if (!$this->scopeValidator->validate($identifier)) {
            return null;
        }

        return new ScopeEntity($identifier);
    }

    /**
     * @inheritDoc
     */
    public function finalizeScopes(
        array $scopes,
        ?string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {

        if (!$userIdentifier) {
            return $scopes;
        }

        $ids = array_map(fn ($it) => $it->getIdentifier(), $scopes);

        $availableIds = $this->userAvailableScopesProvider->filter($userIdentifier, $ids);

        $scopes = array_filter($scopes, function ($scope) use ($availableIds) {
            return in_array($scope->getIdentifier(), $availableIds);
        });

        return array_values($scopes);
    }
}
