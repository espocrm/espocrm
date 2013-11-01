<?php

namespace Espo\Utils\Api;

use \Slim\Slim,
	\Espo\Entities as Entities;

class Auth extends \Slim\Middleware
{
	protected $noAuthRoutes = array();

	function __construct($noAuthRoutes = array())
	{
		$this->noAuthRoutes = $noAuthRoutes;

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

			$User= new Entities\User();
            $app= $User->login($authKey, $authSec);

            if($app){
				//set the current user
				global $base;
				$base->currentUser= $base->em->getRepository('\Espo\Entities\User')->findOneBy(array('username' => $authKey));
				//END: set the current user

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