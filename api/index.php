<?php

require_once('../bootstrap.php');

use \Espo\Utils\Api as Api,
	\Espo\Utils as Utils,
	\Slim;

/* START: remove for composer */
require 'vendor/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
/* END: remove for composer */

//$routes = new \Slim\Slim();

$routes = new \Slim\Slim(array(
    'mode' => 'development'
));
$routes->add(new Api\Auth());

//convert all url params to camel case format
$routes->hook('slim.before.dispatch', function () use ($routes) {
	$routeParams= $routes->router()->getCurrentRoute()->getParams();

	if (!empty($routeParams)) {
		$baseUtils= new Utils\BaseUtils();
		foreach($routeParams as &$param) {
	       $param= $baseUtils->toCamelCase($param);
		}

	    $routes->router()->getCurrentRoute()->setParams($routeParams);
	}
});
//END: convert all url params to camel case format


$routes->hook('slim.before.dispatch', function () use ($routes) {

	$currentRoute = $routes->router()->getCurrentRoute();
    $conditions = $currentRoute->getConditions();

	if (isset($conditions['useController']) && $conditions['useController'] == false) {
		return;
	}

	$espoController = call_user_func( $routes->router()->getCurrentRoute()->getCallable() );
	$espoKeys = array_keys($espoController);

	if (!in_array('controller', $espoKeys)) {
		return;
	}

	$ControllerManager = new Utils\Controllers\Manager();

	$params = $currentRoute->getParams();
	$data = $routes->request()->getBody();

	//prepare controller Params
	$controllerParams = array();
    $controllerParams['HttpMethod'] = strtolower($routes->request()->getMethod());

	foreach($espoController as $key => $val) {
    	if (strstr($val, ':')) {
    		$paramName = str_replace(':', '', $val);
        	$val = $params[$paramName];
    	}
		$controllerParams[$key] = $val;
	}
	//END: prepare controller Params

	$result = $ControllerManager->call($controllerParams, $params, $data);

	return Api\Helper::output($result->data, $result->errMessage, $result->errCode);
});


//return json response
$routes->hook('slim.after.router', function () use (&$routes) {
    $routes->contentType('application/json');
    //$routes->contentType('text/javascript');
});
//END: return json response


//Setup routes
$routes->get('/', '\Espo\Utils\Api\Rest::main')->conditions( array('useController' => false) );
$routes->get('/app/user/', '\Espo\Utils\Api\Rest::getAppUser')->conditions( array('useController' => false) );

//METADATA
$routes->get('/metadata/', function() {
	return array(
		'controller' => 'Metadata',
	);
});

$routes->put('/metadata/:type/:scope/', function() {
	return array(
		'controller' => 'Metadata',
		'scope' => ':scope',
		'action' => ':type',
	);
});
//END: METADATA

//SETTINGS
$routes->get('/settings/', function() {
	return array(
		'controller' => 'Settings',
	);
})->conditions( array('auth' => false) );

$routes->map('/settings/', function() {
	return array(
		'controller' => 'Settings',
	);
})->via('PATCH');
//END: SETTINGS

//LAYOUT
$routes->get('/:controller/layout/:name/', function() {
	return array(
		'controller' => 'Layout',
		'scope' => ':controller',
		'action' => ':name',
	);
});

$routes->put('/:controller/layout/:name/', function() {
	return array(
		'controller' => 'Layout',
		'scope' => ':controller',
		'action' => ':name',
	);
});

$routes->map('/:controller/layout/:name/', function() {
	return array(
		'controller' => 'Layout',
		'scope' => ':controller',
		'action' => ':name',
	);
})->via('PATCH');
//END: LAYOUT


/*$routes->get('/:controller/:id', function() {
	return array(
		'controller' => ':controller',
		'action' => 'read',
        'id' => ':id'
	);
});

$routes->post('/:controller', function() {
	return array(
		'controller' => ':controller',
		'action' => 'create',
	);
});

$routes->put('/:controller/:id', function() {
	return array(
		'controller' => ':controller',
		'action' => 'update',
		 'id' => ':id'
	);
});

$routes->patch('/:controller/:id', function() {
	return array(
		'controller' => ':controller',
		'action' => 'patch',
		 'id' => ':id'
	);
});


$routes->get('/:controller/:id/:link/:foreignId', function() {
	return array(
		'controller' => ':controller',
		'action' => 'readRelated',
		'id' => ':id',
		'link' => ':link',
		'foreignId' => ':foreignId'
	);
});    */



$routes->run();

?>