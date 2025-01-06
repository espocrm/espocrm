<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Application\Runner\Params as RunnerParams;
use Espo\Core\ApplicationUser;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Authentication\AuthenticationFactory;
use Espo\Core\Authentication\AuthToken\Manager as AuthTokenManager;
use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Api\AuthBuilderFactory;
use Espo\Core\Portal\Utils\Url;
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
    public function __construct(
        private AuthenticationFactory $authenticationFactory,
        private EntryPointManager $entryPointManager,
        private ClientManager $clientManager,
        private ApplicationUser $applicationUser,
        private AuthTokenManager $authTokenManager,
        private AuthBuilderFactory $authBuilderFactory,
        private ErrorOutput $errorOutput
    ) {}

    /**
     * @throws BadRequest
     */
    public function start(?string $entryPoint = null, bool $final = false): void
    {
        $requestWrapped = new RequestWrapper(
            ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
            Route::detectBasePath()
        );

        if ($requestWrapped->getMethod() !== 'GET') {
            throw new BadRequest("Only GET requests allowed for entry points.");
        }

        if ($entryPoint === null) {
            $entryPoint = $requestWrapped->getQueryParam('entryPoint');
        }

        if (!$entryPoint) {
            throw new BadRequest("No 'entryPoint' param.");
        }

        /**
         * @todo
         *     Consider supporting portal detection when it's run through the `portals/` directory.
         *     E.g. ChangePassword is not run through the portal unless the ID is set in ENV.
         */
        $portalId = Url::getPortalIdFromEnv();

        if ($portalId && !$final) {
            $this->runThroughPortal($portalId, $entryPoint);

            return;
        }

        $responseWrapped = new ResponseWrapper(new Response());

        try {
            $authRequired = $this->entryPointManager->checkAuthRequired($entryPoint);
        } catch (NotFound $exception) {
            $this->errorOutput->processWithBodyPrinting($requestWrapped, $responseWrapped, $exception);

            (new ResponseEmitter())->emit($responseWrapped->toPsr7());

            return;
        }

        if ($authRequired && !$final) {
            $portalId = $this->detectPortalId($requestWrapped);

            if ($portalId) {
                $this->runThroughPortal($portalId, $entryPoint);

                return;
            }
        }

        $this->processRequest(
            $entryPoint,
            $requestWrapped,
            $responseWrapped,
            $authRequired
        );

        (new ResponseEmitter())->emit($responseWrapped->toPsr7());
    }

    private function processRequest(
        string $entryPoint,
        RequestWrapper $requestWrapped,
        ResponseWrapper $responseWrapped,
        bool $authRequired
    ): void {

        try {
            $this->processRequestInternal(
                $entryPoint,
                $requestWrapped,
                $responseWrapped,
                $authRequired
            );
        } catch (Exception $exception) {
            $this->errorOutput->processWithBodyPrinting($requestWrapped, $responseWrapped, $exception);
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
        bool $authRequired
    ): void {

        $authentication = $this->authenticationFactory->create();

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

        $clientManager = $app->getContainer()
            ->getByClass(ClientManager::class);

        $clientManager->setBasePath($this->clientManager->getBasePath());
        $clientManager->setApiUrl('api/v1/portal-access/' . $portalId);
        $clientManager->setApplicationId($portalId);

        $params = RunnerParams::fromArray([
            'entryPoint' => $entryPoint,
            'final' => true,
        ]);

        $app->run(EntryPointRunner::class, $params);
    }
}
