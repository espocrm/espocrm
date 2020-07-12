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

namespace Espo\Core;

use Espo\Core\Exceptions\{
    Error,
};

use Espo\Core\{
    ContainerConfiguration,
    InjectableFactory,
    EntryPointManager,
    Authentication\Authentication,
    Api\Auth as ApiAuth,
    Api\ErrorOutput as ApiErrorOutput,
    Api\RequestWrapper,
    Api\ResponseWrapper,
    Api\RouteProcessor,
    Utils\Route,
    Utils\Autoload,
    Utils\Config,
    Utils\Metadata,
    Utils\ClientManager,
    Utils\Log,
    ORM\EntityManager,
    Portal\Application as PortalApplication,
    Loaders\Config as ConfigLoader,
    Loaders\Log as LogLoader,
    Loaders\FileManager as FileManagerLoader,
    Loaders\DataManager as DataManagerLoader,
    Loaders\Metadata as MetadataLoader,
};

use Psr\Http\{
    Message\ResponseInterface as Response,
    Message\ServerRequestInterface as Request,
    Server\RequestHandlerInterface as RequestHandler,
};

use Slim\{
    App as SlimApp,
    Factory\AppFactory as SlimAppFactory,
};

use ReflectionClass;

/**
 * A central access point of the application.
 */
class Application
{
    protected $container;

    protected $slim = null;

    protected $log;

    protected $loaderClassNames = [
        'config' => ConfigLoader::class,
        'log' => LogLoader::class,
        'fileManager' => FileManagerLoader::class,
        'dataManager' => DataManagerLoader::class,
        'metadata' => MetadataLoader::class,
    ];

    public function __construct()
    {
        date_default_timezone_set('UTC');

        $this->initContainer();
        $this->initAutoloads();
        $this->initPreloads();
    }

    protected function initContainer()
    {
        $this->container = new Container(ContainerConfiguration::class, $this->loaderClassNames);
    }

