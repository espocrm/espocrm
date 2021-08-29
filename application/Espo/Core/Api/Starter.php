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

use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Api\RequestProcessor;
use Espo\Core\Api\Route;
use Espo\Core\Api\Route\RouteParamsFetcher;
use Espo\Core\Utils\Route as RouteUtil;
use Espo\Core\Utils\Log;

use Slim\App as SlimApp;
use Slim\Factory\AppFactory as SlimAppFactory;

use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\ServerRequestInterface as Psr7Request;

/**
 * API request processing entry point.
 */
class Starter
{
    private $requestProcessor;

    private $routeUtil;

    private $routeParamsFetcher;

    private $log;

    public function __construct(
        RequestProcessor $requestProcessor,
        RouteUtil $routeUtil,
        RouteParamsFetcher $routeParamsFetcher,
        Log $log
    ) {
        $this->requestProcessor = $requestProcessor;
        $this->routeUtil = $routeUtil;
        $this->routeParamsFetcher = $routeParamsFetcher;
        $this->log = $log;
    }

    public function start(): void
    {
        $slim = SlimAppFactory::create();

        $slim->setBasePath(RouteUtil::detectBasePath());
        $slim->addRoutingMiddleware();
        $this->addRoutes($slim);
        $slim->addErrorMiddleware(false, true, true, $this->log);
        $slim->run();
    }

    private function addRoutes(SlimApp $slim): void
    {
        $routeList = $this->routeUtil->getFullList();

        foreach ($routeList as $item) {
            $this->addRoute($slim, $item);
        }
    }

    private function addRoute(SlimApp $slim, Route $item): void
    {
        $slim->map(
            [$item->getMethod()],
            $item->getRoute(),
            function (Psr7Request $request, Psr7Response $response, array $args) use ($slim, $item)
            {
                $routeParams = $this->routeParamsFetcher->fetch($item, $args);

                $requestWrapped = new RequestWrapper($request, $slim->getBasePath(), $routeParams);
                $responseWrapped = new ResponseWrapper($response);

                $this->requestProcessor->process($item, $requestWrapped, $responseWrapped);

                return $responseWrapped->getResponse();
            }
        );
    }
}
