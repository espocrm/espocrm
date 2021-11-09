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

namespace Espo\Core\EntryPoint;

use Espo\Core\Exceptions\Error;
use Espo\Core\Application\Runner\Params as RunnerParams;
use Espo\Core\EntryPoint\EntryPointManager;
use Espo\Core\ApplicationUser;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Authentication\AuthenticationFactory;
use Espo\Core\Authentication\AuthToken\Manager as AuthTokenManager;
use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Api\AuthBuilderFactory;
use Espo\Core\Utils\Route;
use Espo\Core\Utils\ClientManager;
use Espo\Core\ApplicationRunners\EntryPoint as EntryPointRunner;

use Slim\ResponseEmitter;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Response;

use Exception;

/**
 * Starts an entry point.
 */
class Starter
{
    private $authenticationFactory;

    private $entryPointManager;

    private $clientManager;

    private $applicationUser;

    private $authTokenManager;

    private $authBuilderFactory;

    private $errorOutput;

    public function __construct(
        AuthenticationFactory $authenticationFactory,
        EntryPointManager $entryPointManager,
        ClientManager $clientManager,
        ApplicationUser $applicationUser,
        AuthTokenManager $authTokenManager,
        AuthBuilderFactory $authBuilderFactory,
        ErrorOutput $errorOutput
    ) {
        $this->authenticationFactory = $authenticationFactory;
        $this->entryPointManager = $entryPointManager;
        $this->clientManager = $clientManager;
        $this->applicationUser = $applicationUser;
        $this->authTokenManager = $authTokenManager;
        $this->authBuilderFactory = $authBuilderFactory;
        $this->errorOutput = $errorOutput;
    }

    public function start(?string $entryPoint = null, bool $final = false): void
    {
        $requestWrapped = new RequestWrapper(
            ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
            Route::detectBasePath()
        );

        if ($requestWrapped->getMethod() !== 'GET') {
            throw new Error("Only GET requests allowed for entry points.");
        }

        if ($entryPoint === null) {
            $entryPoint = $requestWrapped->getQueryParam('entryPoint');
        }

        if (!$entryPoint) {
            throw new Error("No 'entryPoint' param.");
        }

        $authRequired = $this->entryPointManager->checkAuthRequired($entryPoint);
        $authNotStrict = $this->entryPointManager->checkNotStrictAuth($entryPoint);

        if ($authRequired && !$authNotStrict && !$final) {
            $portalId = $this->detectPortalId($requestWrapped);

            if ($portalId) {
                $this->runThroughPortal($portalId, $entryPoint);

                return;
            }
        }

        $responseWrapped = new ResponseWrapper(new Response());

        $this->processRequest(
            $entryPoint,
            $requestWrapped,
            $responseWrapped,
            $authRequired,
            $authNotStrict
        );

        (new ResponseEmitter())->emit($responseWrapped->getResponse());
    }

    private function processRequest(
        string $entryPoint,
        RequestWrapper $requestWrapped,
        ResponseWrapper $responseWrapped,
        bool $authRequired,
        bool $authNotStrict
    ): void {

        try {
            $this->processRequestInternal(
                $entryPoint,
                $requestWrapped,
                $responseWrapped,
                $authRequired,
                $authNotStrict
            );
        }
        catch (Exception $exception) {
            $this->errorOutput->processWithBodyPrinting($requestWrapped, $responseWrapped, $exception);
        }
    }

    private function processRequestInternal(
        string $entryPoint,
        RequestWrapper $request,
        ResponseWrapper $response,
        bool $authRequired,
        bool $authNotStrict
    ): void {

        $authentication = $authNotStrict ?
            $this->authenticationFactory->createWithAnyAccessAllowed() :
            $this->authenticationFactory->create();

        $apiAuth = $this->authBuilderFactory
            ->create()
            ->setAuthentication($authentication)
            ->setAuthRequired($authRequired)
            ->forEntryPoint()
            ->build();

        $authResult = $apiAuth->process($request, $response);

        if (!$authResult->isResolved()) {
            return;
        }

        if ($authResult->isResolvedUseNoAuth()) {
            $this->applicationUser->setupSystemUser();
        }

        ob_start();

        $this->entryPointManager->run($entryPoint, $request, $response);

        $contents = ob_get_clean();

        if ($contents) {
            $response->writeBody($contents);
        }
    }

    private function detectPortalId(RequestWrapper $request): ?string
    {
        if ($request->hasQueryParam('portalId')) {
            return $request->getQueryParam('portalId');
        }

        $token = $request->getCookieParam('auth-token');

        if (!$token) {
            return null;
        }

        $authToken = $this->authTokenManager->get($token);

        if ($authToken) {
            return $authToken->getPortalId();
        }

        return null;
    }

    private function runThroughPortal(string $portalId, string $entryPoint): void
    {
        $app = new PortalApplication($portalId);

        $app->setClientBasePath($this->clientManager->getBasePath());

        $params = RunnerParams::fromArray([
            'entryPoint' => $entryPoint,
            'final' => true,
        ]);

        $app->run(EntryPointRunner::class, $params);
    }
}
