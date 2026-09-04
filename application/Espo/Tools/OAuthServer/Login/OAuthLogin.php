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

namespace Espo\Tools\OAuthServer\Login;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Authentication\Login;
use Espo\Core\Authentication\Login\Data;
use Espo\Core\Authentication\Repository\UserRepository;
use Espo\Core\Authentication\Result;
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\DateTime\Clock;
use Espo\Tools\OAuthServer\Repository\AccessTokenRepository;

/**
 * @noinspection PhpUnused
 */
class OAuthLogin implements Login
{
    private const string HEADER_AUTHORIZATION = 'Authorization';
    private const string SCHEME = 'Bearer';

    public function __construct(
        private AccessTokenRepository $repository,
        private Clock $clock,
        private UserRepository $userRepository,
    ) {}

    public function login(Data $data, Request $request): Result
    {
        $header = $request->getHeader(self::HEADER_AUTHORIZATION);

        $prefix = self::SCHEME . ' ';

        if (!$header || !str_starts_with($header, $prefix)) {
            return Result::fail(Result\FailReason::ERROR);
        }

        $opaqueToken = substr($header, strlen($prefix));

        $accessToken = $this->repository->getActiveByIdentifier($opaqueToken);

        if (!$accessToken) {
            return Result::fail(Result\FailReason::WRONG_CREDENTIALS);
        }

        $now = DateTime::fromDateTime($this->clock->now());

        if (!$accessToken->getExpiresAt()->isLessThan($now)) {
            $response = self::composeErrorResponse(
                error: 'invalid_token',
                errorDescription: 'The access token expired.',
            );

            return Result::fail(Result\FailReason::DENIED, $response);
        }

        if (!$accessToken->getClient()->isActive()) {
            $response = self::composeErrorResponse(
                error: 'access_denied',
                errorDescription: 'The client is not active.',
            );

            return Result::fail(Result\FailReason::DENIED, $response);
        }

        $userLink = $accessToken->getUser();

        $user = $this->userRepository->findOneById($userLink->getId());

        if (!$user) {
            $response = self::composeErrorResponse(
                error: 'access_denied',
                errorDescription: 'User not found.',
            );

            return Result::fail(Result\FailReason::DENIED, $response);
        }

        $user->setScopes($accessToken->getScopes());

        return Result::success($user);
    }

    private static function composeErrorResponse(
        string $error,
        string $errorDescription,

    ): Response {

        $header = "Bearer error=\"$error\", error_description=\"$errorDescription\"";

        $response = ResponseComposer::json([
            'error' => $error,
            'error_description' => $errorDescription,
        ]);

        $response->setHeader('WWW-Authenticate', $header);

        return $response;
    }
}
