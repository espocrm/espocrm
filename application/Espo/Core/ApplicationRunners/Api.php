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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\Exceptions\Error;

use Espo\Core\{
    InjectableFactory,
    ApplicationUser,
    Authentication\Authentication,
    Api\Auth as ApiAuth,
    Api\ErrorOutput as ApiErrorOutput,
    Api\RequestWrapper,
    Api\ResponseWrapper,
    Api\RouteProcessor,
    Utils\Route,
};

use Slim\{
    App as SlimApp,
    Factory\AppFactory as SlimAppFactory,
};

use Psr\Http\{
    Message\ResponseInterface as Psr7Response,
    Message\ServerRequestInterface as Psr7Request,
    Server\RequestHandlerInterface as Psr7RequestHandler,
};

use Exception;

/**
 * Runs API request processing.
 */
class Api implements ApplicationRunner
{
    protected $allowedMethodList = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
        'options',
    ];

    protected $injectableFactory;
    protected $applicationUser;

    public function __construct(InjectableFactory $injectableFactory, ApplicationUser $applicationUser)
    {
        $this->injectableFactory = $injectableFactory;
        $this->applicationUser = $applicationUser;
    }

    public function run()
    {
        $slim = SlimAppFactory::create();
        $slim->setBasePath(Route::detectBasePath());
        $slim->addRoutingMiddleware();

        $routeList = $this->getRouteList();
        $routeList = $this->filterRouteList($routeList);

        foreach ($routeList as $item) {
            $this->addRoute($slim, $item);
        }

        $slim->addErrorMiddleware(false, true, true);
        $slim->run();
    }

    protected function addRoute(SlimApp $slim, array $item)
    {
        $method = strtolower($item['method']);
        $route = $item['route'];

        $slim->$method(
            $route,
            function (Psr7Request $request, Psr7Response $response, array $args) use ($item, $slim) {
                $requestWrapped = new RequestWrapper($request, $slim->getBasePath());
                $responseWrapped = new ResponseWrapper($response);

                $this->processRequest($item, $requestWrapped, $responseWrapped, $args);

                return $responseWrapped->getResponse();
            }
        );
    }

    protected function processRequest(array $item, RequestWrapper $requestWrapped, ResponseWrapper $responseWrapped, array $args)
    {
        try {
            $authRequired = !($item['noAuth'] ?? false);

            $authentication = $this->injectableFactory->create(Authentication::class);

            $apiAuth = new ApiAuth($authentication, $authRequired);
            $apiAuth->process($requestWrapped, $responseWrapped);

            if (!$apiAuth->isResolved()) {
                return;
            }

            if ($apiAuth->isResolvedUseNoAuth()) {
                $this->applicationUser->setupSystemUser();
            }

            $routeProcessor = $this->injectableFactory->create(RouteProcessor::class);
            $routeProcessor->process($item['route'], $item['params'], $requestWrapped, $responseWrapped, $args);
        } catch (Exception $exception) {
            (new ApiErrorOutput($requestWrapped))->process(
                $responseWrapped, $exception, false, $item, $args
            );
        }
    }

    protected function filterRouteList(array $routeList) : array
    {
        $routeList = array_filter($routeList, function ($item) {
            $method = strtolower($item['method'] ?? '');
            $route = $item['route'] ?? null;

            if (!$route) {
                return false;
            }

            if (!in_array($method, $this->allowedMethodList)) {
                $GLOBALS['log']->warning("Route: Method '{$method}' is not supported. Fix the route '{$route}'.");
                return false;
            }
            return true;
        });

        return $routeList;
    }

    protected function getRouteList() : array
    {
        return $this->injectableFactory->create(Route::class)->getFullList();
    }
}
