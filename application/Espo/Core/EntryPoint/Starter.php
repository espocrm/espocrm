<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\EntryPoint;

use Espo\Core\Api\Method;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Application\Runner\Params as RunnerParams;
use Espo\Core\ApplicationUser;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Authentication\AuthenticationFactory;
use Espo\Core\Authentication\AuthToken\Manager as AuthTokenManager;
use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Api\AuthBuilderFactory;
use Espo\Core\Portal\Utils\Url;
use Espo\Core\Utils\ClientManager;
use Espo\Core\ApplicationRunners\EntryPoint as EntryPointRunner;

use Exception;

/**
 * Starts an entry point.
 */
class Starter
{
    public function __construct(
        private AuthenticationFactory $authenticationFactory,
        private EntryPointManager $entryPointManager,
        private ClientManager $clientManager,
        private ApplicationUser $applicationUser,
        private AuthTokenManager $authTokenManager,
        private AuthBuilderFactory $authBuilderFactory,
        private RequestFactory $requestFactory,
        private ResponseFactory $responseFactory,
        private ErrorOutput $errorOutput,
        private OutputEmitter $outputEmitter,
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function start(Params $params = new Params()): void
    {
        $request = $this->requestFactory->create();
        $response = $this->responseFactory->create();

        try {
            $name = $this->getName($params, $request);

            $this->checkStart($name, $params, $request->getMethod());
        } catch (BadRequest|Forbidden $e) {
            $this->errorOutput->processWithBodyPrinting($request, $response, $e);
            $this->outputEmitter->emit($response);

            return;
        }

        /**
         * @todo
         *     Consider supporting portal detection when it's run through the `portals/` directory.
         *     E.g. ChangePassword is not run through the portal unless the ID is set in ENV.
         */
        $portalId = Url::getPortalIdFromEnv();

        if ($portalId && !$params->final) {
            $this->runThroughPortal($portalId, $name);

            return;
        }

        try {
            $authRequired = $this->entryPointManager->checkAuthRequired($name);
        } catch (NotFound $e) {
            $this->errorOutput->processWithBodyPrinting($request, $response, $e);
            $this->outputEmitter->emit($response);

            return;
        }

        if ($authRequired && !$params->final) {
            $portalId = $this->detectPortalId($request);

            if ($portalId) {
                $this->runThroughPortal($portalId, $name);

                return;
            }
        }

        $this->processRequest(
            entryPoint: $name,
            request: $request,
            response: $response,
            authRequired: $authRequired,
        );

        $this->outputEmitter->emit($response);
    }

    private function processRequest(
        string $entryPoint,
        RequestWrapper $request,
        ResponseWrapper $response,
        bool $authRequired,
    ): void {

        try {
            $this->processRequestInternal(
                entryPoint: $entryPoint,
                request: $request,
                response: $response,
                authRequired: $authRequired,
            );
        } catch (Exception $exception) {
            print_r($exception->getMessage());
            $this->errorOutput->processWithBodyPrinting($request, $response, $exception);
        }
    }

    /**
     * @throws NotFound
     * @throws BadRequest
     */
    private function processRequestInternal(
        string $entryPoint,
        RequestWrapper $request,
        ResponseWrapper $response,
        bool $authRequired,
    ): void {

        $authentication = $this->authenticationFactory->create();

        $apiAuth = $this->authBuilderFactory->create()
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

        return $authToken?->getPortalId();
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function runThroughPortal(string $portalId, string $entryPoint): void
    {
        $app = new PortalApplication($portalId);

        $clientManager = $app->getContainer()->getByClass(ClientManager::class);

        $clientManager->setBasePath($this->clientManager->getBasePath());
        $clientManager->setApiUrl('api/v1/portal-access/' . $portalId);
        $clientManager->setApplicationId($portalId);

        $params = RunnerParams::fromArray([
            EntryPointRunner::PARAM_ENTRY_POINT => $entryPoint,
            EntryPointRunner::PARAM_FINAL => true,
        ]);

        $app->run(EntryPointRunner::class, $params);
    }

    /**
     * @throws BadRequest
     */
    private function getName(Params $params, RequestWrapper $request): string
    {
        $name = $params->name ?? $request->getQueryParam('entryPoint');

        if (!$name) {
            throw new BadRequest("No 'entryPoint' param.");
        }

        return $name;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function checkStart(string $name, Params $params, string $method): void
    {
        $metaParams = $this->entryPointManager->getMetaParams($name);

        if ($metaParams->notExposed && $params->name === null) {
            throw new Forbidden("The entry point is not exposed.");
        }

        if ($metaParams->allowedMethods !== null) {
            if (!in_array(strtolower($method), $metaParams->allowedMethods)) {
                throw new BadRequest("Method '$method' is not allowed.");
            }
        } else if ($method !== Method::GET) {
            throw new BadRequest("Only GET requests allowed for the entry point.");
        }
    }
}
