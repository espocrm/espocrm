<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Api\Route\RouteParamsFetcher;
use Espo\Core\Utils\Config;
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
    private string $routeCacheFile = 'data/cache/application/slim-routes.php';

    public function __construct(
        private RouteProcessor $routeProcessor,
        private RouteUtil $routeUtil,
        private RouteParamsFetcher $routeParamsFetcher,
        private MiddlewareProvider $middlewareProvider,
        private Log $log,
        private Config $config,
        ?string $routeCacheFile = null
    ) {
        $this->routeCacheFile = $routeCacheFile ?? $this->routeCacheFile;
    }

    public function start(): void
    {
        $slim = SlimAppFactory::create();

        if ($this->config->get('useCache')) {
            $slim->getRouteCollector()->setCacheFile($this->routeCacheFile);
        }

        $slim->setBasePath(RouteUtil::detectBasePath());
        $this->addGlobalMiddlewares($slim);
        $slim->addRoutingMiddleware();
        $this->addRoutes($slim);
        $slim->addErrorMiddleware(false, true, true, $this->log);

        $slim->run();
    }

    private function addGlobalMiddlewares(SlimApp $slim): void
    {
        foreach ($this->middlewareProvider->getGlobalMiddlewareList() as $middleware) {
            $slim->add($middleware);
        }
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
        $slimRoute = $slim->map(
            [$item->getMethod()],
            $item->getAdjustedRoute(),
            function (Psr7Request $request, Psr7Response $response, array $args) use ($slim, $item) {
                $routeParams = $this->routeParamsFetcher->fetch($item, $args);

                $processData = new ProcessData(
                    route: $item,
                    basePath: $slim->getBasePath(),
                    routeParams: $routeParams,
                );

                return $this->routeProcessor->process($processData, $request, $response);
            }
        );

        $middlewareList = $this->middlewareProvider->getRouteMiddlewareList($item);

        foreach ($middlewareList as $middleware) {
            $slimRoute->addMiddleware($middleware);
        }
    }
}
