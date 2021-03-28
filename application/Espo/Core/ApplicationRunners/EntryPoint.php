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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\{
    Application\Runner,
    Application\RunnerParams,
    Exceptions\Error,
    EntryPoint\EntryPointManager,
    ApplicationUser,
    ORM\EntityManager,
    Portal\Application as PortalApplication,
    Utils\Route,
    Utils\ClientManager,
    Authentication\AuthenticationFactory,
    Api\AuthBuilderFactory,
    Api\ErrorOutput as ApiErrorOutput,
    Api\RequestWrapper,
    Api\ResponseWrapper,
    Authentication\AuthToken\AuthTokenManager,
};

use Slim\{
    ResponseEmitter,
    Factory\ServerRequestCreatorFactory,
    Psr7\Response,
};

use Exception;

/**
 * Runs an entry point.
 */
class EntryPoint implements Runner
{
    private $params;

    private $authenticationFactory;

    private $entryPointManager;

    private $entityManager;

    private $clientManager;

    private $applicationUser;

    private $authTokenManager;

    private $authBuilderFactory;

    public function __construct(
        AuthenticationFactory $authenticationFactory,
        EntryPointManager $entryPointManager,
        EntityManager $entityManager,
        ClientManager $clientManager,
        ApplicationUser $applicationUser,
        AuthTokenManager $authTokenManager,
        AuthBuilderFactory $authBuilderFactory,
        ?RunnerParams $params = null
    ) {
        $this->authenticationFactory = $authenticationFactory;
        $this->entryPointManager = $entryPointManager;
        $this->entityManager = $entityManager;
        $this->clientManager = $clientManager;
        $this->applicationUser = $applicationUser;
        $this->authTokenManager = $authTokenManager;
        $this->authBuilderFactory = $authBuilderFactory;

        $this->params = $params ?? RunnerParams::fromNothing();
    }

    public function run() : void
    {
        $requestWrapped = new RequestWrapper(
            ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
            Route::detectBasePath()
        );

        if ($requestWrapped->getMethod() !== 'GET') {
            throw new Error("Only GET requests allowed for entry points.");
        }

        $entryPoint = $this->params->get('entryPoint') ?? $requestWrapped->getQueryParam('entryPoint');

        $final = $this->params->get('final') ?? false;

        if (!$entryPoint) {
            throw new Error();
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

        $this->processRequest($entryPoint, $requestWrapped, $responseWrapped, $authRequired, $authNotStrict);

        (new ResponseEmitter())->emit($responseWrapped->getResponse());
    }

    private function processRequest(
        string $entryPoint,
        RequestWrapper $requestWrapped,
        ResponseWrapper $responseWrapped,
        bool $authRequired,
        bool $authNotStrict
    ) : void {

        try {
            $authentication = $authNotStrict ?
                $this->authenticationFactory->createWithAnyAccessAllowed() :
                $this->authenticationFactory->create();

            $apiAuth = $this->authBuilderFactory
                ->create()
                ->setAuthentication($authentication)
                ->setAuthRequired($authRequired)
                ->forEntryPoint()
                ->build();

            $authResult = $apiAuth->process($requestWrapped, $responseWrapped);

            if (!$authResult->isResolved()) {
                return;
            }

            if ($authResult->isResolvedUseNoAuth()) {
                $this->applicationUser->setupSystemUser();
            }

            ob_start();

            $this->entryPointManager->run($entryPoint, $requestWrapped, $responseWrapped/*, $data*/);

            $contents = ob_get_clean();

            if ($contents) {
                $responseWrapped->writeBody($contents);
            }
        }
        catch (Exception $e) {
            (new ApiErrorOutput($requestWrapped))->process($responseWrapped, $e, true);
        }
    }

    private function detectPortalId(RequestWrapper $requestWrapped) : ?string
    {
        if ($requestWrapped->hasQueryParam('portalId')) {
            return $requestWrapped->getQueryParam('portalId');
        }

        $token = $requestWrapped->getCookieParam('auth-token');

        if (!$token) {
            return null;
        }

        $authToken = $this->authTokenManager->get($token);

        if ($authToken) {
            return $authToken->getPortalId();
        }

        return null;
    }

    private function runThroughPortal(string $portalId, string $entryPoint) : void
    {
        $app = new PortalApplication($portalId);

        $app->setClientBasePath($this->clientManager->getBasePath());

        $params = RunnerParams::fromArray([
            'entryPoint' => $entryPoint,
            'final' => true,
        ]);

        $app->run(EntryPoint::class, $params);
    }
}
