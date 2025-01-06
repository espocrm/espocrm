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

namespace Espo\Core\Api;

use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\ServiceUnavailable;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Authentication\ConfigDataProvider;
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
    public function __construct(
        private Log $log,
        private Authentication $authentication,
        private ConfigDataProvider $configDataProvider,
        private bool $authRequired = true,
        private bool $isEntryPoint = false
    ) {}

    /**
     * @throws BadRequest
     * @throws Exception
     */
    public function process(Request $request, Response $response): AuthResult
    {
        $username = null;
        $password = null;

        $authenticationMethod = $this->obtainAuthenticationMethodFromRequest($request);

        if (!$authenticationMethod) {
            [$username, $password] = $this->obtainUsernamePasswordFromRequest($request);
        }

        $authenticationData = AuthenticationData::create()
            ->withUsername($username)
            ->withPassword($password)
            ->withMethod($authenticationMethod);

        $hasAuthData = $username || $authenticationMethod;

        if (!$hasAuthData) {
            $password = $this->obtainTokenFromCookies($request);

            if ($password) {
                $authenticationData = AuthenticationData::create()
                    ->withPassword($password)
                    ->withByTokenOnly(true);

                $hasAuthData = true;
            }
        }

        if (!$this->authRequired && !$this->isEntryPoint && $hasAuthData) {
            $authResult = $this->processAuthNotRequired(
                $authenticationData,
                $request,
                $response
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

        $showDialog =
            ($this->isEntryPoint || !$this->isXMLHttpRequest($request)) &&
            !$request->getHeader('Referer');

        $this->handleUnauthorized($response, null, $showDialog);

        return AuthResult::createNotResolved();
    }

    /**
     * @throws Exception
     */
    private function processAuthNotRequired(
        AuthenticationData $data,
        Request $request,
        Response $response
    ): ?AuthResult {

        try {
            $result = $this->authentication->login($data, $request, $response);
        } catch (Exception $e) {
            $this->handleException($response, $e);

            return AuthResult::createNotResolved();
        }

        if (!$result->isFail()) {
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
        Response $response
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
            $showDialog =
                $this->isEntryPoint &&
                !$request->getHeader('Referer');

            $this->handleUnauthorized($response, $result, $showDialog);
        }

        if ($result->isSecondStepRequired()) {
            $this->handleSecondStepRequired($response, $result);
        }

        return AuthResult::createNotResolved();
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
            throw new BadRequest("Auth: Bad authorization string provided.");
        }

        [$username, $password] = explode(':', $stringDecoded, 2);

        $username = trim($username);
        $password = trim($password);

        return [$username, $password];
    }

    private function handleSecondStepRequired(Response $response, Result $result): void
    {
        $response->setStatus(401);
        $response->setHeader('X-Status-Reason', 'second-step-required');

        $bodyData = [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
            'view' => $result->getView(),
            'token' => $result->getToken(),
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
                $this->log->notice("Auth exception: {message}", ['message' => $e->getMessage()]);
            }

            return;
        }

        throw $e;
    }

    private function handleUnauthorized(Response $response, ?Result $result, bool $showDialog): void
    {
        if ($showDialog) {
            $response->setHeader('WWW-Authenticate', 'Basic realm=""');
        }

        if ($result && $result->getFailReason() === Result\FailReason::ERROR) {
            $response = $response->setHeader('X-Status-Reason', 'error');
        }

        $response->setStatus(401);
    }

    private function isXMLHttpRequest(Request $request): bool
    {
        if (strtolower($request->getHeader('X-Requested-With') ?? '') == 'xmlhttprequest') {
            return true;
        }

        return false;
    }

    private function obtainAuthenticationMethodFromRequest(Request $request): ?string
    {
        if ($request->hasHeader(HeaderKey::AUTHORIZATION)) {
            return null;
        }

        $paramsList = array_values(array_filter(
            $this->configDataProvider->getLoginMetadataParamsList(),
            function ($params) use ($request): bool {
                $header = $params->getCredentialsHeader();

                if (!$header || !$params->isApi()) {
                    return false;
                }

                return $request->hasHeader($header);
            }
        ));

        if (count($paramsList)) {
            return $paramsList[0]->getMethod();
        }

        return null;
    }

    /**
     * @return array{?string, ?string}
     * @throws BadRequest
     */
    private function obtainUsernamePasswordFromRequest(Request $request): array
    {
        if ($request->hasHeader(HeaderKey::AUTHORIZATION)) {
            $headerValue = $request->getHeader(HeaderKey::AUTHORIZATION) ?? '';

            return $this->decodeAuthorizationString($headerValue);
        }

        if (
            $request->getServerParam('PHP_AUTH_USER') &&
            $request->getServerParam('PHP_AUTH_PW')
        ) {
            $username = $request->getServerParam('PHP_AUTH_USER');
            $password = $request->getServerParam('PHP_AUTH_PW');

            if (is_string($username)) {
                $username = trim($username);
            }

            if (is_string($password)) {
                $password = trim($password);
            }

            return [$username, $password];
        }

        $cgiAuthString = $request->getHeader('Http-Espo-Cgi-Auth') ??
            $request->getHeader('Redirect-Http-Espo-Cgi-Auth');

        if ($cgiAuthString) {
            [$username, $password] = $this->decodeAuthorizationString(substr($cgiAuthString, 6));

            return [$username, $password];
        }

        return [null, null];
    }

    private function obtainTokenFromCookies(Request $request): ?string
    {
        return $request->getCookieParam('auth-token');
    }
}
