<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Core;

use Espo\Core\Utils\Api\Auth as ApiAuth;
use Espo\Core\Utils\Api\Output;
use Espo\Core\Utils\Api\Slim;
use Espo\Core\Utils\Auth;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Route;

class Application
{

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Slim
     */
    private $slim;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->container = new Container();
        date_default_timezone_set('UTC');
        $GLOBALS['log'] = $this->container->get('log');
    }

    /**
     * @param string $name
     *

     */
    public function run($name = 'default')
    {
        $this->routeHooks();
        $this->initRoutes();
        $this->getSlim()->run();
    }

    protected function routeHooks()
    {
        $container = $this->getContainer();
        $slim = $this->getSlim();
        $auth = $this->getAuth();
        $apiAuth = new ApiAuth($auth);
        $this->getSlim()->add($apiAuth);
        $this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container){
            $route = $slim->router()->getCurrentRoute();
            $conditions = $route->getConditions();
            if (isset($conditions['useController']) && $conditions['useController'] == false) {
                return;
            }
            $routeOptions = call_user_func($route->getCallable());
            $routeKeys = is_array($routeOptions) ? array_keys($routeOptions) : array();
            if (!in_array('controller', $routeKeys, true)) {
                /**
                 * @var Output $output
                 *
                 */
                $output = $container->get('output');
                $output->render($routeOptions);
                return;
            }
            $params = $route->getParams();
            $data = $slim->request()->getBody();
            $controllerParams = array();
            foreach ($routeOptions as $key => $value) {
                if (strstr($value, ':')) {
                    $paramName = str_replace(':', '', $value);
                    $value = $params[$paramName];
                }
                $controllerParams[$key] = $value;
            }
            $params = array_merge($params, $controllerParams);
            $controllerName = ucfirst($controllerParams['controller']);
            if (!empty($controllerParams['action'])) {
                $actionName = $controllerParams['action'];
            } else {
                $httpMethod = strtolower($slim->request()->getMethod());
                /**
                 * @var Config $config
                 */
                $config = $container->get('config');
                $crudList = $config->get('crud');
                $actionName = $crudList[$httpMethod];
            }
            try{
                $controllerManager = new ControllerManager($container);
                $result = $controllerManager->process($controllerName, $actionName, $params, $data, $slim->request());
                /**
                 * @var Output $output
                 *
                 */
                $output = $container->get('output');
                $output->render($result);
            } catch(\Exception $e){
                /**
                 * @var Output $output
                 *
                 */
                $output = $container->get('output');
                $output->processError($e->getMessage(), $e->getCode());
            }
        });
        $this->getSlim()->hook('slim.after.router', function () use (&$slim){
            $slim->contentType('application/json');
            $res = $slim->response();
            $res->header('Expires', '0');
            $res->header('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
            $res->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            $res->header('Pragma', 'no-cache');
        });
    }

    /**
     * @return Container

     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Slim
     */
    public function getSlim()
    {
        if (empty($this->slim)) {
            $this->slim = $this->container->get('slim');
        }
        return $this->slim;
    }

    /**
     * @return Auth

     */
    protected function getAuth()
    {
        if (empty($this->auth)) {
            $this->auth = new Auth($this->container);
        }
        return $this->auth;
    }

    protected function initRoutes()
    {
        /**
         * @var Config      $config
         * @var Log         $log
         * @var \Slim\Route $currentRoute
         */
        $routes = new Route($this->getContainer()->get('config'), $this->getMetadata(),
            $this->getContainer()->get('fileManager'));
        $config = $this->getContainer()->get('config');
        $crudList = array_keys($config->get('crud'));
        foreach ($routes->getAll() as $route) {
            $method = strtolower($route['method']);
            if (!in_array($method, $crudList)) {
                $log = $GLOBALS['log'];
                $log->error('Route: Method [' . $method . '] does not exist. Please check your route [' . $route['route'] . ']');
                continue;
            }
            $currentRoute = $this->getSlim()->$method($route['route'],
                function () use ($route){   //todo change "use" for php 5.4
                    return $route['params'];
                });
            if (isset($route['conditions'])) {
                $currentRoute->conditions($route['conditions']);
            }
        }
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        if (empty($this->metadata)) {
            $this->metadata = $this->container->get('metadata');
        }
        return $this->metadata;
    }

    /**

     */
    public function runClient()
    {
        /**
         * @var $config Config
         */
        $config = $this->getContainer()->get('config');
        $html = file_get_contents('main.html');
        $html = str_replace('{{cacheTimestamp}}', $config->get('cacheTimestamp', 0), $html);
        $html = str_replace('{{useCache}}', $config->get('useCache') ? 'true' : 'false', $html);
        echo $html;
        exit;
    }

    public function runEntryPoint($entryPoint)
    {
        if (empty($entryPoint)) {
            throw new \ErrorException("No Entry Point Provided");
        }
        $slim = $this->getSlim();
        $container = $this->getContainer();
        $slim->get('/', function (){
        });
        $entryPointManager = new EntryPointManager($container);
        $auth = $this->getAuth();
        $apiAuth = new ApiAuth($auth, $entryPointManager->checkAuthRequired($entryPoint), true);
        $slim->add($apiAuth);
        $slim->hook('slim.before.dispatch', function () use ($entryPoint, $entryPointManager, $container){
            try{
                $entryPointManager->run($entryPoint);
            } catch(\Exception $e){
                /**
                 * @var $output Output
                 */
                $output = $container->get('output');
                $output->processError($e->getMessage(), $e->getCode(), true);
            }
        });
        $slim->run();
    }

    public function runCron()
    {
        $auth = $this->getAuth();
        $auth->useNoAuth(true);
        $cronManager = new CronManager($this->container);
        $cronManager->run();
    }

    public function runRebuild()
    {
        /**
         * @var DataManager $dataManager
         */
        $dataManager = $this->getContainer()->get('dataManager');
        $dataManager->rebuild();
    }

    public function isInstalled()
    {
        /**
         * @var Config $config
         */
        $config = $this->getContainer()->get('config');
        if (file_exists($config->getConfigPath()) && $config->get('isInstalled')) {
            return true;
        }
        return false;
    }
}

