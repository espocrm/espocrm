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

use Espo\Core\Exceptions\Error;
use Espo\Core\Authentication\AuthenticationFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\ApplicationUser;

use Exception;
use Throwable;

/**
 * Processes requests. Handles authentication. Obtains a controller name, action, body from a request.
 * Then passes them to the action processor.
 */
class RequestProcessor
{
    private $authenticationFactory;

    private $actionProcessor;

    private $authBuilderFactory;

    private $errorOutput;

    private $config;

    private $log;

    private $applicationUser;

    public function __construct(
        AuthenticationFactory $authenticationFactory,
        ActionProcessor $actionProcessor,
        AuthBuilderFactory $authBuilderFactory,
        ErrorOutput $errorOutput,
        Config $config,
        Log $log,
        ApplicationUser $applicationUser
    ) {
        $this->authenticationFactory = $authenticationFactory;
        $this->actionProcessor = $actionProcessor;
        $this->authBuilderFactory = $authBuilderFactory;
        $this->errorOutput = $errorOutput;
        $this->config = $config;
        $this->log = $log;
        $this->applicationUser = $applicationUser;
    }

    public function process(Route $route, Request $request, Response $response): void
    {
        try {
            $this->processInternal($route, $request, $response);
        }
        catch (Exception $exception) {
            $this->handleException($exception, $request, $response, $route->getRoute());
        }
    }

    private function processInternal(Route $route, Request $request, Response $response): void
    {
        $authRequired = !$route->noAuth();

        $apiAuth = $this->authBuilderFactory
            ->create()
            ->setAuthentication($this->authenticationFactory->create())
            ->setAuthRequired($authRequired)
            ->build();

        $authResult = $apiAuth->process($request, $response);

        if (!$authResult->isResolved()) {
            return;
        }

        if ($authResult->isResolvedUseNoAuth()) {
            $this->applicationUser->setupSystemUser();
        }

        ob_start();

        $this->proceed($request, $response);

        ob_clean();
    }

    private function proceed(Request $request, Response $response): void
    {
        $this->beforeProceed($response);

        $controllerName = $this->getControllerName($request);
        $actionName = $request->getRouteParam('action');
        $requestMethod = $request->getMethod();

        if (!$actionName) {
            $httpMethod = strtolower($requestMethod);

            $crudList = $this->config->get('crud') ?? [];

            $actionName = $crudList[$httpMethod] ?? null;

            if (!$actionName) {
                throw new Error("No action for method {$httpMethod}.");
            }
        }

        $this->actionProcessor->process($controllerName, $actionName, $request, $response);

        $this->afterProceed($response);
    }

    private function getControllerName(Request $request): string
    {
        $controllerName = $request->getRouteParam('controller');

        if (!$controllerName) {
            throw new Error("Route doesn't have specified controller.");
        }

        return ucfirst($controllerName);
    }

    private function handleException(
        Exception $exception,
        Request $request,
        Response $response,
        string $route
    ): void {

        try {
            $this->errorOutput->process($request, $response, $exception, $route);
        }
        catch (Throwable $exceptionAnother) {
            $this->log->error($exceptionAnother->getMessage());

            $response->setStatus(500);
        }
    }

    private function beforeProceed(Response $response): void
    {
        $response->setHeader('Content-Type', 'application/json');
    }

    private function afterProceed(Response $response): void
    {
        $response
            ->setHeader('Expires', '0')
            ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
