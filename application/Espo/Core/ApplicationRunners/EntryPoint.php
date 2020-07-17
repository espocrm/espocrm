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
    EntryPointManager,
    ApplicationUser,
    ORM\EntityManager,
    Portal\Application as PortalApplication,
    Utils\Route,
    Utils\ClientManager,

    Authentication\Authentication,
    Api\Auth as ApiAuth,
    Api\ErrorOutput as ApiErrorOutput,
    Api\RequestWrapper,
    Api\ResponseWrapper,
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

use StdClass;

/**
 * Runs an entry point.
 */
class EntryPoint implements ApplicationRunner
{
    protected $injectableFactory;
    protected $entryPointManager;
    protected $entityManager;
    protected $clientManager;
    protected $applicationUser;

    public function __construct(
        InjectableFactory $injectableFactory,
        EntryPointManager $entryPointManager,
        EntityManager $entityManager,
        ClientManager $clientManager,
        ApplicationUser $applicationUser
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->entryPointManager = $entryPointManager;
        $this->entityManager = $entityManager;
        $this->clientManager = $clientManager;
        $this->applicationUser = $applicationUser;
    }

    public function run(?StdClass $params = null)
    {
        $params = $params ?? (object) [];

        $entryPoint = $params->entryPoint ?? $_GET['entryPoint'];

        $final = $params->final ?? false;
        $data = $params->data ?? null;

        if (!$entryPoint) {
            throw new Error();
        }

        $authRequired = $this->entryPointManager->checkAuthRequired($entryPoint);
        $authNotStrict = $this->entryPointManager->checkNotStrictAuth($entryPoint);

        if ($authRequired && !$authNotStrict && !$final) {
            $portalId = $this->detectPortalId();
            if ($portalId) {
                $this->runThroughPortal($portalId, $entryPoint, $data);
                return;
            }
        }

        $slim = SlimAppFactory::create();
        $slim->setBasePath(Route::detectBasePath());

        $slim->add(
            function (Psr7Request $request, Psr7RequestHandler $handler) use (
                $entryPoint, $data, $authRequired, $authNotStrict, $slim
            ) : Psr7Response {
                $requestWrapped = new RequestWrapper($request, $slim->getBasePath());
                $responseWrapped = new ResponseWrapper($handler->handle($request));

                $this->processRequest($entryPoint, $requestWrapped, $responseWrapped, $data, $authRequired, $authNotStrict);

                return $responseWrapped->getResponse();
            }
        );

        $slim->get('/', function (Psr7Request $request, Psr7Response $response) : Psr7Response {
            return $response;
        });

        $slim->run();
    }

    protected function processRequest(
        string $entryPoint,
        RequestWrapper $requestWrapped,
        ResponseWrapper $responseWrapped,
        ?StdClass $data,
        bool $authRequired,
        bool $authNotStrict
    ) {
        try {
            $authentication = $this->injectableFactory->createWith(Authentication::class, [
                'allowAnyAccess' => $authNotStrict,
            ]);

            $apiAuth = ApiAuth::createForEntryPoint($authentication, $authRequired);

            $apiAuth->process($requestWrapped, $responseWrapped);

            if (!$apiAuth->isResolved()) {
                return;
            }

            if ($apiAuth->isResolvedUseNoAuth()) {
                $this->applicationUser->setupSystemUser();
            }

            ob_start();
            $this->entryPointManager->run($entryPoint, $requestWrapped, $responseWrapped, $data);
            $contents = ob_get_clean();

            if ($contents) {
                $responseWrapped->writeBody($contents);
            }
        } catch (\Exception $e) {
            (new ApiErrorOutput($requestWrapped))->process($responseWrapped, $e, true);
        }
    }

    protected function detectPortalId() : ?string
    {
        if (!empty($_GET['portalId'])) {
            return $_GET['portalId'];
        }
        if (!empty($_COOKIE['auth-token'])) {
            $token = $this->entityManager->getRepository('AuthToken')->where(['token' => $_COOKIE['auth-token']])->findOne();

            if ($token && $token->get('portalId')) {
                return $token->get('portalId');
            }
        }
        return null;
    }

    protected function runThroughPortal(string $portalId, string $entryPoint, ?StdClass $data)
    {
        $app = new PortalApplication($portalId);
        $app->setClientBasePath($this->clientManager->getBasePath());
        $app->run(EntryPoint::class, (object) [
            'entryPoint' => $entryPoint,
            'data' => $data,
            'final' => true,
        ]);
    }
}
