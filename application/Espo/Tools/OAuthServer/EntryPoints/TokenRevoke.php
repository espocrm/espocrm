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
use Espo\Core\Exceptions\Unauthorized;
use Espo\Tools\OAuthServer\Revoke\Data;
use Espo\Tools\OAuthServer\Revoke\TokenRevokeService;
use Espo\Tools\OAuthServer\Utils\ErrorResponseComposer;

/**
 * @noinspection PhpUnused
 */
class TokenRevoke implements EntryPoint
{
    private const string HEADER_AUTHORIZATION = 'Authorization';

    public function __construct(
        private TokenRevokeService $service,
    ) {}

    public function run(Request $request, Response $response): void
    {
        $token = $request->getParsedBody()->token ?? throw new BadRequest("No 'token'.");
        $tokenTypeHint = $request->getParsedBody()->token_type_hint ?? null;

        [$clientId, $clientSecret] = $this->obtainAuthorizationFromHeader($request);

        $clientId ??= $request->getParsedBody()->client_id ?? throw new BadRequest("No client ID.");
        $clientSecret ??= $request->getParsedBody()->client_secret ?? null;

        if (!is_string($clientId)) {
            throw new BadRequest("Bad 'client_id' value");
        }

        if ($clientSecret !== null && !is_string($clientSecret)) {
            throw new BadRequest("Bad 'client_secret' value");
        }

        if (!is_string($token)) {
            throw new BadRequest("Bad 'token' value.");
        }

        if ($tokenTypeHint !== null && !is_string($tokenTypeHint)) {
            throw new BadRequest("Bad 'token_type_hint' value.");
        }

        if (!$this->isTokenTypeValid($tokenTypeHint)) {
            $response->applyPsr7(
                ErrorResponseComposer::composeErrorResponse(
                    error: 'unsupported_token_type',
                    errorDescription: 'Token type is not supported.',
                    statusCode: 400,
                )->toPsr7()
            );

            return;
        }

        $data = new Data(
            clientId: $clientId,
            clientSecret: $clientSecret,
            token: $token,
            tokenTypeHint: $tokenTypeHint,
        );

        try {
            $this->service->revoke($data);
        } catch (Unauthorized) {
            $response->applyPsr7(
                ErrorResponseComposer::composeErrorResponse(
                    error: 'invalid_client',
                    errorDescription: 'Invalid client credentials.',
                    statusCode: 401,
                )->toPsr7()
            );
        }
    }

    private function isTokenTypeValid(?string $tokenTypeHint): bool
    {
        return
            $tokenTypeHint === null ||
            in_array($tokenTypeHint, [
                TokenRevokeService::TYPE_ACCESS_TOKEN,
                TokenRevokeService::TYPE_REFRESH_TOKEN,
            ]);
    }

    /**
     * @return array{?string, ?string}
     * @throws BadRequest
     */
    private function obtainAuthorizationFromHeader(Request $request): array
    {
        $headerValue = $request->getHeader(self::HEADER_AUTHORIZATION);

        if (!$headerValue) {
            return [null, null];
        }

        $prefix = 'Basic ';

        if (!str_starts_with($headerValue, $prefix)) {
            return [null, null];
        }

        $string = substr($headerValue, strlen($prefix));

        [$clientId, $clientSecret] = self::decodeAuthorizationString($string);

        if (!$clientId) {
            $clientId = null;
        }

        if (!$clientSecret) {
            $clientSecret = null;
        }

        return [$clientId, $clientSecret];
    }

    /**
     * @return array{string, string}
     * @throws BadRequest
     */
    private function decodeAuthorizationString(string $string): array
    {
        /** @var string $stringDecoded */
        $stringDecoded = base64_decode($string);

        if (!str_contains($stringDecoded, ':')) {
            throw new BadRequest("Bad authorization string.");
        }

        [$username, $password] = explode(':', $stringDecoded, 2);

        $username = trim($username);
        $password = trim($password);

        return [$username, $password];
    }
}
