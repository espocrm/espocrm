<?php

namespace Espo\Core\Utils\Api;

use \Espo\Core\Utils\Api\Slim;

class Auth extends \Slim\Middleware
{
	protected $auth;
	
	protected $authRequired = null;
	
	protected $showDialog = false;

	public function __construct(\Espo\Core\Utils\Auth $auth, $authRequired = null, $showDialog = false)
	{
		$this->auth = $auth;		
		$this->authRequired = $authRequired;		
		$this->showDialog = $showDialog;		
	}	

	function call()
	{
		$req = $this->app->request();        

		$uri = $req->getResourceUri();
		$httpMethod = $req->getMethod();		

		if (is_null($this->authRequired)) {
			$routes = $this->app->router()->getMatchedRoutes($httpMethod, $uri);

			if (!empty($routes[0])) {
				$routeConditions = $routes[0]->getConditions();
		    	if (isset($routeConditions['auth']) && $routeConditions['auth'] === false) {        	
		    		$this->auth->useNoAuth();
			    	$this->next->call();
					return;
				}
			}
		} else {
			if (!$this->authRequired) {
		    	$this->auth->useNoAuth();
			    $this->next->call();
				return;
			}
		}

		$authKey = $req->headers('PHP_AUTH_USER');
        $authSec = $req->headers('PHP_AUTH_PW');   

        if ($authKey && $authSec) {
			$isAuthenticated = false;
			
			$username = $authKey;
			$password = $authSec;
			
			$isAuthenticated = $this->auth->login($username, $password);           

            if ($isAuthenticated) {
                $this->next->call();
            } else {
				$this->processUnauthorized();
            }
        } else {        	
        	if (!$this->isXMLHttpRequest()) {
        		$this->showDialog = true;		
        	}        	
			$this->processUnauthorized();
        }
	}
	
	protected function processUnauthorized()
	{
		$res = $this->app->response();
		
		if ($this->showDialog) {
			$res->header('WWW-Authenticate', 'Basic realm=""');
		} else {
			$res->header('WWW-Authenticate');
		}
		$res->status(401);		
	}

	protected function isXMLHttpRequest()
	{
		$req = $this->app->request();  

		$httpXRequestedWith = $req->headers('HTTP_X_REQUESTED_WITH');

		if (isset($httpXRequestedWith) && strtolower($httpXRequestedWith) == 'xmlhttprequest') {
			return true;
		}

		return false;
	}

}

