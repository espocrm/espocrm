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

use Exception;

use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Utils\Auth as AuthUtil;

use Espo\Core\{
    Api\Request,
    Api\Response,
};

class Auth
{
    protected $auth;

    protected $authRequired;

    private $isResolved = false;

    private $isResolvedUseNoAuth = false;

    public function __construct(AuthUtil $auth, bool $authRequired = true, bool $isEntryPoint = false)
    {
        $this->auth = $auth;
        $this->authRequired = $authRequired;
        $this->isEntryPoint = $isEntryPoint;
    }

    protected function resolve()
    {
        $this->isResolved = true;
    }

    protected function resolveUseNoAuth()
    {
        $this->resolve();
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

    public function process(Request $request, Response $response)
    {
        $httpMethod = $request->getMethod();

        $username = null;
        $password = null;

        $showDialog = $this->isEntryPoint;

        $authenticationMethod = null;

        if ($request->hasHeader('Espo-Authorization')) {
            list($username, $password) = $this->decodeAuthorizationString($request->getHeader('Espo-Authorization'));
        } else if ($request->hasHeader('X-Hmac-Authorization')) {
            $authenticationMethod = 'Hmac';
            $username = $this->decodeAuthorizationString($request->getHeader('X-Hmac-Authorization'))[0];
        } else if ($request->hasHeader('X-Api-Key')) {
            $authenticationMethod = 'ApiKey';
            $username = $request->getHeader('X-Api-Key');
        }

        if (!$authenticationMethod) {
            if (!$username) {
                if ($request->getServerParam('PHP_AUTH_USER') && $request->getServerParam('PHP_AUTH_PW')) {
                    $username = $request->getServerParam('PHP_AUTH_USER');
                    $password = $request->getServerParam('PHP_AUTH_PW');
                }
            }

            if (!$username) {
                if ($request->getCookieParam('auth-username') && $request->getCookieParam('auth-token')) {
                    $username = $request->getCookieParam('auth-username');
                    $password = $request->getCookieParam('auth-token');
                }
            }

            if (!$username) {
                $espoCgiAuth = $request->getHeader('Http-Espo-Cgi-Auth') ?? $request->getHeader('Redirect-Http-Espo-Cgi-Auth');
                if ($cgiAuthString) {
                    list($username, $password) = $this->decodeAuthorizationString(substr($cgiAuthString, 6));
                }
            }
        }

        if (!$this->authRequired) {
            if (!$this->isEntryPoint) {
                if ($username && $password) {
                    try {
                        $isAuthenticated = $this->auth->login($username, $password, $request);
                    } catch (Exception $e) {
                        $this->processException($response, $e);
                        return;
                    }
                    if ($isAuthenticated) {
                        $this->resolve();
                        return;
                    }
                }
            }
            $this->resolveUseNoAuth();
            return;
        }

        if ($username) {
            try {
                $authResult = $this->auth->login($username, $password, $request, $authenticationMethod);
            } catch (Exception $e) {
                $this->processException($response, $e);
            }

            if ($authResult) {
                $this->handleAuthResult($response, $authResult);
            } else {
                $this->processUnauthorized($response, $showDialog);
            }
        } else {
            if (!$this->isXMLHttpRequest($request)) {
                $showDialog = true;
            }
            $this->processUnauthorized($response, $showDialog);
        }
    }

    protected function decodeAuthorizationString(string $string) : array
    {
        $string = base64_decode($string);

        if (strpos($string, ':') === false) {
            throw new BadRequest("Auth: Bad authorization string provided.");
        }

        return explode(':', $string, 2);
    }

    protected function handleAuthResult(Response $response, array $authResult)
    {
        $status = $authResult['status'];

        if ($status === AuthUtil::STATUS_SUCCESS) {
            $this->resolve();
            return;
        }

        if ($status === AuthUtil::STATUS_SECOND_STEP_REQUIRED) {
            $response->setStatus(401);
            $response->setHeader('X-Status-Reason', 'second-step-required');

            $bodyData = [
                'status' => $status,
                'message' => $authResult['message'] ?? null,
                'view' => $authResult['view'] ?? null,
                'token' => $authResult['token'] ?? null,
            ];
            $response->writeBody(json_encode($bodyData));
        }
    }

    protected function processException(Response $response, Exception $e)
    {
        $reason = $e->getMessage();

        if ($reason) {
            $response->setHeader('X-Status-Reason', $e->getMessage());
        }

        $response->setStatus($e->getCode(), $reason);
    }

    protected function processUnauthorized(Response $response, bool $showDialog)
    {
        if ($showDialog) {
            $response->setHeader('WWW-Authenticate', 'Basic realm=""');
        }

        $response->setStatus(401);
    }

    protected function isXMLHttpRequest(Request $request)
    {
        if (strtolower($request->getHeader('X-Requested-With') ?? '') == 'xmlhttprequest') {
            return true;
        }

        return false;
    }
}
