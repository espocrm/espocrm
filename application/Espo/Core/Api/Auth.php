<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Api;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\ServiceUnavailable;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\Result;
use Espo\Core\Utils\Log;

use Exception;

/**
 * Determines which auth method to use. Fetches a username and password from headers and server parameters.
 * Then tries to log in.
 */
class Auth
{
    private $log;

    private $authentication;

    private $authRequired;

    private $isEntryPoint;

    public function __construct(
        Log $log,
        Authentication $authentication,
        bool $authRequired = true,
        bool $isEntryPoint = false
    ) {
        $this->log = $log;
        $this->authentication = $authentication;
        $this->authRequired = $authRequired;
        $this->isEntryPoint = $isEntryPoint;
    }

    public function process(Request $request, Response $response): AuthResult
    {
        list ($username, $password, $authenticationMethod) = $this->obtainAuthenticationFromRequest($request);

        $authenticationData = AuthenticationData::create()
            ->withUsername($username)
            ->withPassword($password)
            ->withMethod($authenticationMethod);

        $hasAuthData = (bool) ($username || $authenticationMethod);

        if (!$this->authRequired && !$this->isEntryPoint) {
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

        return $this->processWithAuthData($authenticationData, $request, $response);
    }

    private function processAuthNotRequired(
        AuthenticationData $data,
        Request $request,
        Response $response
    ): ?AuthResult {

        try {
            $result = $this->authentication->login($data, $request, $response);
        }
        catch (Exception $e) {
            $this->handleException($response, $e);

            return AuthResult::createNotResolved();
        }

        if (!$result->isFail()) {
            return AuthResult::createResolved();
        }

        return null;
    }

    private function processWithAuthData(
        AuthenticationData $data,
        Request $request,
        Response $response
    ): AuthResult {

        $showDialog = $this->isEntryPoint || !$this->isXMLHttpRequest($request);

        try {
            $result = $this->authentication->login($data, $request, $response);
        }
        catch (Exception $e) {
            $this->handleException($response, $e);

            return AuthResult::createNotResolved();
        }

        if ($result->isSuccess()) {
            return AuthResult::createResolved();
        }

        if ($result->isFail()) {
            $this->handleUnauthorized($response, $showDialog);
        }

        if ($result->isSecondStepRequired()) {
            $this->handleSecondStepRequired($response, $result);
        }

        return AuthResult::createNotResolved();
    }

    protected function decodeAuthorizationString(string $string): array
    {
        $stringDecoded = base64_decode($string);

        if (strpos($stringDecoded, ':') === false) {
            throw new BadRequest("Auth: Bad authorization string provided.");
        }

        $auth = explode(':', $stringDecoded, 2);
        if (strpos($auth[1], ':') === false) {
            $auth[2] = null;
            return $auth;
        }
        return array_merge([$auth[0]], explode(':', $auth[1]));
    }

    protected function handleSecondStepRequired(Response $response, Result $result): void
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

        $response->writeBody(json_encode($bodyData));
    }

    protected function handleException(Response $response, Exception $e): void
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

            $this->log->notice("Auth: " . $e->getMessage());

            return;
        }

        $response->setStatus(500, $e->getMessage());

        $this->log->error("Auth: " . $e->getMessage());
    }

    protected function handleUnauthorized(Response $response, bool $showDialog): void
    {
        if ($showDialog) {
            $response->setHeader('WWW-Authenticate', 'Basic realm=""');
        }

        $response->setStatus(401);
    }

    protected function isXMLHttpRequest(Request $request): bool
    {
        if (strtolower($request->getHeader('X-Requested-With') ?? '') == 'xmlhttprequest') {
            return true;
        }

        return false;
    }

    protected function obtainAuthenticationFromRequest(Request $request): array
    {
        if ($request->hasHeader('Espo-Authorization')) {
            return $this->decodeAuthorizationString(
                $request->getHeader('Espo-Authorization')
            );
        }

        if ($request->hasHeader('X-Hmac-Authorization')) {
            return [null, null, 'Hmac'];
        }

        if ($request->hasHeader('X-Api-Key')) {
            return [null, null, 'ApiKey'];
        }

        if ($request->hasHeader('X-Auth-Method')) {
            return [null, null, $request->getHeader('X-Auth-Method')];
        }

        if (
            $request->getServerParam('PHP_AUTH_USER') &&
            $request->getServerParam('PHP_AUTH_PW')
        ) {
            $username = $request->getServerParam('PHP_AUTH_USER');
            $password = $request->getServerParam('PHP_AUTH_PW');

            return [$username, $password, null];
        }

        if (
            $request->getCookieParam('auth-username') &&
            $request->getCookieParam('auth-token')
        ) {

            $username = $request->getCookieParam('auth-username');
            $password = $request->getCookieParam('auth-token');

            return [$username, $password, null];
        }

        $cgiAuthString = $request->getHeader('Http-Espo-Cgi-Auth') ??
            $request->getHeader('Redirect-Http-Espo-Cgi-Auth');

        if ($cgiAuthString) {
            return $this->decodeAuthorizationString(substr($cgiAuthString, 6));
        }

        return [null, null, null];
    }
}
