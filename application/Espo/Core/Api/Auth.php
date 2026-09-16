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

namespace Espo\Core\Api;

use Espo\Core\Api\Route\ContentType;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Authentication\Login\MethodResolver;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\ServiceUnavailable;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\Result;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Json;

use Exception;

/**
 * Determines which auth method to use. Fetches a username and password from headers and server parameters.
 * Then tries to log in.
 */
class Auth
{
    private const string SCHEME_BASIC = 'Basic';

    public function __construct(
        private Log $log,
        private Authentication $authentication,
        private MethodResolver $methodResolver,
        private bool $authRequired = true,
        private bool $isEntryPoint = false,
    ) {}

    /**
     * @throws BadRequest
     * @throws Exception
     */
    public function process(Request $request, Response $response): AuthResult
    {
        $username = null;
        $password = null;

        $authenticationMethod = $this->methodResolver->resolve($request);

        if (!$authenticationMethod) {
            [$username, $password] = $this->obtainUsernamePasswordFromRequest($request);
        }

        $authenticationData = AuthenticationData::create()
            ->withUsername($username)
            ->withPassword($password)
            ->withMethod($authenticationMethod);

        $hasAuthData = $username || $authenticationMethod;

        if (!$hasAuthData && !$this->toIgnoreNonHeaderAuthorizationData($request)) {
            $password = self::obtainTokenFromCookies($request);

            if ($password) {
                $authenticationData = AuthenticationData::create()
                    ->withPassword($password)
                    ->withByTokenOnly(true);

                $hasAuthData = true;
            }
        }

        if (!$this->authRequired && !$this->isEntryPoint && $hasAuthData) {
            $authResult = $this->processAuthNotRequired(
                data: $authenticationData,
                request: $request,
                response: $response,
            );

            if ($authResult) {
                return $authResult;
            }
        }

        if (!$this->authRequired) {
            return AuthResult::createResolvedUseNoAuth();
        }

        if ($hasAuthData) {
            return $this->processWithAuthData($authenticationData, $request, $response);
        }

        $this->handleUnauthorized(
            response: $response,
            showDialog: self::toShowDialog($request),
        );

        return AuthResult::createNotResolved();
    }

