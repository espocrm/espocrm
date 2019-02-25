<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Api;

use \Espo\Core\Utils\Api\Slim;

class Auth extends \Slim\Middleware
{
    protected $auth;

    protected $authRequired = null;

    protected $showDialog = false;

    public function __construct(\Espo\Core\Utils\Auth $auth, $authRequired = null, $showDialog = false)
    {
        $this->auth = $auth;
        $this->authRequired = $authRequired;
        $this->showDialog = $showDialog;
    }

    function call()
    {
        $request = $this->app->request();

        $uri = $request->getResourceUri();
        $httpMethod = $request->getMethod();

        $username = $request->headers('PHP_AUTH_USER');
        $password = $request->headers('PHP_AUTH_PW');

        $authenticationMethod = null;

        $espoAuthorizationHeader = $request->headers('Http-Espo-Authorization');
        if (isset($espoAuthorizationHeader)) {
            list($username, $password) = explode(':', base64_decode($espoAuthorizationHeader), 2);
        } else {
            $hmacAuthorizationHeader = $request->headers('X-Hmac-Authorization');
            if ($hmacAuthorizationHeader) {
                $authenticationMethod = 'Hmac';
                list($username, $password) = explode(':', base64_decode($hmacAuthorizationHeader), 2);
            } else {
                $apiKeyHeader = $request->headers('X-Api-Key');
                if ($apiKeyHeader) {
                    $authenticationMethod = 'ApiKey';
                    $username = $apiKeyHeader;
                    $password = null;
                }
            }
        }

        if (!isset($username)) {
            if (!empty($_COOKIE['auth-username']) && !empty($_COOKIE['auth-token'])) {
                $username = $_COOKIE['auth-username'];
                $password = $_COOKIE['auth-token'];
            }
        }

        if (!isset($username) && !isset($password)) {
            $espoCgiAuth = $request->headers('Http-Espo-Cgi-Auth');
            if (empty($espoCgiAuth)) {
                $espoCgiAuth = $request->headers('Redirect-Http-Espo-Cgi-Auth');
            }
            if (!empty($espoCgiAuth)) {
                list($username, $password) = explode(':' , base64_decode(substr($espoCgiAuth, 6)));
            }
        }

        if (is_null($this->authRequired)) {
            $routes = $this->app->router()->getMatchedRoutes($httpMethod, $uri);

            if (!empty($routes[0])) {
                $routeConditions = $routes[0]->getConditions();
                if (isset($routeConditions['auth']) && $routeConditions['auth'] === false) {

                    if ($username && $password) {
                        try {
                            $isAuthenticated = $this->auth->login($username, $password);
                        } catch (\Exception $e) {
                            $this->processException($e);
                            return;
                        }
                        if ($isAuthenticated) {
                            $this->next->call();
                            return;
                        }
                    }

                    $this->auth->useNoAuth();
                    $this->next->call();
                    return;
                }
            }
        } else {
            if (!$this->authRequired) {
                $this->auth->useNoAuth();
                $this->next->call();
                return;
            }
        }

        if ($username) {
            try {
                $isAuthenticated = $this->auth->login($username, $password, $authenticationMethod);
            } catch (\Exception $e) {
                $this->processException($e);
                return;
            }

            if ($isAuthenticated) {
                $this->next->call();
            } else {
                $this->processUnauthorized();
            }
        } else {
            if (!$this->isXMLHttpRequest()) {
                $this->showDialog = true;
            }
            $this->processUnauthorized();
        }
    }

    protected function processException(\Exception $e)
    {
        $response = $this->app->response();

        if ($e->getMessage()) {
            $response->headers->set('X-Status-Reason', $e->getMessage());
        }
        $response->setStatus($e->getCode());
    }

    protected function processUnauthorized()
    {
        $response = $this->app->response();

        if ($this->showDialog) {
            $response->headers->set('WWW-Authenticate', 'Basic realm=""');
        }
        $response->setStatus(401);
    }

    protected function isXMLHttpRequest()
    {
        $request = $this->app->request();

        $httpXRequestedWith = $request->headers('Http-X-Requested-With');
        if ($httpXRequestedWith && strtolower($httpXRequestedWith) == 'xmlhttprequest') {
            return true;
        }

        return false;
    }
}
