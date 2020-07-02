<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Utils\Auth as AuthUtil;

use Psr\Http\Message\{
    ServerRequestInterface as Request,
    ResponseInterface as Response,
};

class Auth
{
    protected $auth;

    protected $authRequired = null;

    protected $showDialog = false;

    private $isResolved = false;

    private $isResolvedUseNoAuth = false;

    public function __construct(AuthUtil $auth, bool $authRequired = null, bool $isEntryPoint = false, bool $showDialog = false)
    {
        $this->auth = $auth;
        $this->authRequired = $authRequired;
        $this->isEntryPoint = $isEntryPoint;
        $this->showDialog = $showDialog;
    }

    protected function resolve()
    {
        $this->isResolved = true;
    }

    protected function resolveUseNoAuth()
    {
        $this->isResolvedUseNoAuth = true;
    }

    public function isResolved() : bool
    {
        return $this->isResolved;
    }

    public function isResolvedUseNoAuth() : bool
    {
        return $this->isResolvedUseNoAuth;
    }

    public function process(Request $request, Response $response) : Response
    {
        $uri = $request->getUri();
        $httpMethod = $request->getMethod();

        $username = null;
        $password = null;

        if ($request->hasHeader('PHP_AUTH_USER')) {
            $username = $request->getHeaderLine('PHP_AUTH_USER');
        }
        if ($request->hasHeader('PHP_AUTH_PW')) {
            $password = $request->getHeaderLine('PHP_AUTH_PW');
        }

        $authenticationMethod = null;

        if ($request->hasHeader('Http-Espo-Authorization')) {
            $espoAuthorizationHeader = $request->getHeaderLine('Http-Espo-Authorization');
            list($username, $password) = explode(':', base64_decode($espoAuthorizationHeader), 2);
        } else {
            if ($request->hasHeader('X-Hmac-Authorization')) {
                $hmacAuthorizationHeader = $request->getHeaderLine('X-Hmac-Authorization');
                $authenticationMethod = 'Hmac';
                list($username, $password) = explode(':', base64_decode($hmacAuthorizationHeader), 2);
            } else {
                $apiKeyHeader = $request->getHeaderLine('X-Api-Key');
                if ($apiKeyHeader) {
                    $authenticationMethod = 'ApiKey';
                    $username = $apiKeyHeader;
                    $password = null;
                }
            }
        }

        if (!isset($username)) {
            $cookie = $request->getCookieParams();
            if (!empty($cookie['auth-username']) && !empty($cookie['auth-token'])) {
                $username = $cookie['auth-username'];
                $password = $cookie['auth-token'];
            }
        }

        if (!isset($username) && !isset($password)) {
            $espoCgiAuth = $request->getHeaderLine('Http-Espo-Cgi-Auth');
            if (!$espoCgiAuth) {
                $espoCgiAuth = $request->getHeaderLine('Redirect-Http-Espo-Cgi-Auth');
            } else {
                list($username, $password) = explode(':' , base64_decode(substr($espoCgiAuth, 6)));
            }
        }

        if (!$this->authRequired) {
            if (!$this->isEntryPoint) {
                if ($username && $password) {
                    try {
                        $isAuthenticated = $this->auth->login($username, $password);
                    } catch (\Exception $e) {
                        return $this->processException($response, $e);
                    }
                    if ($isAuthenticated) {
                        $this->resolve();
                        return $response;
                    }
                }
            }
            $this->resolveUseNoAuth();
            return $response;
        }

        if ($username) {
            try {
                $authResult = $this->auth->login($username, $password, $authenticationMethod);
            } catch (\Exception $e) {
                return $this->processException($response, $e);
            }

            if ($authResult) {
                $response = $this->handleAuthResult($response, $authResult);
            } else {
                $response = $this->processUnauthorized($response);
            }
        } else {
            if (!$this->isXMLHttpRequest($request)) {
                $this->showDialog = true;
            }
            $response = $this->processUnauthorized($response);
        }

        return $response;
    }

    protected function handleAuthResult(Response $response, array $authResult) : Response
    {
        $status = $authResult['status'];

        if ($status === AuthUtil::STATUS_SUCCESS) {
            $this->resolve();
            return $response;
        }

        if ($status === AuthUtil::STATUS_SECOND_STEP_REQUIRED) {
            $response = $response->withStatus(401);
            $response = $response->withHeader('X-Status-Reason', 'second-step-required');

            $bodyData = [
                'status' => $status,
                'message' => $authResult['message'] ?? null,
                'view' => $authResult['view'] ?? null,
                'token' => $authResult['token'] ?? null,
            ];
            $response->getBody()->write(json_encode($bodyData));
        }

        return $response;
    }

    protected function processException(Response $response, \Exception $e) : Response
    {
        $reason = $e->getMessage();

        if ($reason) {
            $response = $response->withHeader('X-Status-Reason', $e->getMessage());
        }

        return $response->withStatus($e->getCode(), $reason);
    }

    protected function processUnauthorized(Response $response) : Response
    {
        if ($this->showDialog) {
            $response = $response->withHeader('WWW-Authenticate', 'Basic realm=""');
        }

        return $response->withStatus(401);
    }

    protected function isXMLHttpRequest(Request $request)
    {
        if (strtolower($request->getHeaderLine('Http-X-Requested-With')) == 'xmlhttprequest') {
            return true;
        }

        return false;
    }
}
