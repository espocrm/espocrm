<?php

namespace Espo\Core\Utils\Api;

use \Slim\Slim;

class Auth extends \Slim\Middleware
{
	private $container;

	protected $realm = 'Protected Area';


	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}

    protected function getContainer()
	{
		return $this->container;
	}



	function call()
	{
		$req = $this->app->request();
        $res = $this->app->response();

		$uri = $req->getResourceUri();
		$httpMethod = $req->getMethod();

		/**
		* Check if user credentials are required for current route
		*/
		$routes= $this->app->router()->getMatchedRoutes($httpMethod, $uri);

		if (!empty($routes[0])) {
			$routeConditions = $routes[0]->getConditions();
        	if (isset($routeConditions['auth']) && $routeConditions['auth']===false) {
	        	$this->next->call();
				return;
			}
		}

		$authKey = $req->headers('PHP_AUTH_USER');
        $authSec = $req->headers('PHP_AUTH_PW');

        if ($authKey && $authSec) {

			$isAuthenticated = $this->getContainer()->get('user')->authenticate($authKey, $authSec);

            if($isAuthenticated){
                $this->next->call();
            }else{
                 $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
                 $res->status(401);
            }
        } else {
            $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
            $res->status(401);
        }

	}
}

?>