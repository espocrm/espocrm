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
    EntryPointManager,
    CronManager,
    Api\Auth as ApiAuth,
    Api\ErrorOutput as ApiErrorOutput,
    Api\RequestWrapper,
    Api\ResponseWrapper,
    Utils\Auth,
    Utils\Route,
    Utils\Autoload,
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

/**
 * A central access point of the application.
 */
class Application
{
    protected $container;

    protected $slim = null;

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

        $routeList = $this->getInjectableFactory()->create(Route::class)->getAll();

        foreach ($routeList as $item) {
            $method = strtolower($item['method']);
            $route = $item['route'];

            if (!in_array($method, $crudList) && $method !== 'options') {
                $GLOBALS['log']->error("Route: Method '{$method}' does not exist. Check the route '{$route}'.");
                continue;
            }

            $slim->$method(
                $route,
                function (Request $request, Response $response, array $args) use ($item, $slim) {
                    $requestWrapped = new RequestWrapper($request, $slim->getBasePath());
                    $responseWrapped = new ResponseWrapper($response);

                    try {
                        $authRequired = true;

                        $conditions = $item['conditions'] ?? [];
                        if (($conditions['auth'] ?? true) === false) {
                            $authRequired = false;
                        }

                        $auth = $this->createAuth($requestWrapped);
                        $apiAuth = new ApiAuth($auth, $authRequired);

                        $apiAuth->process($requestWrapped, $responseWrapped);

                        if (!$apiAuth->isResolved()) {
                            return $responseWrapped->getResponse();
                        }
                        if ($apiAuth->isResolvedUseNoAuth()) {
                            $this->setupSystemUser();
                        }

                        $this->processRoute($item, $requestWrapped, $responseWrapped, $args);
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
     * Display the main HTML page.
     */
    public function runClient()
    {
        $this->container->get('clientManager')->display();
        exit;
    }

    /**
     * Run entryPoint.
     */
    public function runEntryPoint(string $entryPoint, array $data = [], bool $final = false)
    {
        if (empty($entryPoint)) {
            throw new Error();
        }

        $slim = $this->createSlimApp();

        $injectableFactory = $this->container->get('injectableFactory');
        $classFinder = $this->container->get('classFinder');

        $entryPointManager = new EntryPointManager($injectableFactory, $classFinder);

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
                    $auth = $this->createAuth($requestWrapped, $authNotStrict);
                    $apiAuth = new ApiAuth($auth, $authRequired, true, true);

                    $apiAuth->process($requestWrapped, $responseWrapped);

                    if (!$apiAuth->isResolved()) {
                        $requestWrapped->getResponse();
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
     * Run cron.
     */
    public function runCron()
    {
        if ($this->getConfig()->get('cronDisabled')) {
            $GLOBALS['log']->warning("Cron is not run because it's disabled with 'cronDisabled' param.");
            return;
        }

        $this->setupSystemUser();

        $cronManager = $this->container->get('cronManager');
        $cronManager->run();
    }

    /**
     * Run daemon.
     */
    public function runDaemon()
    {
        $maxProcessNumber = $this->getConfig()->get('daemonMaxProcessNumber');
        $interval = $this->getConfig()->get('daemonInterval');
        $timeout = $this->getConfig()->get('daemonProcessTimeout');

        $phpExecutablePath = $this->getConfig()->get('phpExecutablePath');
        if (!$phpExecutablePath) {
            $phpExecutablePath = (new \Symfony\Component\Process\PhpExecutableFinder)->find();
        }

        if (!$maxProcessNumber || !$interval) {
            $GLOBALS['log']->error("Daemon config params are not set.");
            return;
        }

        $processList = [];
        while (true) {
            $toSkip = false;
            $runningCount = 0;
            foreach ($processList as $i => $process) {
                if ($process->isRunning()) {
                    $runningCount++;
                } else {
                    unset($processList[$i]);
                }
            }
            $processList = array_values($processList);
            if ($runningCount >= $maxProcessNumber) {
                $toSkip = true;
            }
            if (!$toSkip) {
                $process = new \Symfony\Component\Process\Process([$phpExecutablePath, 'cron.php']);
                $process->setTimeout($timeout);
                $process->run();
                $processList[] = $process;
            }
            sleep($interval);
        }
    }

    /**
     * Run a job by ID. A job record should exist in database.
     */
    public function runJob(string $id)
    {
        $this->setupSystemUser();

        $cronManager = $this->container->get('cronManager');
        $cronManager->runJobById($id);
    }

    /**
     * Rebuild application.
     */
    public function runRebuild()
    {
        $dataManager = $this->container->get('dataManager');
        $dataManager->rebuild();
    }

    /**
     * Clear application cache.
     */
    public function runClearCache()
    {
        $dataManager = $this->container->get('dataManager');
        $dataManager->clearCache();
    }

    /**
     * Run command in Console Command framework.
     */
    public function runCommand(string $command)
    {
        $this->setupSystemUser();

        $consoleCommandManager = $this->container->get('consoleCommandManager');
        return $consoleCommandManager->run($command);
    }

    /**
     * The whether the application is installed.
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
     * Get the service container.
     */
    public function getContainer() : Container
    {
        return $this->container;
    }

    protected function getMetadata()
    {
        return $this->container->get('metadata');
    }

    protected function getConfig()
    {
        return $this->container->get('config');
    }

    protected function getInjectableFactory()
    {
        return $this->container->get('injectableFactory');
    }

    protected function createSlimApp() : SlimApp
    {
        $slim = SlimAppFactory::create();
        $slim->setBasePath(Route::detectBasePath());
        return $slim;
    }

    protected function createAuth(RequestWrapper $request, bool $allowAnyAccess = false) : Auth
    {
        return $this->getInjectableFactory()->createWith(Auth::class, [
            'request' => $request,
            'allowAnyAccess' => $allowAnyAccess,
        ]);
    }

    protected function processRoute(array $route, RequestWrapper $request, ResponseWrapper $response, array $args)
    {
        $response->setHeader('Content-Type', 'application/json');

        $data = $request->getBodyContents();

        $params = [];

        $paramKeys = array_keys($route['params']);

        $setKeyList = [];

        foreach ($paramKeys as $key) {
            $value = $route['params'][$key];

            $paramName = $key;
            if ($value[0] === ':') {
                $realKey = substr($value, 1);
                $params[$paramName] = $args[$realKey];
                $setKeyList[] = $realKey;
            } else {
                $params[$paramName] = $value;
            }
        }

        foreach ($args as $key => $value) {
            if (in_array($key, $setKeyList)) continue;
            $params[$key] = $value;
        }

        $controllerName = $params['controller'] ?? null;
        $actionName = $params['action'] ?? null;

        if (!$controllerName) {
            throw new Error("Route ".$route['route']." doesn't have a controller.");
        }

        $controllerName = ucfirst($controllerName);

        if (!$actionName) {
            $httpMethod = strtolower($request->getMethod());
            $crudList = $this->getConfig()->get('crud') ?? [];
            $actionName = $crudList[$httpMethod] ?? null;
            if (!$actionName) {
                throw new Error("No action for method {$httpMethod}.");
            }
        }

        unset($params['controller']);
        unset($params['action']);

        $controllerManager = $this->container->get('controllerManager');

        $contents = $controllerManager->process($controllerName, $actionName, $params, $data, $request, $response);

        if (is_string($contents)) {
            $response->writeBody($contents);
        }
    }

    protected function initAutoloads()
    {
        $autoload = new Autoload($this->getConfig(), $this->getMetadata(), $this->container->get('fileManager'));
        $autoload->register();
    }

    protected function initPreloads()
    {
        foreach ($this->getMetadata()->get(['app', 'containerServices']) ?? [] as $name => $defs) {
            if ($defs['preload'] ?? false) {
                $this->container->get($name);
            }
        }
    }

    /**
     * Set a base path of an index file related to the application directory. Used for a portal.
     */
    public function setClientBasePath(string $basePath)
    {
        $this->container->get('clientManager')->setBasePath($basePath);
    }

    /**
     * Get a base path of an index file related to the application directory. Used for a portal.
     */
    public function getClientBasePath() : string
    {
        return $this->container->get('clientManager')->getBasePath();
    }

    public function detectPortalId() : ?string
    {
        if (!empty($_GET['portalId'])) {
            return $_GET['portalId'];
        }
        if (!empty($_COOKIE['auth-token'])) {
            $token =
                $this->container->get('entityManager')
                    ->getRepository('AuthToken')->where(['token' => $_COOKIE['auth-token']])->findOne();

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
        $user = $this->container->get('entityManager')->getEntity('User', 'system');
        if (!$user) {
            throw new Error("System user is not found");
        }

        $user->set('ipAddress', $_SERVER['REMOTE_ADDR'] ?? null);
        $user->set('type', 'system');

        $this->container->set('user', $user);
    }
}
