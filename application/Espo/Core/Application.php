<?php

namespace Espo\Core;


class Application
{
	private $metadata;

	private $container;

	private $slim;

	/**
     * Constructor
     */
    public function __construct()
    {
    	$this->container = new Container();

		$GLOBALS['log'] = $this->log = $this->container->get('log');

        set_error_handler(array($this->getLog(), 'catchError'), E_ALL);
		set_exception_handler(array($this->getLog(), 'catchException'));
		
		date_default_timezone_set('UTC');

		$this->slim = $this->container->get('slim');
		$this->metadata = $this->container->get('metadata');

		$this->initMetadata();
    }

	public function getSlim()
	{
		return $this->slim;
	}

	public function getMetadata()
	{
		return $this->metadata;
	}

	public function getContainer()
	{
		return $this->container;
	}

	public function getLog()
	{
		return $this->log;
	}

    public function run($name = 'default')
    {
        $this->routeHooks();
        $this->initRoutes();
        $this->getSlim()->run();
    }


	protected function initMetadata()
	{
    	$isNotCached = !$this->getMetadata()->isCached();

        $this->getMetadata()->init($isNotCached);
	}

	protected function routeHooks()
	{
		$container = $this->getContainer();
		$slim = $this->getSlim();

		$auth = new \Espo\Core\Utils\Api\Auth($container->get('entityManager'), $container);
		$this->getSlim()->add($auth);

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
				$actionName = $container->get('config')->get('crud')->$httpMethod;
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
		});
	}


	protected function initRoutes()
	{
		$routes = new \Espo\Core\Utils\Route($this->getContainer()->get('config'), $this->getContainer()->get('fileManager'));
		$crudList = array_keys( (array) $this->getContainer()->get('config')->get('crud') );

		foreach ($routes->getAll() as $route) {

			$method = strtolower($route['method']);
			if (!in_array($method, $crudList)) {
				$GLOBALS['log']->add('ERROR', 'Route: Method ['.$method.'] does not exist. Please check your route ['.$route['route'].']');
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