    /**
     * Run REST API.
     */
    public function runApi()
    {
        $slim = $this->createSlimApp();
        $slim->addRoutingMiddleware();

        $crudList = array_keys($this->getConfig()->get('crud'));

        $routeList = $this->getRouteList();

        foreach ($routeList as $item) {
            $method = strtolower($item['method']);
            $route = $item['route'];

            if (!in_array($method, $crudList) && $method !== 'options') {
                $this->getLog()->error("Route: Method '{$method}' does not exist. Check the route '{$route}'.");
                continue;
            }

            $slim->$method(
                $route,
                function (Request $request, Response $response, array $args) use ($item, $slim) {
                    $requestWrapped = new RequestWrapper($request, $slim->getBasePath());
                    $responseWrapped = new ResponseWrapper($response);

                    try {
                        $authRequired = !($item['noAuth'] ?? false);

                        $apiAuth = new ApiAuth($this->createAuthentication(), $authRequired);
                        $apiAuth->process($requestWrapped, $responseWrapped);

                        if (!$apiAuth->isResolved()) {
                            return $responseWrapped->getResponse();
                        }
                        if ($apiAuth->isResolvedUseNoAuth()) {
                            $this->setupSystemUser();
                        }

                        $routeProcessor = $this->getInjectableFactory()->create(RouteProcessor::class);
                        $routeProcessor->process($item['route'], $item['params'], $requestWrapped, $responseWrapped, $args);
                    } catch (\Exception $exception) {
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

    /**
     * Run entryPoint.
     */
    public function runEntryPoint(string $entryPoint, $data = null, bool $final = false)
    {
        if (empty($entryPoint)) {
            throw new Error();
        }

        $slim = $this->createSlimApp();

        $entryPointManager = $this->getInjectableFactory()->create(EntryPointManager::class);

        $authRequired = $entryPointManager->checkAuthRequired($entryPoint);
        $authNotStrict = $entryPointManager->checkNotStrictAuth($entryPoint);

        if ($authRequired && !$authNotStrict) {
            if (!$final && $portalId = $this->detectPortalId()) {
                $app = new PortalApplication($portalId);
                $app->setClientBasePath($this->getClientBasePath());
                $app->runEntryPoint($entryPoint, $data, true);
                exit;
            }
        }

        $slim->add(
            function (Request $request, RequestHandler $handler) use (
                $entryPointManager, $entryPoint, $data, $authRequired, $authNotStrict, $slim
            ) {
                $requestWrapped = new RequestWrapper($request, $slim->getBasePath());
                $responseWrapped = new ResponseWrapper($handler->handle($request));

                try {
                    $apiAuth = new ApiAuth($this->createAuthentication($authNotStrict), $authRequired, true);

                    $apiAuth->process($requestWrapped, $responseWrapped);

                    if (!$apiAuth->isResolved()) {
                        return $responseWrapped->getResponse();
                    }
                    if ($apiAuth->isResolvedUseNoAuth()) {
                        $this->setupSystemUser();
                    }

                    ob_start();
                    $entryPointManager->run($entryPoint, $requestWrapped, $responseWrapped, $data);
                    $contents = ob_get_clean();

                    if ($contents) {
                        $responseWrapped->writeBody($contents);
                    }
                } catch (\Exception $e) {
                    (new ApiErrorOutput($requestWrapped))->process($responseWrapped, $e, true);
                }

                return $responseWrapped->getResponse();
            }
        );

        $slim->get('/', function (Request $request, Response $response) {
            return $response;
        });

        $slim->run();
    }

    /**
     * Run an application through a specific runner.
     * You can find runner classes in `Espo\Core\ApplicationRunners`.
     */
    public function run(string $runnerName, ?object $params = null)
    {
        $className = $this->getRunnerClassName($runnerName);

        if (!$className) {
            $this->getLog()->error("Application runner '{$runnerName}' does not exist.");
            return;
        }

        $class = new ReflectionClass($className);

        if ($class->getStaticPropertyValue('cli', false)) {
            if (substr(php_sapi_name(), 0, 3) !== 'cli') {
                die("Application runner '{$runnerName}' can be run only via CLI.");
            }
        }
        if ($class->getStaticPropertyValue('setupSystemUser', false)) {
            $this->setupSystemUser();
        }

        $runner = $this->getInjectableFactory()->create($className);

        $runner->run($params);
    }

    protected function getRunnerClassName(string $runnerName) : ?string
    {
        $className = 'Espo\\Core\\ApplicationRunners\\' . ucfirst($runnerName);

        if (!class_exists($className)) {
            $className = $this->getMetadata()->get(['app', 'runners', $runnerName, 'className']);
            if (!$className || !class_exists($className)) {
                return null;
            }
        }

        return $className;
    }

    /**
     * Whether an application is installed.
     */
    public function isInstalled() : bool
    {
        $config = $this->getConfig();

        if (file_exists($config->getConfigPath()) && $config->get('isInstalled')) {
            return true;
        }

        return false;
    }

    /**
     * Get a service container.
     */
    public function getContainer() : Container
    {
        return $this->container;
    }

    protected function getInjectableFactory() : InjectableFactory
    {
        return $this->container->get('injectableFactory');
    }

    protected function getLog() : Log
    {
        return $this->container->get('log');
    }

    protected function getClientManager() : ClientManager
    {
        return $this->container->get('clientManager');
    }

    protected function getMetadata() : Metadata
    {
        return $this->container->get('metadata');
    }

    protected function getConfig() : Config
    {
        return $this->container->get('config');
    }

    protected function getEntityManager() : EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function createSlimApp() : SlimApp
    {
        $slim = SlimAppFactory::create();
        $slim->setBasePath(Route::detectBasePath());
        return $slim;
    }

    protected function createAuthentication(bool $allowAnyAccess = false) : Authentication
    {
        return $this->getInjectableFactory()->createWith(Authentication::class, [
            'allowAnyAccess' => $allowAnyAccess,
        ]);
    }

    protected function initAutoloads()
    {
        $autoload = $this->getInjectableFactory()->create(Autoload::class);
        $autoload->register();
    }

    /**
     * Initialize services that has the 'preload' parameter.
     */
    protected function initPreloads()
    {
        foreach ($this->getMetadata()->get(['app', 'containerServices']) ?? [] as $name => $defs) {
            if ($defs['preload'] ?? false) {
                $this->container->get($name);
            }
        }
    }

    protected function getRouteList() : array
    {
        return $this->getInjectableFactory()->create(Route::class)->getFullList();
    }

    /**
     * Set a base path of an index file related to the application directory. Used for a portal.
     */
    public function setClientBasePath(string $basePath)
    {
        $this->getClientManager()->setBasePath($basePath);
    }

    /**
     * Get a base path of an index file related to the application directory. Used for a portal.
     */
    public function getClientBasePath() : string
    {
        return $this->getClientManager()->getBasePath();
    }

    protected function detectPortalId() : ?string
    {
        if (!empty($_GET['portalId'])) {
            return $_GET['portalId'];
        }
        if (!empty($_COOKIE['auth-token'])) {
            $token =
                $this->getEntityManager()->getRepository('AuthToken')
                    ->where(['token' => $_COOKIE['auth-token']])->findOne();

            if ($token && $token->get('portalId')) {
                return $token->get('portalId');
            }
        }
        return null;
    }

    /**
     * Setup the system user. The system user is used when no user is logged in.
     */
    public function setupSystemUser()
    {
        $user = $this->getEntityManager()->getEntity('User', 'system');
        if (!$user) {
            throw new Error("System user is not found");
        }

        $user->set('ipAddress', $_SERVER['REMOTE_ADDR'] ?? null);
        $user->set('type', 'system');

        $this->container->set('user', $user);
    }
}
