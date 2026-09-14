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

namespace Espo\Tools\OAuthServer\EntryPoints;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\Exceptions\BadRequest;
use Espo\Tools\OAuthServer\AuthorizationService;

/**
 * @noinspection PhpUnused
 */
class AuthorizeComplete implements EntryPoint
{
    public function __construct(
        private AuthorizationService $service,
    ) {}

    public function run(Request $request, Response $response): void
    {
        $body = $request->getParsedBody();

        $clientId = $body->clientId ?? throw new BadRequest("No 'clientId'.");
        $approved = ($body->approved ?? null) === 'true';

        if (!is_string($clientId)) {
            throw new BadRequest();
        }

        $scopes = $this->fetchScopes($request);

        $psr7Response = $this->service->authorizeComplete(
            clientId: $clientId,
            response: $response->toPsr7(),
            approved: $approved,
            scopes: $scopes,
        );

        $response->applyPsr7($psr7Response);
    }

    /**
     * @return non-empty-string[]
     * @throws BadRequest
     */
    private function fetchScopes(Request $request): array
    {
        $scopesRaw = $request->getParsedBody()->scopes ?? throw new BadRequest("No 'scopes'.");

        $scopes = explode(' ', $scopesRaw);

        foreach ($scopes as $scope) {
            if (!$scope) {
                throw new BadRequest("Bad 'scopes' item values.");
            }

            if ($scope !== trim($scope)) {
                throw new BadRequest("Bad scope.");
            }
        }

        /** @var non-empty-string[] */
        return $scopes;
    }
}
