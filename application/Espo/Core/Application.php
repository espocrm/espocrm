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


class Application
{
	private $metadata;

	private $container;

	private $slim;

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

	public function getSlim()
	{
		if (empty($this->slim)) {
			$this->slim = $this->container->get('slim');
		}
		return $this->slim;
	}

	public function getMetadata()
	{
		if (empty($this->metadata)) {
			$this->metadata = $this->container->get('metadata');
		}
		return $this->metadata;
	}

    protected function getAuth()
    {
    	if (empty($this->auth)) {
    		$this->auth = new \Espo\Core\Utils\Auth($this->container);
    	}
    	return $this->auth;
    }

	public function getContainer()
	{
		return $this->container;
	}

    public function run($name = 'default')
    {
        $this->routeHooks();
        $this->initRoutes();
        $this->getSlim()->run();
    }

    public function runClient()
    {
    	$config = $this->getContainer()->get('config');

    	$html = file_get_contents('main.html');
    	$html = str_replace('{{cacheTimestamp}}', $config->get('cacheTimestamp', 0), $html);
    	$html = str_replace('{{useCache}}', $config->get('useCache') ? 'true' : 'false' , $html);
    	echo $html;
    	exit;
    }

    public function runEntryPoint($entryPoint)
    {
    	if (empty($entryPoint)) {
    		throw new \Error();
    	}

    	$slim = $this->getSlim();
    	$container = $this->getContainer();

		$slim->get('/', function() {});

    	$entryPointManager = new \Espo\Core\EntryPointManager($container);

    	$auth = $this->getAuth();
    	$apiAuth = new \Espo\Core\Utils\Api\Auth($auth, $entryPointManager->checkAuthRequired($entryPoint), true);
    	$slim->add($apiAuth);

		$slim->hook('slim.before.dispatch', function () use ($entryPoint, $entryPointManager, $container) {
			try {
				$entryPointManager->run($entryPoint);
			} catch (\Exception $e) {
				$container->get('output')->processError($e->getMessage(), $e->getCode(), true);
			}
		});

    	$slim->run();
    }

    public function runCron()
    {
    	$auth = $this->getAuth();
    	$auth->useNoAuth();

    	$cronManager = new \Espo\Core\CronManager($this->container);
		$cronManager->run();
    }

    public function isInstalled()
    {
    	$config = $this->getContainer()->get('config');

    	if (file_exists($config->get('configPath')) && $config->get('isInstalled')) {
    		return true;
    	}

		return false;
    }

	protected function routeHooks()
	{
		$container = $this->getContainer();
		$slim = $this->getSlim();

		$auth = $this->getAuth();

		$apiAuth = new \Espo\Core\Utils\Api\Auth($auth);
		$this->getSlim()->add($apiAuth);

		$this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container) {

			$route = $slim->router()->getCurrentRoute();
		    $conditions = $route->getConditions();

			if (isset($conditions['useController']) && $conditions['useController'] == false) {
				return;
			}

			$routeOptions = call_user_func($route->getCallable());
			$routeKeys = is_array($routeOptions) ? array_keys($routeOptions) : array();

			if (!in_array('controller', $routeKeys, true)) {
				return $container->get('output')->render($routeOptions);
			}

			$params = $route->getParams();
			$data = $slim->request()->getBody();

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
				$crudList = $container->get('config')->get('crud');
				$actionName = $crudList[$httpMethod];
			}

			try {
				$controllerManager = new \Espo\Core\ControllerManager($container);
				$result = $controllerManager->process($controllerName, $actionName, $params, $data, $slim->request());
				$container->get('output')->render($result);
			} catch (\Exception $e) {
				$container->get('output')->processError($e->getMessage(), $e->getCode());
			}
		});

		$this->getSlim()->hook('slim.after.router', function () use (&$slim) {
			$slim->contentType('application/json');

			$res = $slim->response();
			$res->header('Expires', '0');
			$res->header('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
			$res->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
			$res->header('Pragma', 'no-cache');
		});
	}


	protected function initRoutes()
	{
		$routes = new \Espo\Core\Utils\Route($this->getContainer()->get('config'), $this->getMetadata(), $this->getContainer()->get('fileManager'));
		$crudList = array_keys( $this->getContainer()->get('config')->get('crud') );

		foreach ($routes->getAll() as $route) {

			$method = strtolower($route['method']);
			if (!in_array($method, $crudList)) {
				$GLOBALS['log']->error('Route: Method ['.$method.'] does not exist. Please check your route ['.$route['route'].']');
				continue;
			}

            $currentRoute = $this->getSlim()->$method($route['route'], function() use ($route) {   //todo change "use" for php 5.4
	        	return $route['params'];
			});

			if (isset($route['conditions'])) {
            	$currentRoute->conditions($route['conditions']);
			}
		}
	}
}

