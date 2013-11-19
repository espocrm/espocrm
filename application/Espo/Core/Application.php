<?php

namespace Espo\Core;


class Application
{

	protected static $apps = array();


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

    	$this->serviceFactory = new ServiceFactory($this->container);
		$this->slim = $this->container->get('slim');
    }

	public function getSlim()
	{
		return $this->slim;
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
    	//set_error_handler(array($this->getLog(), 'catchError'), E_ALL);
		//set_exception_handler(array($this->getLog(), 'catchException'));

        $this->routeHooks();
        $this->routes();

        $this->getSlim()->run();

		static::$apps[$name] = $this;
    
    	// TODO place routing HERE
    	// dispatch which controller to use
    	// $this->controller = new $controllerClassName($this->container, $this->serviceFactory);
    	// call needed controller method $this->$method($params, $data)


		// dont't return anything here
    }


	public static function getInstance($name = 'default')
    {
        return isset(static::$apps[$name]) ? static::$apps[$name] : null;
    }


	protected function routeHooks()
	{
		$container = $this->getContainer();
		$slim = $this->getSlim();
		$serviceFactory = $this->getServiceFactory();

		//check user credentials
		$this->getSlim()->add(new \Espo\Core\Utils\Api\Auth( $container ));

		//convert all url params to camel case format
		$this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container) {

			$conditions = $slim->router()->getCurrentRoute()->getConditions();
			$upperList = isset($conditions['upper']) ? $conditions['upper'] : array();

            $routeParams= $slim->router()->getCurrentRoute()->getParams();

			if (!empty($routeParams)) {
				foreach($routeParams as $name => &$param) {
                    $isUpper = in_array($name, $upperList) ? true : false;
					$param= \Espo\Core\Utils\Util::toCamelCase($param, $isUpper);
				}

			    $slim->router()->getCurrentRoute()->setParams($routeParams);
			}
		});
		//END: convert all url params to camel case format


		$this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container, $serviceFactory) {

			$currentRoute = $slim->router()->getCurrentRoute();
		    $conditions = $currentRoute->getConditions();

			if (isset($conditions['useController']) && $conditions['useController'] == false) {
				return;
			}

			$espoController = call_user_func( $slim->router()->getCurrentRoute()->getCallable() );
			$espoKeys = is_array($espoController) ? array_keys($espoController) : array();

			if (!in_array('controller', $espoKeys, true)) {
				return $container->get('rest')->render($espoController);
			}


			$params = $currentRoute->getParams();
			$data = $slim->request()->getBody();

			//prepare controller Params
			$controllerParams = array();
		    $controllerParams['HttpMethod'] = strtolower($slim->request()->getMethod());

			foreach($espoController as $key => $val) {
				if (strstr($val, ':')) {
				$paramName = str_replace(':', '', $val);
					$val = $params[$paramName];
				}
				$controllerParams[$key] = $val;
			}

			$controllerParams['container'] = $container;
			$controllerParams['serviceFactory'] = $serviceFactory;
			//END: prepare controller Params

			$result = $container->get('controllerManager')->call($controllerParams, $params, $data);

			return $container->get('rest')->render($result->data, $result->errMessage, $result->errCode);
		});


		//return json response
		$this->getSlim()->hook('slim.after.router', function () use (&$slim) {
			$slim->contentType('application/json');
			//$routes->contentType('text/javascript');
		});
		//END: return json response
	}


	protected function routes()
	{
		//$this->getSlim()->get('/', '\Espo\Utils\Api\Rest::main')->conditions( array('useController' => false) );

		$this->getSlim()->get('/', function() {
        	return $template = <<<EOT
	            <h1>Main Page of REST API!!!</h1>
EOT;
		}); // ->conditions( array('useController' => false) );

		$this->getSlim()->get('/app/user/', function() {
        	return '{"user":{"modified_by_name":"Administrator","created_by_name":"","id":"1","user_name":"admin","user_hash":"","system_generated_password":"0","pwd_last_changed":"","authenticate_id":"","sugar_login":"1","first_name":"","last_name":"Administrator","full_name":"Administrator","name":"Administrator","is_admin":"1","external_auth_only":"0","receive_notifications":"1","description":"","date_entered":"2013-06-13 12:18:44","date_modified":"2013-06-13 12:19:48","modified_user_id":"1","created_by":"","title":"Administrator","department":"","phone_home":"","phone_mobile":"","phone_work":"","phone_other":"","phone_fax":"","status":"Active","address_street":"","address_city":"","address_state":"","address_country":"","address_postalcode":"","UserType":"","deleted":"0","portal_only":"0","show_on_employees":"1","employee_status":"Active","messenger_id":"","messenger_type":"","reports_to_id":"","reports_to_name":"","email1":"test@letrium.com","email_link_type":"","is_group":"0","c_accept_status_fields":" ","m_accept_status_fields":" ","accept_status_id":"","accept_status_name":""},"preferences":{}}';
		}); //->conditions( array('useController' => false) );

		//METADATA
		$this->getSlim()->get('/metadata/', function() {
			return array(
				'controller' => 'Metadata',
			);
		});

		/*$this->getSlim()->put('/metadata/:type/:scope/', function() {
			return array(
				'controller' => 'Metadata',
				'scope' => ':scope',
				'action' => ':type',
			);
		})->conditions( array('upper' => array('scope')) ); */
		//END: METADATA

		//SETTINGS
		$this->getSlim()->get('/settings/', function() {
			return array(
				'controller' => 'Settings',
			);
		}); //->conditions( array('auth' => false) );

		$this->getSlim()->map('/settings/', function() {
			return array(
				'controller' => 'Settings',
			);
		})->via('PATCH');
		//END: SETTINGS

		//LAYOUT
		$this->getSlim()->get('/:controller/layout/:name/', function() {
			return array(
				'controller' => 'Layout',
				'scope' => ':controller',
				'action' => ':name',
			);
		})->conditions( array('upper' => array('controller')) );

		$this->getSlim()->put('/:controller/layout/:name/', function() {
			return array(
				'controller' => 'Layout',
				'scope' => ':controller',
				'action' => ':name',
			);
		})->conditions( array('upper' => array('controller')) );

		$this->getSlim()->map('/:controller/layout/:name/', function() {
			return array(
				'controller' => 'Layout',
				'scope' => ':controller',
				'action' => ':name',
			);
		})->via('PATCH')->conditions( array('upper' => array('controller')) );
		//END: LAYOUT


		/*$this->getSlim()->get('/:controller/:id', function() {
			return array(
				'controller' => ':controller',
				'action' => 'read',
		        'id' => ':id'
			);
		});

		$this->getSlim()->post('/:controller', function() {
			return array(
				'controller' => ':controller',
				'action' => 'create',
			);
		});

		$this->getSlim()->put('/:controller/:id', function() {
			return array(
				'controller' => ':controller',
				'action' => 'update',
				 'id' => ':id'
			);
		});

		$this->getSlim()->patch('/:controller/:id', function() {
			return array(
				'controller' => ':controller',
				'action' => 'patch',
				 'id' => ':id'
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
		});    */
	}



}