    /**
     * @throws Exception
     */
    private function processAuthNotRequired(
        AuthenticationData $data,
        Request $request,
        Response $response,
    ): ?AuthResult {

        try {
            $result = $this->authentication->login($data, $request, $response);
        } catch (Exception $e) {
            $this->handleException($response, $e);

            return AuthResult::createNotResolved();
        }

        if ($result->isSuccess()) {
            return AuthResult::createResolved();
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function processWithAuthData(
        AuthenticationData $data,
        Request $request,
        Response $response,
    ): AuthResult {

        try {
            $result = $this->authentication->login($data, $request, $response);
        } catch (Exception $e) {
            $this->handleException($response, $e);

            return AuthResult::createNotResolved();
        }

        if ($result->isSuccess()) {
            return AuthResult::createResolved();
        }

        if ($result->isFail()) {
            $this->handleUnauthorized(
                response: $response,
                result: $result,
                showDialog: $this->toShowDialogOnFail($request),
            );
        }

        if ($result->isSecondStepRequired()) {
            self::handleSecondStepRequired($response, $result);
        }

        return AuthResult::createNotResolved();
    }

    /**
     * @return array{string, string}
     * @throws BadRequest
     */
    private static function decodeAuthorizationString(string $string): array
    {
        /** @var string $stringDecoded */
        $stringDecoded = base64_decode($string);

        if (!str_contains($stringDecoded, ':')) {
            throw new BadRequest("Auth: Bad authorization string provided.");
        }

        [$username, $password] = explode(':', $stringDecoded, 2);

        $username = trim($username);
        $password = trim($password);

        return [$username, $password];
    }

    private static function handleSecondStepRequired(Response $response, Result $result): void
    {
        $response->setStatus(401);
        $response->setHeader('X-Status-Reason', 'second-step-required');

        $bodyData = [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
            'view' => $result->getView(),
            'data' => $result->getData(),
        ];

        $response->writeBody(Json::encode($bodyData));
    }

    /**
     * @throws Exception
     */
    private function handleException(Response $response, Exception $e): void
    {
        if (
            $e instanceof BadRequest ||
            $e instanceof ServiceUnavailable ||
            $e instanceof Forbidden
        ) {
            $reason = $e->getMessage();

            if ($reason) {
                $response->setHeader('X-Status-Reason', $e->getMessage());
            }

            $response->setStatus($e->getCode());

            if ($e->getBody()) {
                $response->writeBody($e->getBody());
            }

            if ($e->getMessage()) {
                $this->log->notice("Auth exception.", ['exception' => $e]);
            }

            return;
        }

        throw $e;
    }

    private function handleUnauthorized(Response $response, ?Result $result = null, bool $showDialog = false): void
    {
        if ($showDialog) {
            $response->setHeader('WWW-Authenticate', 'Basic realm="api"');
        }

        if ($result && $result->getFailReason() === Result\FailReason::ERROR) {
            $response = $response->setHeader(ErrorOutput::HEADER_STATUS_REASON, 'error');
        }

        $response->setStatus(401);
    }

    /**
     * @return array{?string, ?string}
     * @throws BadRequest
     */
    private function obtainUsernamePasswordFromRequest(Request $request): array
    {
        if ($request->hasHeader(HeaderKey::AUTHORIZATION)) {
            $headerValue = $request->getHeader(HeaderKey::AUTHORIZATION) ?? '';

            return self::decodeAuthorizationString($headerValue);
        }

        // Bypass as the Authorization header may be used for OAuth.
        if (!$this->authRequired) {
            return [null, null];
        }

        if ($this->toIgnoreNonHeaderAuthorizationData($request)) {
            return [null, null];
        }

        if (
            $request->getServerParam('PHP_AUTH_USER') &&
            $request->getServerParam('PHP_AUTH_PW')
        ) {
            $username = $request->getServerParam('PHP_AUTH_USER');
            $password = $request->getServerParam('PHP_AUTH_PW');

            if (!is_string($username) || !is_string($password)) {
                return [null, null];
            }

            $username = trim($username);
            $password = trim($password);

            return [$username, $password];
        }

        $cgiAuthString = self::getCgiAuthString($request);

        if ($cgiAuthString) {
            [$username, $password] = self::decodeAuthorizationString($cgiAuthString);

            return [$username, $password];
        }

        return [null, null];
    }

    private static function obtainTokenFromCookies(Request $request): ?string
    {
        return $request->getCookieParam('auth-token');
    }

    /**
     * Header might be written by a web server's rewrite rule.
     */
    private static function getCgiAuthString(Request $request): ?string
    {
        $value = $request->getHeader('Http-Espo-Cgi-Auth') ??
            $request->getHeader('Redirect-Http-Espo-Cgi-Auth');

        if (!$value) {
            return null;
        }

        $basicPrefix = self::SCHEME_BASIC . ' ';

        if (!str_starts_with($value, $basicPrefix)) {
            return null;
        }

        return substr($value, strlen($basicPrefix));
    }

    private static function toShowDialog(Request $request): bool
    {
        return
            $request->getMethod() === Method::GET &&
            !$request->getHeader('Referer') &&
            self::isDocumentNavigate($request);
    }

    private function toShowDialogOnFail(Request $request): bool
    {
        return $this->isEntryPoint && $this->toShowDialog($request);
    }

    private static function isDocumentNavigate(Request $request): bool
    {
        return
            $request->getHeader('Sec-Fetch-Mode') === 'navigate' &&
            $request->getHeader('Sec-Fetch-Dest') === 'document';
    }

    private function toIgnoreNonHeaderAuthorizationData(Request $request): bool
    {
        return
            !$this->isEntryPoint &&
            $request->getHeader('Sec-Fetch-Mode') === 'navigate' &&
            in_array($request->getContentType(), [
                ContentType::MULTIPART_FORM_DATA,
                ContentType::APPLICATION_X_WWW_FORM_URLENCODED,
                ContentType::TEXT_PLAIN,
            ]);
    }
}
