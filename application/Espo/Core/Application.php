<?php

namespace Espo\Core;


class Application
{
	private $metadata;

	private $container;
	
	private $serviceFactory;

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

    	$this->serviceFactory = new ServiceFactory($this->container);
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

	public function getServiceFactory()
	{
		return $this->serviceFactory;
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
		$serviceFactory = $this->getServiceFactory();

		$auth = new \Espo\Core\Utils\Api\Auth($container->get('entityManager'), $container);
		$this->getSlim()->add($auth);

		$this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container, $serviceFactory) {

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
				$controllerManager = new \Espo\Core\ControllerManager($container, $serviceFactory);
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

		foreach($routes->getAll() as $route) {

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

		/* //todo remove when it is tested
		$this->getSlim()->get('/', function() {
        	return $template = "<h1>EspoCRM REST API</h1>";
		});

		$this->getSlim()->get('/App/user/', function() {
        	return '{"user":{"modified_by_name":"Administrator","created_by_name":"","id":"1","user_name":"admin","user_hash":"","system_generated_password":"0","pwd_last_changed":"","authenticate_id":"","sugar_login":"1","first_name":"","last_name":"Administrator","full_name":"Administrator","name":"Administrator","is_admin":"1","external_auth_only":"0","receive_notifications":"1","description":"","date_entered":"2013-06-13 12:18:44","date_modified":"2013-06-13 12:19:48","modified_user_id":"1","created_by":"","title":"Administrator","department":"","phone_home":"","phone_mobile":"","phone_work":"","phone_other":"","phone_fax":"","status":"Active","address_street":"","address_city":"","address_state":"","address_country":"","address_postalcode":"","UserType":"","deleted":"0","portal_only":"0","show_on_employees":"1","employee_status":"Active","messenger_id":"","messenger_type":"","reports_to_id":"","reports_to_name":"","email1":"test@letrium.com","email_link_type":"","is_group":"0","c_accept_status_fields":" ","m_accept_status_fields":" ","accept_status_id":"","accept_status_name":""},"preferences":{}}';
		});


		$this->getSlim()->get('/Metadata/', function() {
			return array(
				'controller' => 'Metadata',
			);
		});

		$this->getSlim()->get('/Settings/', function() {
			return array(
				'controller' => 'Settings',
			);
		})->conditions( array('auth' => false) );
		$this->getSlim()->map('/Settings/', function() {
			return array(
				'controller' => 'Settings',
			);
		})->via('PATCH');

		$this->getSlim()->get('/:controller/layout/:name/', function() {
			return array(
				'controller' => 'Layout',
				'scope' => ':controller',
			);
		});
		$this->getSlim()->put('/:controller/layout/:name/', function() {
			return array(
				'controller' => 'Layout',
				'scope' => ':controller',
			);
		});
		$this->getSlim()->map('/:controller/layout/:name/', function() {
			return array(
				'controller' => 'Layout',
				'scope' => ':controller',
			);
		})->via('PATCH');

		$this->getSlim()->get('/Admin/rebuild/', function() {
			return array(
				'controller' => 'Admin',
				'action' => 'rebuild',
			);
		});		

		$this->getSlim()->get('/:controller/:id', function() {
			return array(
				'controller' => ':controller',
				'action' => 'read',
		        'id' => ':id'
			);
		});
		
		$this->getSlim()->get('/:controller', function() {
			return array(
				'controller' => ':controller',
				'action' => 'index',
			);
		});

		$this->getSlim()->post('/:controller', function() {
			return array(
				'controller' => ':controller',
				'action' => 'create'
			);
		});

		$this->getSlim()->put('/:controller/:id', function() {
			return array(
				'controller' => ':controller',
				'action' => 'update',
				'id' => ':id'
			);
		});

		$this->getSlim()->map('/:controller/:id', function() {
			return array(
				'controller' => ':controller',
				'action' => 'patch',
				'id' => ':id'
			);
		})->via('PATCH');
		
		$this->getSlim()->get('/:controller/:id/:link', function() {
			return array(
				'controller' => ':controller',
				'action' => 'listLinked',
				'id' => ':id',
				'link' => ':link',
			);
		});

		$this->getSlim()->get('/:controller/:id/:link/:foreignId', function() {
			return array(
				'controller' => ':controller',
				'action' => 'readRelated',
				'id' => ':id',
				'link' => ':link',
				'foreignId' => ':foreignId'
			);
		});  */

	}

}
