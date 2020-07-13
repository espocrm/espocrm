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
    Utils\Config,
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
 * Runs API.
 */
class Api implements ApplicationRunner
{
    protected $injectableFactory;
    protected $applicationUser;

    public function __construct(InjectableFactory $injectableFactory, ApplicationUser $applicationUser, Config $config) {
        $this->injectableFactory = $injectableFactory;
        $this->applicationUser = $applicationUser;
        $this->config = $config;
    }

    public function run()
    {
        $slim = SlimAppFactory::create();
        $slim->setBasePath(Route::detectBasePath());
        $slim->addRoutingMiddleware();

        $crudList = array_keys($this->config->get('crud'));

        $routeList = $this->getRouteList();

        foreach ($routeList as $item) {
            $method = strtolower($item['method']);
            $route = $item['route'];

            if (!in_array($method, $crudList) && $method !== 'options') {
                $GLOBALS['log']->error("Route: Method '{$method}' does not exist. Check the route '{$route}'.");
                continue;
            }

            $slim->$method(
                $route,
                function (Psr7Request $request, Psr7Response $response, array $args) use ($item, $slim) {
                    $requestWrapped = new RequestWrapper($request, $slim->getBasePath());
                    $responseWrapped = new ResponseWrapper($response);

                    try {
                        $authRequired = !($item['noAuth'] ?? false);

                        $authentication = $this->injectableFactory->create(Authentication::class);

                        $apiAuth = new ApiAuth($authentication, $authRequired);
                        $apiAuth->process($requestWrapped, $responseWrapped);

                        if (!$apiAuth->isResolved()) {
                            return $responseWrapped->getResponse();
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

                    return $responseWrapped->getResponse();
                }
            );
        }

        $slim->addErrorMiddleware(false, true, true);
        $slim->run();
    }

    protected function getRouteList() : array
    {
        return $this->injectableFactory->create(Route::class)->getFullList();
    }
}
