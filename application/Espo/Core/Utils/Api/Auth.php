<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
        $req = $this->app->request();

        $uri = $req->getResourceUri();
        $httpMethod = $req->getMethod();

        $authUsername = $req->headers('PHP_AUTH_USER');
        $authPassword = $req->headers('PHP_AUTH_PW');

        $espoAuth = $req->headers('HTTP_ESPO_AUTHORIZATION');
        if (isset($espoAuth)) {
            list($authUsername, $authPassword) = explode(':', base64_decode($espoAuth));
        }

        $espoCgiAuth = $req->headers('HTTP_ESPO_CGI_AUTH');
        if (empty($espoCgiAuth)) {
            $espoCgiAuth = $req->headers('REDIRECT_HTTP_ESPO_CGI_AUTH');
        }
        if (!isset($authUsername) && !isset($authPassword) && !empty($espoCgiAuth)) {
            list($authUsername, $authPassword) = explode(':' , base64_decode(substr($espoCgiAuth, 6)));
        }

        if (is_null($this->authRequired)) {
            $routes = $this->app->router()->getMatchedRoutes($httpMethod, $uri);

            if (!empty($routes[0])) {
                $routeConditions = $routes[0]->getConditions();
                if (isset($routeConditions['auth']) && $routeConditions['auth'] === false) {

                    if ($authUsername && $authPassword) {
                        $isAuthenticated = $this->auth->login($authUsername, $authPassword);
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

        if ($authUsername && $authPassword) {

            $isAuthenticated = $this->auth->login($authUsername, $authPassword);

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

    protected function processUnauthorized()
    {
        $res = $this->app->response();

        if ($this->showDialog) {
            $res->header('WWW-Authenticate', 'Basic realm=""');
        } else {
            $res->header('WWW-Authenticate');
        }
        $res->status(401);
    }

    protected function isXMLHttpRequest()
    {
        $req = $this->app->request();

        $httpXRequestedWith = $req->headers('HTTP_X_REQUESTED_WITH');

        if (isset($httpXRequestedWith) && strtolower($httpXRequestedWith) == 'xmlhttprequest') {
            return true;
        }

        return false;
    }

}

